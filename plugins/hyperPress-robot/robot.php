<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Robot
 * Description:         Custom Post Type for Newsletters for hyperPress
 * Version:             1.0.0
 * Requires at least:   5.2
 * Requires PHP:        7.2
 * Author:              Chris McGroarty
 * Author URI:          https://idesign.co
 * Text Domain:         hyperpress
 * Requires Plugins: meta-box-aio
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// require the definitions first
require_once( __DIR__ . '/lib/custom-post-type.php' );
// then require the ancillary
require_once( __DIR__ . '/lib/breadcrumb.php' );