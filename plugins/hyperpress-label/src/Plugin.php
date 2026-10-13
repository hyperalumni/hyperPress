<?php
/**
 * Plugin bootstrap.
 *
 * @package HyperPress\Label
 */

namespace HyperPress\Label;

use HyperPress\Label\Labels\PostLabels;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the label plugin.
 */
final class Plugin {

	/**
	 * Boot the plugin on `plugins_loaded`.
	 */
	public static function boot(): void {
		Renderer::register();
		PostLabels::register();
		SettingsPage::register();
		MetaBoxes::register();
		add_filter( 'pre_update_option_' . Settings::OPTION, array( Settings::class, 'normalize' ) );
	}
}
