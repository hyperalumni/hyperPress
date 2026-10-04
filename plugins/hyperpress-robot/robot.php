<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Robot
 * Plugin URI:          https://hyperalumni.org
 * GitHub Plugin URI:   https://github.com/hyperalumni/hyperPress
 * Description:         Custom Post Type for Newsletters for hyperPress
 * Version:             2026.0.0
 * Requires at least:   6.9
 * Requires PHP:        8.3
 * Author:              Chris McGroarty
 * Author URI:          https://idesign.co
 * License:             MIT License
 * License URI:         http://www.opensource.org/licenses/mit-license.php
 * Text Domain:         hyperpress
 * Requires Plugins:    hyperpress-season, meta-box-aio
 */

namespace HYPER_Press_Robot;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// require the definitions first
require_once( __DIR__ . '/lib/custom-post-type.php' );
// then require the ancillary
require_once( __DIR__ . '/lib/breadcrumbs.php' );