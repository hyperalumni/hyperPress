<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Competition
 * Plugin URI:          https://hyperalumni.org
 * Description:         Custom Post Type and Taxonomy for Competitions for hyperPress
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
 * Requires Plugins:    hyperpress-utils, hyperpress-season, hyperpress-robot, meta-box-aio
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'HyperPress\\Competition\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
);

// Boot after every plugin's main file is loaded, so Utils is always available.
add_action( 'plugins_loaded', array( \HyperPress\Competition\Plugin::class, 'boot' ) );
register_activation_hook( __FILE__, array( \HyperPress\Competition\Plugin::class, 'activate' ) );
