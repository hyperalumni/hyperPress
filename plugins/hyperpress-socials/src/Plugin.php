<?php
namespace HyperPress\Socials;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function boot(): void {
		Customizer::register();
		Widgets::register();
	}
}
