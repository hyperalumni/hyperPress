<?php
namespace HyperPress\Shortcode;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
	}

	public static function register(): void {
		Shortcodes\TimeRestrict::register();
		Shortcodes\DateDistance::register();
		Shortcodes\Raw::register();
		Shortcodes\Emphasis::register();
		Shortcodes\Banner::register();
	}
}
