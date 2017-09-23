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

	app/Http/Controllers/vamapi.php
	
	Initialiases key virtual airline parameter data used by all controllers

*/

	//  Get va parameters
	$params = app('db')->select("select * from va_parameters");
	$this->ivao = $params[0]->ivao;
	$this->vatsim = $params[0]->vatsim;
	$this->plane_status_hangar = $params[0]->plane_status_hangar;
	$this->landing_crash = $params[0]->landing_crash;
	$this->landing_penalty1 = $params[0]->landing_penalty1;
	$this->landing_penalty2 = $params[0]->landing_penalty2;
	$this->landing_penalty3 = $params[0]->landing_penalty3;
	$this->landing_vs_penalty1 = $params[0]->landing_vs_penalty1;
	$this->landing_vs_penalty2 = $params[0]->landing_vs_penalty2;
	$this->flight_wear = $params[0]->flight_wear;
	$this->hanger_maintenance_days = $params[0]->hangar_maintenance_days;		
	$this->hanger_crash_days = $params[0]->hangar_crash_days;
	$this->pilot_crash_penalty = $params[0]->pilot_crash_penalty;
	$this->pilot_public = $params[0]->pilot_public;
	$this->va_date_format = $params[0]->date_format;
	$this->va_time_format = $params[0]->time_format;
	$this->auto_approval = $params[0]->auto_approval;
	
	// Get pilot callsign based on .env VAMAPI_USER_MAP attribute
	function get_pilot_callsign($userid) {
	
		if (env('VAMAPI_USER_MAP',false) == "true") {

			$user = app('db')->select("select vam_id from vamapi_user_map where external_id=:pilot",['pilot'=>$userid]);
			return $user[0]->vam_id;
		
		} else {
		
			return $userid;
			
		}
	
	}

?>

