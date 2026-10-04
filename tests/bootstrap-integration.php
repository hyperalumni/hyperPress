<?php
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );
define( 'WP_PHPUNIT__TESTS_CONFIG', __DIR__ . '/wp-tests-config.php' );

$hyperpress_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';
require_once $hyperpress_tests_dir . '/includes/functions.php';

// Meta Box is not installed in tests. Tests set $GLOBALS['hyperpress_test_rwmb'][ $key ] to simulate meta.
if ( ! function_exists( 'rwmb_meta' ) ) {
	function rwmb_meta( $key, $args = array(), $post_id = null ) { // phpcs:ignore
		return $GLOBALS['hyperpress_test_rwmb'][ $key ] ?? '';
	}
}

// Pretty permalinks, so post type archives and rewrites resolve.
tests_add_filter( 'pre_option_permalink_structure', static fn() => '/%postname%/' );

// Register and activate the theme.
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		register_theme_directory( '/hp-themes' );
	}
);
tests_add_filter( 'pre_option_template', static fn() => 'hyperpress' );
tests_add_filter( 'pre_option_stylesheet', static fn() => 'hyperpress' );

// Load the plugins.
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		$first = array( 'hyperpress-utils', 'hyperpress-season', 'hyperpress-program', 'hyperpress-robot' );
		$skip  = getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ? array( 'hyperpress-shortcodes', 'hyperpress-media' ) : array();

		$all = array_map( 'basename', glob( '/plugins/hyperpress-*', GLOB_ONLYDIR ) );
		sort( $all );
		$ordered = array_merge( $first, array_diff( $all, $first ) );

		foreach ( $ordered as $plugin ) {
			$main = "/plugins/$plugin/$plugin.php";
			if ( in_array( $plugin, $skip, true ) || ! is_file( $main ) ) {
				continue;
			}
			require_once $main;
		}
	}
);

require $hyperpress_tests_dir . '/includes/bootstrap.php';
