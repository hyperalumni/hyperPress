<?php
namespace HyperPress\Media;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function boot(): void {
		NextGen\SettingsPage::register();
		NextGen\MetaBoxes::register();
		ImageEditor\AvifViaGd::register();

		// NextGen Gallery is an optional third-party plugin.
		if ( class_exists( 'Imagely\NGG\DataMappers\Album' ) ) {
			NextGen\Breadcrumbs::register();
		}
	}
}
