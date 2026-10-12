<?php
namespace HyperPress\Contact;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function boot(): void {
		SettingsPage::register();
		Addresses::register();
		MetaBoxes::register();
		AddressShortcode::register();
		MapShortcode::register();
	}
}
