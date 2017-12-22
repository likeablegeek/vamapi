<?php

/*

	VAMAPI 0.2-2.6.2 (https://github.com/likeablegeek/vamapi)
	PHP REST API for VAM 2.6.2 (http://virtualairlinesmanager.net/)

	By: Arman Danesh
	
	Based on Lumen 5.5
	
	License:

	Copyright (c) 2017 Arman Danesh

	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.
	
*/

/*

	app/Http/Controllers/FlightController.php
	
	This controller provides methods for accessing data about the virtual airline's
	flights as well as booking and cancelling flights and filing manual PIREPs:
	
	latest_flights(): returns a list of the latest flights flown by all pilots
	available_flights(): returns a list of flight available for the pilot to book
	available_aircraft(): returns a list of aircraft available for a pilot to book on 
							a specified flight
	book_flight(): books a flight with a specified aircraft for a specified pilot
	pirep_flight(): files a manual pirep for a flight
	cancel_flight(): cancels a flight for the specified pilot using a specified aircraft
	
*/

namespace App\Http\Controllers;

class FlightController extends Controller
{
    /* Create a new controller instance */
    public function __construct()
    {
		$this->middleware('auth'); // Set up API auth
		
		$this->load_dependencies(); // Dependencies
		
	}

	/* Load dependencies */
	private function load_dependencies() {

		require_once dirname( __FILE__ ) . '/vamapi.php'; // VAM airline params
		
	}
	
	/*
	
		Controller methods
		
	*/

	/* Return list of latest flights flown by all pilots */
	public function latest_flights() {

		$flights = app('db')->select("select gvauser_id,a1.name as dep_name, a2.name as arr_name, a1.iso_country as 
										dep_country,a2.iso_country as arr_country,callsign,pilot_name,departure,
										arrival,DATE_FORMAT(date,:va_date_format) as date_string, date, 
										format(time,0) as time from v_last_5_flights v, airports a1, airports a2
										where v.departure=a1.ident and v.arrival=a2.ident and time is not null 
										order by date desc",
										[
											"va_date_format"=>$this->va_date_format
										]);

		return response()->json($flights);

	}

	/* Return list of flight available for the pilot to book */
	public function available_flights($pilot) {

		$vam_id = get_pilot_callsign($pilot);

		$pilot_route = app('db')->select("select route_id from gvausers gu where gu.gvauser_id=:vam_id",['vam_id'=>$vam_id]);

		if (count($pilot_route) == 0) { return response()->json(['No available flights']); }

		if ($pilot_route[0]->route_id == "0" || $pilot_route[0]->route_id == NULL) {

			$routes = app('db')->select("select distinct a3.iso_country as alt_country, 
							a3.name as alt_name,a1.name as dep_name, 
							a2.name as arr_name, a1.iso_country as dep_country,
							a2.iso_country as arr_country, 
							r.route_id as route, flight,r.departure,
							r.arrival,alternative,etd,eta,duration from
							fleets f inner join gvausers g on g.location = f.location
							inner join routes r on r.departure = f.location
							inner join fleettypes_routes ftr on ftr.route_id = r.route_id
							inner join fleettypes_gvausers ftu on ftu.fleettype_id = f.fleettype_id
							inner join airports a1 on (a1.ident=r.departure)
							inner join airports a2 on (a2.ident=r.arrival)
							inner join airports a3 on (a3.ident=r.alternative)
							where f.booked=0
							and ftr.fleettype_id = f.fleettype_id
							and g.gvauser_id=:vam_id1
							and ftu.gvauser_id=:vam_id2", 
							['vam_id1'=>$vam_id, 'vam_id2'=>$vam_id]);

			foreach ($routes as $route) {

				$icaos = app('db')->select("select ft.plane_icao from fleettypes_routes fr, routes r, fleettypes ft where r.route_id=:routeid and r.route_id=fr.route_id and fr.fleettype_id=ft.fleettype_id",['routeid'=>$route->route]);

				$icaolist = "";

				foreach ($icaos as $icao) {

					$icaolist = $icaolist . " " . $icao->plane_icao;

				}

				$route->planes_icaos = $icaolist;

			}

			return response()->json($routes);

		}

	}

