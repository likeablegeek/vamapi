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

	app/Http/Controllers/PilotController.php
	
	This controller provides methods for accessing data about a virtual airline's
	pilots:
	
	profile(): returns the profile of a specified pilot
	completed_flights(): returns a list of flights completed by a specified pilot
	booked_flights(): returns a list of flights currently booked by a pilot
	new_pilots(): returns a list of the newest pilots to join the airline

*/

namespace App\Http\Controllers;

class PilotController extends Controller
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

	/* Register a pilot with the specified discord ID and related to the VAM callsign */
	public function register_pilot($admin_id,$external_id,$vam_callsign) {

		if (is_vam_admin($admin_id)) {
		
			$pilot = app('db')->select("select gvauser_id from gvausers gu where gu.callsign=:vam_callsign",['vam_callsign'=>$vam_callsign]);
			$vam_id = $pilot[0]->gvauser_id;

			$register = app('db')->insert('insert into vamapi_user_map (external_id,vam_id) values (:external_id,:vam_id)', ['external_id'=>$external_id,'vam_id'=>$vam_id]);

			return 0;
			
		} else {
		
			return 1;
			
		}
		

//		return 0;

	}

	/* Return a pilot's profile */
	public function profile($pilot) {

		$vam_id = get_pilot_callsign($pilot);

		$profile = app('db')->select("select *, 
						date_format(register_date,'%Y-%m-%d') as register_date 
						FROM gvausers where gvauser_id=:vam_id",
						[
							"vam_id"=>$vam_id
						]);

		$hub = app('db')->select("select hub from hubs where hub_id=:hub_id",
						["hub_id"=>$profile[0]->hub_id]);
		$profile[0]->hub = $hub[0]->hub;

		$rank = app('db')->select("select rank from ranks where rank_id=:rank_id",
						["rank_id"=>$profile[0]->rank_id]);
		$profile[0]->rank = $rank[0]->rank;

		// For debugging
		$profile[0]->ip = env('AUTH_IP',false);
		$profile[0]->va_date_format = $this->va_date_format;

		return response()->json($profile);

	}
	
	/* Return a list of flights flown by a pilot */
	public function completed_flights($pilot) {

		$vam_id = get_pilot_callsign($pilot);

		$flights = app('db')->select("select a1.iso_country as country_dep, 
						a2.iso_country as country_arr ,
						REPLACE(a1.name,'Airport','') as dep_name,
						REPLACE(a2.name,'Airport','') as arr_name,
						CreatedOn as date_int,pirepfsfk_id as id,'' as comment,
						validated as status,pirepfsfk_id as flight, 
						SUBSTRING(OriginAirport,1,4) departure, 
						SUBSTRING(DestinationAirport,1,4) arrival , 
						DATE_FORMAT(CreatedOn,'%Y-%m-%d') as date  , 
						DistanceFlight as distance, 
						FlightTime as duration, charter , 'keeper' as type , 
						flight as flight_regular
				          	from pirepfsfk , airports a1, airports a2 
						where a1.ident=SUBSTRING(OriginAirport,1,4) and 
						a2.ident=SUBSTRING(DestinationAirport,1,4) and 
						gvauser_id=:vam_id1
				          	UNION
						SELECT a1.iso_country as country_dep, 
						a2.iso_country as country_arr ,
						REPLACE(a1.name,'Airport','') as dep_name,
						REPLACE(a2.name,'Airport','') as arr_name,
						date as date_int,report_id as id,'' as comment , 
						validated as status, report_id as flight , 
						origin_id as departure, destination_id as arrival, 
						DATE_FORMAT(date,'%Y-%m-%d') as date, distance, 
						(HOUR(duration)*60 + minute(duration))/60 as duration, 
						charter, 'Fsacars' as type, flight as flight_regular
						from reports , airports a1, airports a2 where 
						a1.ident=origin_id and a2.ident=destination_id and  
						pilot_id=:vam_id2
						UNION
						select a1.iso_country as country_dep, a2.iso_country as 
						country_arr ,REPLACE(a1.name,'Airport','') as dep_name,
						REPLACE(a2.name,'Airport','') as arr_name,date as date_int,
						pirep_id as id,comment,valid as status,pirep_id as flight,
						from_airport departure, to_airport arrival , 
						DATE_FORMAT(date,'%Y-%m-%d') as date,distance,
						duration,charter, 'pirep' as type ,flight as flight_regular
						from pireps  , airports a1, airports a2 
						where a1.ident=from_airport and a2.ident=to_airport and  
						gvauser_id=:vam_id3
						UNION
						SELECT a1.iso_country as country_dep, 
						a2.iso_country as country_arr ,
						REPLACE(a1.name,'Airport','') as dep_name,
						REPLACE(a2.name,'Airport','') as arr_name,
						flight_date as date_int, flightid as id,'' as comment , 
						validated as status, flightid as flight, departure, 
						arrival , DATE_FORMAT(flight_date,'%Y-%m-%d') 
						as date, distance, flight_duration as duration, charter, 
						'VAMACARS' as type, flight as flight_regular
						from vampireps , airports a1, airports a2
						 where a1.ident=departure and a2.ident=arrival and  
						gvauser_id=:vam_id4
						order by date_int desc, id desc",
						[
							'vam_id1'=>$vam_id,
							'vam_id2'=>$vam_id,
							'vam_id3'=>$vam_id,
							'vam_id4'=>$vam_id
						]);

		return response()->json($flights);

	}

	/* Return a list of flights booked by a pilot */
	public function booked_flights($pilot) {

		$vam_id = get_pilot_callsign($pilot);

		$flights = app('db')->select("select a3.iso_country as alt_country, 
						a3.name as alt_name,a1.name as dep_name, 
						a2.name as arr_name,
						a1.iso_country as dep_country,
						a2.iso_country as arr_country,flight, departure, arrival, 
						alternative, registry, plane_icao,
						f.fleet_id, plane_icao
						from routes ro, reserves re, fleets f, fleettypes ft , 
						airports a1, airports a2, airports a3
						where a1.ident=ro.departure and a2.ident=ro.arrival and 
						a3.ident=ro.alternative and
						ft.fleettype_id=f.fleettype_id and f.fleet_id=re.fleet_id 
						and ro.route_id=re.route_id and 
						re.gvauser_id=:vam_id",
						[
							'vam_id'=>$vam_id
						]);
//						and ro.route_id=$route 

		return response()->json($flights);

	}
	
	/* Return a list of the newest pilots to join the airline */
	public function new_pilots() {

		$pilots = app('db')->select("select gvauser_id, callsign, concat(name,' ',surname) as pilot_name, 
										DATE_FORMAT(register_date,:va_date_format1) as register_date from 
										gvausers where activation=1 order by DATE_FORMAT(register_date,:va_date_format2) 
										desc limit 5",
										[
											"va_date_format1"=>$this->va_date_format,
											"va_date_format2"=>$this->va_date_format
										]);

		return response()->json($pilots);

	}
	
	/* Check user authentication based on callsign and MD5 hash of password */
	public function auth_pilot($callsign, $cred) {
	
		$auth = app('db')->select("select gvauser_id, callsign from gvausers
										where callsign=:callsign and password=:password",
										[
											"callsign"=>$callsign,
											"password"=>$cred
										]);
		
		return response()->json($auth);
	
	}
	

    //
}
