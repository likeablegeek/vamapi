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

	app/Http/Controllers/NotamController.php
	
	This controller provides methods for accessing NOTAMs:
	
	active_notams(): returns a list active NOTAMs
	view_Notam(): returns the contents of a specified NOTAM

*/

namespace App\Http\Controllers;

class NotamController extends Controller
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

	/* Return a list of active Notams (i.e. where current date is between publish date and hide date */
	public function active_notams() {

		$notams = app('db')->select("select notam_id,notam_name,DATE_FORMAT(publish_date,'%Y-%m-%d') as publish_date_web
										from notams 
										where publish_date <= curdate() and 
										hide_date > curdate()
										order by publish_date desc");

		return response()->json($notams);

	}

	/* View a notam specified by a Notam ID */
	public function view_notam($notam) {

		$notam_data = app('db')->select("select notam_id,notam_name,notam_text,DATE_FORMAT(publish_date,'%Y-%m-%d') as publish_date_web
										from notams 
										where notam_ID=:notam",["notam"=>$notam]);

		return response()->json($notam_data);

	}

}
