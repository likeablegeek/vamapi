#!/bin/sh

#	VAMAPI 0.1-2.6.2 (https://github.com/likeablegeek/vamapi)
#	PHP REST API for VAM 2.6.2 (http://virtualairlinesmanager.net/)
#
#	By: Arman Danesh
#	
#	Based on Lumen 5.5
#	
#	License:
#
#	Copyright (c) 2017 Arman Danesh
#
#	Permission is hereby granted, free of charge, to any person obtaining a copy
#	of this software and associated documentation files (the "Software"), to deal
#	in the Software without restriction, including without limitation the rights
#	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
#	copies of the Software, and to permit persons to whom the Software is
#	furnished to do so, subject to the following conditions:
#
#	The above copyright notice and this permission notice shall be included in all
#	copies or substantial portions of the Software.
#
#	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
#	SOFTWARE.

APPNAME=$1
BASEDIR=$(dirname "$0")

echo "VAMAPI: Creating new lumen app - $APPNAME"
lumen new $APPNAME

echo "VAMAPI: cp $BASEDIR/source/controllers/* $APPNAME/app/Http/Controllers/"
cp $BASEDIR/source/controllers/* $APPNAME/app/Http/Controllers/

echo "VAMAPI: cp $BASEDIR/source/middleware/* $APPNAME/app/Http/Middleware/"
cp $BASEDIR/source/middleware/* $APPNAME/app/Http/Middleware/

echo "cp $BASEDIR/source/providers/* $APPNAME/app/Providers/"
cp $BASEDIR/source/providers/* $APPNAME/app/Providers/

echo "cp $BASEDIR/source/routes/* $APPNAME/routes/"
cp $BASEDIR/source/routes/* $APPNAME/routes/

echo "cp $BASEDIR/source/bootstrap/* $APPNAME/bootstrap/"
cp $BASEDIR/source/bootstrap/* $APPNAME/bootstrap/

echo "cp $BASEDIR/source/composer.json $APPNAME/"
cp $BASEDIR/source/composer.json $APPNAME/

echo "cp $BASEDIR/source/.env.example $APPNAME/.env"
cp $BASEDIR/source/.env.example $APPNAME/.env

echo "Change to directory for lumen app - $APPNAME"
cd $APPNAME

echo "VAMAPI: composer update --no-scripts"
composer update --no-scripts