	/* Return list of aircraft available for a pilot to book on a specified flight */
	public function available_aircraft($pilot,$flight) {

		$vam_id = get_pilot_callsign($pilot);

		$route = app('db')->select("select route_id from routes where flight=:flight",['flight'=>$flight]);
		$route_id = $route[0]->route_id;

		$planes = app('db')->select("SELECT DISTINCT a3.name as alt_name, 
					a3.iso_country as alt_country, a1.name as dep_name, 
					a2.name as arr_name, a1.iso_country as dep_country,
					a2.iso_country as arr_country , r.route_id,r.flight flight, 
					f.fleet_id, ft.plane_icao as icao, registry as reg,
					status , plane_description, r.departure, r.arrival, duration, 
					etd,eta,pax_price,flproute,comments, alternative, flight_level
					FROM gvausers gu, fleets f, fleettypes ft, routes r, 
					fleettypes_gvausers ftgu, fleettypes_routes ftro, airports a1, airports a2 ,airports a3
					WHERE a1.ident=r.departure
					AND a2.ident=r.arrival
					AND a3.ident=r.alternative
					AND gu.gvauser_id = ftgu.gvauser_id
					AND ftgu.fleettype_id = f.fleettype_id
					AND ft.fleettype_id = f.fleettype_id
					AND ft.fleettype_id = ftgu.fleettype_id
					AND ftro.fleettype_id = f.fleettype_id
					AND ft.fleettype_id = ftro.fleettype_id
					AND ftro.route_id = r.route_id
					AND r.departure = gu.location
					AND gu.gvauser_id =:vam_id
					AND f.location = gu.location
					AND	f.booked = 0
					AND r.route_id=:route_id order by plane_description, registry asc",
					['vam_id'=>$vam_id, 'route_id'=>$route_id]);

		return response()->json($planes);

	}

