<?php
/**
 * HyperPress UIkit theme bootstrap.
 *
 * @package hyperpress-uikit
 */

declare(strict_types=1);

use HyperPress\Uikit\Assets;

$hp_uikit_autoload = __DIR__ . '/vendor/autoload.php';

if ( is_file( $hp_uikit_autoload ) ) {
	require_once $hp_uikit_autoload;
} else {
	// Without vendor/ the admin notice from Assets still explains what is missing.
	require_once __DIR__ . '/inc/Assets.php';
}

if ( class_exists( 'Timber\Timber' ) ) {
	Timber\Timber::init();
	Timber\Timber::$dirname = array( 'views' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Timber's public static property, not a global.
}

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'html5', array( 'script', 'style' ) );
	}
);

( new Assets( get_template_directory(), get_template_directory_uri() ) )->register();
