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

	app/Http/Controllers/RankController.php
	
	This controller provides methods for accessing data about a virtual airline's
	ranks:
	
	all_ranks(): returns a list of all airline ranks

*/

namespace App\Http\Controllers;

class RankController extends Controller
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

	/* Return a list of all ranks in the airline */
	public function all_ranks() {

		$ranks = app('db')->select("select * from ranks order by minimum_hours asc");

		return response()->json($ranks);

	}

}