	/* Book a flight with a specified aircraft for a specified pilot */
	public function book_flight($pilot,$flight,$aircraft) {

		$vam_id = get_pilot_callsign($pilot);

		$route = app('db')->select("select route_id from routes where flight=:flight",['flight'=>$flight]);
		if (count($route) < 1) { return response()->json(["Booking failed. The specified route does not exist."]); }
		$route_id = $route[0]->route_id; 
		
		$plane = app('db')->select("select fleet_id from fleets where registry=:aircraft",['aircraft'=>$aircraft]);
		if (count($plane) < 1) { return response()->json(["Booking failed. The specified aircraft does not exist."]); }
		$plane_id = $plane[0]->fleet_id;

		$reserves = app('db')->select("select * from reserves where gvauser_id=:vam_id",['vam_id'=>$vam_id]);
		if (count($reserves) > 0) { return response()->json(["Booking failed. You already have a flight booked."]); }

		$route_booked = app('db')->select("select route_id from gvausers where route_id=:route_id", ['route_id'=>$route_id]);
		if (count($route_booked) > 0) { return response()->json(["Booking failed. Route is already booked."]); }

		$plane_booked = app('db')->update("UPDATE fleets set booked=1, gvauser_id=:vam_id, booked_at=now() where fleet_id=:plane_id and booked=0",['vam_id'=>$vam_id, 'plane_id'=>$plane_id]);
		if ($plane_booked < 1) { return response()->json("Booking failed. The aircraft is already booked."); }

		$flight_booked = app('db')->update("update gvausers set route_id=:route_id where gvauser_id=:vam_id",['route_id'=>$route_id, 'vam_id'=>$vam_id]);
		if ($flight_booked < 1) { return response()->json(["Booking failed. Failed to reserve route for user."]); }

		$plane_data = app('db')->select("select * from fleets f inner join fleettypes ft on (f.fleettype_id = ft.fleettype_id) and f.fleet_id=:plane_id", ['plane_id'=>$plane_id]);
		$pax = intval($plane_data[0]->pax * (rand (85,100) / 100));
		$cargo =  intval ($plane_data[0]->cargo_capacity * (rand (85,100) / 100));
		$registry = $plane_data[0]->registry;
		$name = $plane_data[0]->name;
		$plane_icao = $plane_data[0]->plane_icao;
		$status = $plane_data[0]->status;

		$deleted = app('db')->delete("delete from reserves where gvauser_id=:vam_id", ['vam_id'=>$vam_id]);
		$reserved = app('db')->insert("INSERT into reserves (route_id,gvauser_id,fleet_id,pax,cargo) values (:route_id,:vam_id,:plane_id,:pax,:cargo)", [
			'route_id'=>$route_id,
			'vam_id'=>$vam_id,
			'plane_id'=>$plane_id,
			'pax'=>$pax,
			'cargo'=>$cargo
		]);

		$reservations = app('db')->select('select f.status as status,r.pax as pax,
						departure,arrival,cargo,flight,flproute , 
						flight_level, plane_icao, name, registry, cargo 
						from fleets f inner join fleettypes ft on 
						(f.fleettype_id = ft.fleettype_id)
						inner join reserves r on (r.gvauser_id=f.gvauser_id)
						inner join routes ro on (ro.route_id=r.route_id)
						and f.fleet_id=:plane_id', ['plane_id'=>$plane_id]);

		return response()->json(["Flight booked.", $reservations]);

	}

	/* Pirep a flight */
	public function pirep_flight($pilot,$date,$aircraft,$departure,$arrival,$distance,$time,$fuel,$comments) {

		$vam_id = get_pilot_callsign($pilot);

		$charter = null;
		$route_id = null;
		$flight = null;
		$pax = null;
		$cargo = null;

		$fleettype = app('db')->select("select * from fleettypes where plane_icao=:aircraft",["aircraft"=>$aircraft]);
		$aircraft_id = $fleettype[0]->fleettype_id;
		
		$route = app('db')->select("select r.route_id route, r.flight flight from gvausers, routes r 
									where gvauser_id=:vam_id and gvausers.route_id is not null and 
									r.departure=:departure and r.arrival=:arrival and gvausers.route_id=r.route_id ",
									[
										'vam_id'=>$vam_id,
										'departure'=>$departure,
										'arrival'=>$arrival
									]);

		if (count($route) == 0) {
			$charter = 1;
		} else {
			$charter = 0;
			$route_id = $route[0]->route;
			$flight = $route[0]->flight;
			$reservation = app('db')->select("select * from reserves where gvauser_id=:vam_id",['vam_id'=>$vam_id]);
			$pax = $reservation[0]->pax;
			$cargo = $reservation[0]->cargo;
		}
		
		$pirep = app('db')->insert("insert into pireps (from_airport,to_airport,comment,duration,plane_type,
									fuel,gvauser_id,charter,distance,date,route,flight,pax,cargo) 
									values (
										:departure,
										:arrival,
										:comments,
										:time,
										:aircraft,
										:fuel,
										:vam_id,
										:charter,
										:distance,
										STR_TO_DATE(:date,'%d-%m-%Y'),
										:route_id,
										:flight,
										:pax,
										:cargo
									)",
									[
										"departure"=>$departure,
										"arrival"=>$arrival,
										"comments"=>$comments,
										"time"=>$time,
										"aircraft"=>$aircraft,
										"fuel"=>$fuel,
										"vam_id"=>$vam_id,
										"charter"=>$charter,
										"distance"=>$distance,
										"date"=>$date,
										"route_id"=>$route_id,
										"flight"=>$flight,
										"pax"=>$pax,
										"cargo"=>$cargo
									]);

//		return response()->json([$route]);

		if ($charter == 0) {

			// Update pilot's time and location
			$pilot = app('db')->update("UPDATE gvausers SET route_id=0 ,location=:arrival,
										gva_hours=gva_hours + :time where gvauser_id=:vam_id",
										[
											"arrival"=>$arrival,
											"time"=>$time,
											"vam_id"=>$vam_id
										]);

			// Get plane status
			$plane = app('db')->select("select * from  fleets  where fleet_id = (select fleet_id from  
										reserves where gvauser_id=:vam_id)",
										[
											"vam_id"=>$vam_id
										]);
			$plane_status = $plane[0]->status - $this->flight_wear;
			$fleet_id = $plane[0]->fleet_id;
			$registry = $plane[0]->registry;
			$booked = 0;

			if ($plane_status <= $this->plane_status_hangar) {
				$hangar = app('db')->insert("insert into hangar (gvauser_id,fleet_id,registry,departure,location,
											date_in,date_out,reason) values (:vam_id,:fleet_id,:registry,:departure,
											:arrival,now(),ADDDATE(CURDATE(),:hangar_maintenance_days) ,'In Maintenance')",
											[
												"vam_id"=>$vam_id,
												"fleet_id"=>$fleet_id,
												"registry"=>$registry,
												"departure"=>$departure,
												"arrival"=>$arrival,
												"hangar_maintenance_days"=>$hangar_maintenance_days
											]);
				$booked = 1;
			}
			
