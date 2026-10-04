<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Label
 * Plugin URI:          https://hyperalumni.org
 * GitHub Plugin URI:   https://github.com/hyperalumni/hyperPress
 * Description:         Labels for Posts and Custom Post Types
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
 * Requires Plugins:    hyperpress-utils, meta-box-aio
 */

namespace HYPER_Press_Label;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}