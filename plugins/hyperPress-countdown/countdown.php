<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Countdown
 * Description:         Custom Post Type and Shortcode for Countdowns for hyperPress
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
require_once( __DIR__ . '/lib/customize.php' );
require_once( __DIR__ . '/lib/enqueue-scripts.php' );
require_once( __DIR__ . '/lib/meta-boxes.php' );
require_once( __DIR__ . '/lib/shortcodes.php' );