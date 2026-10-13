<?php
/**
 * The Labels settings page under the hyperPress menu.
 *
 * @package HyperPress\Label
 */

namespace HyperPress\Label;

use HyperPress\Utils\AdminMenu;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Labels settings page with Meta Box.
 */
final class SettingsPage {

	public const ID = 'hyperpress_label';

	public static function register(): void {
		add_filter( 'mb_settings_pages', array( self::class, 'pages' ) );
	}

	/**
	 * Adds the Labels page.
	 *
	 * @param array<int,array<string,mixed>> $pages Settings pages.
	 * @return array<int,array<string,mixed>>
	 */
	public static function pages( array $pages ): array {
		$pages[] = array(
			'id'          => self::ID,
			'option_name' => Settings::OPTION,
			'menu_title'  => __( 'Labels', 'hyperpress' ),
			'page_title'  => __( 'Labels', 'hyperpress' ),
			'parent'      => AdminMenu::SLUG,
			'capability'  => 'manage_options',
			'style'       => 'no-boxes',
		);
		return $pages;
	}
}
