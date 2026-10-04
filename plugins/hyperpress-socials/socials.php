<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Socials
 * Plugin URI:          https://hyperalumni.org
 * GitHub Plugin URI:   https://github.com/hyperalumni/hyperPress
 * Description:         Customize options and widget for Socials for hyperPress
 * Version:             2026.0.0
 * Requires at least:   6.9
 * Requires PHP:        8.3
 * Author:              Chris McGroarty
 * Author URI:          https://idesign.co
 * License:             MIT License
 * License URI:         http://www.opensource.org/licenses/mit-license.php
 * Text Domain:         hyperpress
 * Domain Path:         /languages
 * Update URI:          false
 * Requires Plugins:    meta-box-aio
 */

namespace HYPER_Press_Socials;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

require_once( __DIR__ . '/lib/widgets/load.php' );
require_once( __DIR__ . '/lib/customize.php' );