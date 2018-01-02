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

	routes/web.php
	
	Specifies all routes for the API
	
*/


$router->get('/', function () use ($router) {
    return $router->version();
});

// Create new VAM user
$router->get(
	'/pilots/create/{admin_id}/{firstname}/{lastname}',
	'PilotController@create_vam_user'
);

// Register new discord user with VAM and map to VAM pilot user
// **TODO** Should only be available authorised admin users -- not regular pilots
$router->get(
	'/pilots/register/{admin_id}/{external_id}/{external_username}/{vam_callsign}', 
	'PilotController@register_pilot'
);

// Validate pilot auth credentials
$router->get(
	'/pilots/auth/{callsign}/{cred}', 
	'PilotController@auth_pilot'
);

// Provide list of VA parameters
$router->get(
	'/airline/params',
	'AirlineController@airline_parameters'
);

// Provide list of newwest pilots to join airline
$router->get(
	'/pilots/new',
	'PilotController@new_pilots'
);

// Provide list of all completed flights for a specified pilot
$router->get(
	'/pilots/profile/{pilot}',
	'PilotController@profile'
);

// Change a field in pilot's profile
$router->get(
	'/pilots/change/{pilot}/{field}/{value}',
	'PilotController@change_profile'
);

// Provide list of all completed flights for a specified pilot
$router->get(
	'/pilots/flights/{pilot}',
	'PilotController@completed_flights'
);

// Provide list of all booked flights for a specified pilot
$router->get(
	'/pilots/booked/{pilot}',
	'PilotController@booked_flights'
);

// Provide list of all scheduled routes
$router->get(
	'/routes',
	'RouteController@all_routes'
);

// Provide list of all planes in fleet
$router->get(
	'/ranks',
	'RankController@all_ranks'
);

// Provide list of all planes in fleet
$router->get(
	'/fleet',
	'FleetController@all_fleet'
);

// Provide list of all hubs
$router->get(
	'/hubs',
	'HubController@all_hubs'
);

// Provide list of pilots based at a hub
$router->get(
	'/hub/details/{icao}',
	'HubController@hub_details'
);

// Provide list of pilots based at a hub
$router->get(
	'/hub/pilots/{icao}',
	'HubController@hub_pilots'
);

// Provide list of aircraft based at a hub
$router->get(
	'/hub/fleet/{icao}',
	'HubController@hub_fleet'
);

// Provide list of routs to/from a hub
$router->get(
	'/hub/routes/{icao}',
	'HubController@hub_routes'
);

// Provide list of latest flights flown in the airline
$router->get(
	'/flights/latest',
	'FlightController@latest_flights'
);

// Provide list of flights available to book by specified pilot
$router->get(
	'/flights/book/{pilot}',
	'FlightController@available_flights'
);

// Provide list of aircraft available to book by specified pilot for a specified flight
$router->get(
	'/flights/book/{pilot}/{flight}',
	'FlightController@available_aircraft'
);

// Book flight for specified pilot using specified aircaft
$router->get(
	'/flights/book/{pilot}/{flight}/{aircraft}',
	'FlightController@book_flight'
);

// Book flight for specified pilot using specified aircaft
$router->get(
	'/flights/pirep/{pilot}/{date}/{aircraft}/{departure}/{arrival}/{distance}/{time}/{fuel}/{comments}',
	'FlightController@pirep_flight'
);

// Cancel flight for specified pilot using specified aircaft
$router->get(
	'/flights/cancel/{pilot}/{flight}/{aircraft}',
	'FlightController@cancel_flight'
);

// Provide a list of active NOTAMs
$router->get(
	'/notams',
	'NotamController@active_notams'
);

// Provide a list of active NOTAMs
$router->get(
	'/notam/{notam}',
	'NotamController@view_notam'
);

