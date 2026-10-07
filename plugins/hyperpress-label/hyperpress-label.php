<?php
/**
 * The hyperPress Label plugin.
 *
 * @wordpress-plugin
 * Plugin Name:         hyperPress Label
 * Plugin URI:          https://hyperalumni.org
 * Description:         Configurable labels for post headers, per post type
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
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'HyperPress\\Label\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
);

// Boot after every plugin's main file is loaded.
add_action( 'plugins_loaded', array( \HyperPress\Label\Plugin::class, 'boot' ) );
