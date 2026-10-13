<?php
namespace HyperPress\Social;

use HyperPress\Utils\AdminMenu;

defined( 'ABSPATH' ) || exit;

/**
 * The Social settings page under the hyperPress menu.
 */
final class SettingsPage {

	public const ID = 'hyperpress_social';

	public static function register(): void {
		add_filter( 'mb_settings_pages', array( self::class, 'pages' ) );
	}

	/**
	 * Adds the Social page.
	 *
	 * @param array<int,array<string,mixed>> $pages Settings pages.
	 * @return array<int,array<string,mixed>>
	 */
	public static function pages( array $pages ): array {
		$pages[] = array(
			'id'          => self::ID,
			'option_name' => Settings::OPTION,
			'menu_title'  => __( 'Social', 'hyperpress' ),
			'page_title'  => __( 'Social', 'hyperpress' ),
			'parent'      => AdminMenu::SLUG,
			'capability'  => 'manage_options',
			'style'       => 'no-boxes',
		);
		return $pages;
	}
}
