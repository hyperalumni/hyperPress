<?php
namespace HyperPress\Program;

use HyperPress\Utils\AdminMenu;

defined( 'ABSPATH' ) || exit;

/**
 * The Program settings page under the hyperPress menu.
 */
final class SettingsPage {

	public const ID = 'hyperpress_program';

	public static function register(): void {
		add_filter( 'mb_settings_pages', array( self::class, 'pages' ) );
	}

	/**
	 * Adds the Program page.
	 *
	 * @param array<int,array<string,mixed>> $pages Settings pages.
	 * @return array<int,array<string,mixed>>
	 */
	public static function pages( array $pages ): array {
		$pages[] = array(
			'id'          => self::ID,
			'option_name' => Settings::OPTION,
			'menu_title'  => __( 'Program', 'hyperpress' ),
			'page_title'  => __( 'Program', 'hyperpress' ),
			'parent'      => AdminMenu::SLUG,
			'capability'  => 'manage_options',
			'style'       => 'no-boxes',
		);
		return $pages;
	}
}
