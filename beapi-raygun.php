<?php
/*
Plugin Name: BEAPI Raygun
Version: 1.0.0
Plugin URI: https://beapi.fr
Description: Straighforward Raygun implementation for WordPress.
Author: Be API Technical team
Author URI: https://beapi.fr
Domain Path: languages
Text Domain: bea-raygun
Requires PHP: 5.6
----

Copyright 2019 Be API Technical team (human@beapi.fr)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

use BEAPI\Raygun\Main;

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Plugin constants
define( 'BEAPI_RAYGUN_VERSION', '1.0.0' );

// Plugin URL and PATH
define( 'BEAPI_RAYGUN_URL', plugin_dir_url( __FILE__ ) );
define( 'BEAPI_RAYGUN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BEAPI_RAYGUN_PLUGIN_DIRNAME', basename( rtrim( dirname( __FILE__ ), '/' ) ) );

if ( file_exists( BEAPI_RAYGUN_DIR . '/vendor/autoload.php' ) ) {
	require BEAPI_RAYGUN_DIR . '/vendor/autoload.php';
}

if ( ! class_exists( '\BEAPI\Raygun\Main' ) ) {
	trigger_error( 'BEAPI Raygun not fully installed! Please install with Composer or download full release archive.', E_USER_ERROR );

	return;
}

// Bootstrap the plugin.
( new Main() )->register();
