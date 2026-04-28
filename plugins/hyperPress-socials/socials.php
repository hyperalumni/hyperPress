<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Socials
 * Description:         Customize options and widget for Socials for hyperPress
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

require_once( __DIR__ . '/lib/load.php' );
require_once( __DIR__ . '/lib/customize.php' );