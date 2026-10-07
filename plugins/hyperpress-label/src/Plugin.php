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
		Customizer::register();
	}
}
