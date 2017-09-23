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

	app/Http/Controllers/HubController.php
	
	This controller provides methods for accessing data about the virtual airline's
	hubs:
	
	all_hubs(): returns a list of the airline's hubs
	hub_details(): returns a details of a specified hub
	hub_pilots(): returns a list of pilots based at a specified hub
	hub_fleet(): returns a list of aircraft based at a specified hub
	hub_routes(): returns a list of routes to/from a specified hub

*/

namespace App\Http\Controllers;

class HubController extends Controller
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

	/* Return a list of all airline hubs */
	public function all_hubs() {

		$hubs = app('db')->select("select * from hubs h inner join airports a on a.ident = h.hub");

		return response()->json($hubs);

	}

	/* Return details for a specified hub */
	public function hub_details($icao) {

		$hub = app('db')->select("select hub_id from hubs where hub=:icao",['icao'=>$icao]);
		$hub_id = $hub[0]->hub_id;

		$details = [];

		$data = app('db')->select("select count(*) num_pilots from gvausers where hub_id=:hub_id 
					and activation=1",
					['hub_id'=>$hub_id]);
		$details[0]['num_pilots'] = $data[0]->num_pilots;

		$data = app('db')->select("select count(*) num_aircraft from fleets where hub_id=:hub_id",
					['hub_id'=>$hub_id]);
		$details[0]['num_aircraft'] = $data[0]->num_aircraft;

		$data = app('db')->select("select count(*) num_routes from routes where hub_id=:hub_id",
					['hub_id'=>$hub_id]);
		$details[0]['num_routes'] = $data[0]->num_routes;

		$data = app('db')->select("select * from hubs h inner join airports a 
					on a.ident = h.hub where hub_id=:hub_id",
					['hub_id'=>$hub_id]);
		$details[0]['name'] = $data[0]->name;

		return response()->json($details);

	}

	/* Return a list of pilots based at the specified hub */
	public function hub_pilots($icao) {

		$hub = app('db')->select("select hub_id from hubs where hub=:icao",['icao'=>$icao]);
		$hub_id = $hub[0]->hub_id;

		$pilots = app('db')->select("select * from country_t c, gvausers gu, ranks r, hubs h, 
						(select 0 + sum(time) as gva_hours, pilot from 
						v_pilot_roster_rejected vv group by pilot) as v 
						where h.hub_id=:hub_id and gu.rank_id=r.rank_id and 
						h.hub_id=gu.hub_id and gu.activation<>0 and 
						gu.country=c.iso2 and v.pilot = gu.gvauser_id order 
						by callsign asc",
					['hub_id'=>$hub_id]);

		return response()->json($pilots);

	}

	/* Return a list of aircraft based at a specified hub */
	public function hub_fleet($icao) {

		$hub = app('db')->select("select hub_id from hubs where hub=:icao",['icao'=>$icao]);
		$hub_id = $hub[0]->hub_id;

		$fleet = app('db')->select("select registry, status, hours,plane_icao, location
						from fleets f
						inner join fleettypes ft on f.fleettype_id=ft.fleettype_id
						where hub_id=:hub_id",
						['hub_id'=>$hub_id]);

		return response()->json($fleet);

	}

	/* Return a list of routes to/from a specified hub */
	public function hub_routes($icao) {

		$hub = app('db')->select("select hub_id from hubs where hub=:icao",['icao'=>$icao]);
		$hub_id = $hub[0]->hub_id;

		$routes = app('db')->select("select * from routes where hub_id=:hub_id",
						['hub_id'=>$hub_id]);

		return response()->json($routes);

	}

}
