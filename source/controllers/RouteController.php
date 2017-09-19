<?php

/*

	VAMAPI 0.1-2.6.2 (https://github.com/likeablegeek/vamapi)
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

	app/Http/Controllers/RouteController.php
	
	This controller provides methods for accessing data about a virtual airline's
	routes:
	
	all_routes(): returns a list of all the airline's routes

*/

namespace App\Http\Controllers;

class RouteController extends Controller
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

	/* Return a list of all routes */
	public function all_routes() {

		$routes = app('db')->select("select flight, a1.name as dep_name, a2.name as arr_name, a1.iso_country as dep_country,a2.iso_country as arr_country,route_id,departure,arrival, duration from routes r, airports a1 , airports a2 where departure=a1.ident and arrival=a2.ident order by flight");

		foreach ($routes as $route) {

			$icaos = app('db')->select("select ft.plane_icao from fleettypes_routes fr, routes r, fleettypes ft where r.route_id=:routeid and r.route_id=fr.route_id and fr.fleettype_id=ft.fleettype_id",['routeid'=>$route->route_id]);

			$icaolist = "";

			foreach ($icaos as $icao) {

				$icaolist = $icaolist . " " . $icao->plane_icao;

			}

			$route->planes_icaos = $icaolist;

		}

		return response()->json($routes);

	}

}
