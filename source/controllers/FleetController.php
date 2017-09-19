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

	app/Http/Controllers/FleetController.php
	
	This controller provides methods for accessing data about the virtual airline's
	fleet:
	
	all_fleet(): returns a list of all aircraft in the airline's fleet
	
*/

namespace App\Http\Controllers;

class FleetController extends Controller
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

	/* Return list of all aircraft in fleet */
	public function all_fleet() {

		$planes = app('db')->select("select f.fleet_id,hu.hub,f.registry as registry,f.status,
										ft.plane_icao, f.location
										from fleets f left outer join (select registry,status from hangar where status=1) ha
										on f.registry = ha.registry
										inner join  fleettypes ft on f.fleettype_id=ft.fleettype_id
										inner join hubs hu on hu.hub_id = f.hub_id
										left outer join gvausers gv on f.gvauser_id = gv.gvauser_id
										inner join airports a on a.ident=f.location");

		return response()->json($planes);

	}

}
