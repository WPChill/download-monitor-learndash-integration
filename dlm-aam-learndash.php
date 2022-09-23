<?php
/*
	Plugin Name: Download Monitor & LearnDash integration
	Plugin URI: https://www.download-monitor.com/extensions/dlm-learndash-integration/
	Description: The Advanced Access Manager - Learndash extension allows you to limit downloads to Learndash user groups.
	Version: 1.0.1
	Author: WPChill
	Author URI: https://wpchill.com
	License: GPL v3
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function _dlm_aam_learndash() {

	// Define
	define( 'DLM_AAM_LD_FILE', __FILE__ );
	define( 'DLM_AAM_LD_PATH', plugin_dir_path( __FILE__ ) );
	define( 'DLM_AAM_LD_URL', plugin_dir_url( __FILE__ ) );

	// include files
	require_once dirname( __FILE__ ) . '/classes/class-dlm-aam-learndash.php';

	// Instantiate main plugin object
	DLM_AMM_Learndash::get_instance();
}

// init extension
add_action( 'plugins_loaded', '_dlm_aam_learndash', 100 );
