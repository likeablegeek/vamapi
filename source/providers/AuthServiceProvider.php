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

	app/Providers/AuthServiceProvider.php
	
	This provider provides the VAMAPI authentication logic based on an token passed
	in the HTTP Api-Token header and a list of allowed IP addresses.
	
*/

namespace App\Providers;

use App\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // VAMAPI: check auth token and valid IP

        $this->app['auth']->viaRequest('api', function ($request) {

			$header = $request->header('Api-Token');

			$ip_list = explode(',',env('AUTH_IP',false));
			$ip_found = in_array($_SERVER['REMOTE_ADDR'], $ip_list);

			if ($header && $header == env('AUTH_TOKEN',false) && $ip_found) {
				return new User();
			}

        });
    }
}
