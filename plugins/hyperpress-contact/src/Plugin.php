<?php
namespace HyperPress\Contact;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function boot(): void {
		SettingsPage::register();
		MetaBoxes::register();
	}
}
