<?php
use HyperPress\Tests\Support\Convention;

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );
define( 'WP_PHPUNIT__TESTS_CONFIG', __DIR__ . '/wp-tests-config.php' );

$hyperpress_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';

require_once $hyperpress_tests_dir . '/includes/functions.php';

/**
 * Meta Box is not installed in tests. Stub the one function plugins call.
 * Tests inspect the `rwmb_meta_boxes` filter output directly.
 */
if ( ! function_exists( 'rwmb_meta' ) ) {
	function rwmb_meta( $key, $args = array(), $post_id = null ) { // phpcs:ignore
		// Tests set $GLOBALS['hyperpress_test_rwmb'][ $key ] to simulate stored meta.
		return $GLOBALS['hyperpress_test_rwmb'][ $key ] ?? '';
	}
}

/**
 * Find a plugin's main file: the root-level PHP file carrying a "Plugin Name:" header.
 * Works before and after the main file is renamed to <folder>.php.
 */
function hyperpress_tests_find_main_file( string $plugin ): string {
	$dir       = Convention::root() . '/' . $plugin;
	$canonical = $dir . '/' . $plugin . '.php';
	if ( is_file( $canonical ) ) {
		return $canonical;
	}
	foreach ( glob( $dir . '/*.php' ) as $file ) {
		if ( false !== strpos( (string) file_get_contents( $file, false, null, 0, 4096 ), 'Plugin Name:' ) ) {
			return $file;
		}
	}
	throw new RuntimeException( "No main file found for $plugin" );
}

/**
 * WordPress only computes `rewrite` args when a permalink structure exists (or in admin).
 * The test suite has none by default, so every post type would report rewrite === false.
 * Force pretty permalinks before init so URL contracts are meaningful.
 */
tests_add_filter(
	'pre_option_permalink_structure',
	static function (): string {
		return '/%postname%/';
	}
);

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		foreach ( Convention::LOAD_ORDER as $plugin ) {
			// hyperpress-shortcode and hyperpress-media are created later (plugins-04-remaining-plugins); skip folders that do not exist yet.
			if ( ! is_dir( Convention::root() . '/' . $plugin ) ) {
				continue;
			}
			require_once hyperpress_tests_find_main_file( $plugin );
		}
	}
);

require $hyperpress_tests_dir . '/includes/bootstrap.php';
