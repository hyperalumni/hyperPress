<?php
namespace HyperPress\Social;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function boot(): void {
		Customizer::register();
		Widgets::register();
	}
}