			// Update the plane
			$plane_updated = app('db')->update("UPDATE fleets SET gvauser_id=NULL, hangardate=now(),
												status=status-:flight_wear, booked=:booked,location=:arrival,
												hours=hours + :time where fleet_id=(select fleet_id from reserves 
												where gvauser_id=:vam_id)",
												[
													"flight_wear"=>$this->flight_wear,
													"booked"=>$booked,
													"arrival"=>$arrival,
													"time"=>$time,
													"vam_id"=>$vam_id
												]);

			// Remove the reservation
			$reservation_deleted = app('db')->delete("delete from reserves where gvauser_id=:vam_id",["vam_id"=>$vam_id]);

			// Store the flight path for tracking
			$flight_path = app('db')->insert("insert into regular_flights_tracks 
											(gvauser_id,date,departure,arrival,route_id, fuel,distance,fleet_id) 
											values (:vam_id,now(),:departure,:arrival,:route_id,:fuel,
											:distance,:fleet_id)",
											[
												"vam_id"=>$vam_id,
												"departure"=>$departure,
												"arrival"=>$arrival,
												"route_id"=>$route_id,
												"fuel"=>$fuel,
												"distance"=>$distance,
												"fleet_id"=>$fleet_id
											]);

		} else {

			// Update for charter
			$charter_update = app('db')->update("UPDATE gvausers SET location=:arrival,gva_hours=gva_hours + :time where gvauser_id=:vam_id",
												[
													"arrival"=>$arrival,
													"time"=>$time,
													"vam_id"=>$vam_id
												]);

		}

		// Process the PIREP
		$pirep_list = app('db')->select("select * from pireps where gvauser_id=:vam_id and from_airport=:departure and 
											to_airport=:arrival order by pirep_id desc limit 1",
										[
											"vam_id"=>$vam_id,
											"departure"=>$departure,
											"arrival"=>$arrival
										]);
		

		/* We assume manual approval of PIREP - auto_acept_pirep.php in VAM/vam is the key*/		
		/*$flight = $pirep_list[0]->flightid;
		$type = 'pirep';
		$pilot = $vam_id;
		$charter = '';*/
									
		$reply = "PIREP filed: $date ($departure-$arrival)";
		return response()->json([$reply]);

	}

	/* Cancel a flight for the specified pilot using a specified aircraft */
	public function cancel_flight($pilot,$flight,$aircraft) {

		$vam_id = get_pilot_callsign($pilot);

		$route = app('db')->select("select route_id from routes where flight=:flight",['flight'=>$flight]);
		$route_id = $route[0]->route_id;

		$plane = app('db')->select("select fleet_id from fleets where registry=:aircraft",['aircraft'=>$aircraft]);
		$plane_id = $plane[0]->fleet_id;

		$reserves = app('db')->select("select * from reserves where gvauser_id=:vam_id and route_id=:route_id and fleet_id=:plane_id",['vam_id'=>$vam_id, 'route_id'=>$route_id, 'plane_id'=>$plane_id]);

		if (count($reserves) == 0) { return response()->json(["Flight cancellation failed. You have no flight booked."]); }

		$route_booked = app('db')->select("select route_id from gvausers where route_id=:route_id", ['route_id'=>$route_id]);

		if (count($route_booked) == 0) { return response()->json(["Flight cancellation failed. You have no flight booked."]); }

		$plane_booked = app('db')->select("select fleet_id from fleets where booked=1 and gvauser_id=:vam_id and fleet_id=:plane_id",['vam_id'=>$vam_id, 'plane_id'=>$plane_id]);

		if (count($plane_booked) == 0) { return response()->json(["Flight cancellation failed. Specified aircraft is not booked."]); }

		$delete_reserves = app('db')->delete("delete from reserves 
							where route_id=:route_id and 
							fleet_id=:plane_id and gvauser_id=:vam_id", 
							[
								"route_id"=>$route_id, 
								"plane_id"=>$plane_id,  
								"vam_id"=>$vam_id
							]);

		$unbook_fleet = app('db')->update("update fleets set booked=0 
							where fleet_id=:plane_id and 
							gvauser_id=:vam_id and booked=1", 
							[
								"plane_id"=>$plane_id, 
								"vam_id"=>$vam_id
							]);

		$unbook_user = app('db')->update("update gvausers set route_id=0 
							where gvauser_id=:vam_id", 
							[
								"vam_id"=>$vam_id
							]);

		$reply = "Flight cancelled: $flight $aircraft";
		return response()->json([$reply]);

	}

}
