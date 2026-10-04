<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Student
 * Plugin URI:          https://hyperalumni.org
 * GitHub Plugin URI:   https://github.com/hyperalumni/hyperPress
 * Description:         Custom Post Type and Taxonomy for Students for hyperPress
 * Version:             2026.0.0
 * Requires at least:   6.9
 * Requires PHP:        8.3
 * Author URI:          https://idesign.co
 * License:             MIT License
 * License URI:         http://www.opensource.org/licenses/mit-license.php
 * Text Domain:         hyperpress
 * Domain Path:         /languages
 * Update URI:          false
 * Requires Plugins:    hyperpress-season, meta-box-aio
 */

namespace HYPER_Press_Student;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}