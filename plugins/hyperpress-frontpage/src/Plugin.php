<?php
namespace HyperPress\Frontpage;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function boot(): void {
		add_filter( 'default_option_' . Settings::OPTION, array( Settings::class, 'default_option' ), 10, 3 );
		SettingsPage::register();
		MetaBoxes::register();
	}
}
