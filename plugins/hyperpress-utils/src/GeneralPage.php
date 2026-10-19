<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The General settings page under the hyperPress menu.
 *
 * Home for site-wide settings shared by several plugins. It has no settings yet and only explains the page.
 */
final class GeneralPage {

	public const ID     = 'hyperpress_general';
	public const OPTION = 'hyperpress_general';

	public static function register(): void {
		add_filter( 'mb_settings_pages', array( self::class, 'pages' ) );
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the General page.
	 *
	 * @param array<int,array<string,mixed>> $pages Settings pages.
	 * @return array<int,array<string,mixed>>
	 */
	public static function pages( array $pages ): array {
		$pages[] = array(
			'id'          => self::ID,
			'option_name' => self::OPTION,
			'menu_title'  => __( 'General', 'hyperpress' ),
			'page_title'  => __( 'General', 'hyperpress' ),
			'parent'      => AdminMenu::SLUG,
			'capability'  => 'manage_options',
			'style'       => 'no-boxes',
		);
		return $pages;
	}

	/**
	 * Adds the General fields.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'title'          => __( 'General', 'hyperpress' ),
			'id'             => 'hyperpress_general_fields',
			'settings_pages' => self::ID,
			'fields'         => array(
				array(
					'id'   => 'hyperpress_general_about',
					'type' => 'custom_html',
					'std'  => self::about(),
				),
			),
		);
		return $boxes;
	}

	/**
	 * The helper text shown on the page: what it is for and where related options live.
	 */
	private static function about(): string {
		return '<p>' . esc_html__( 'This page is the home for site-wide hyperPress options that are shared across the hyperPress plugins. There are currently no site-wide settings.', 'hyperpress' ) . '</p>'
			. '<p>' . esc_html__( 'Maps use OpenStreetMap, so no map API key is needed.', 'hyperpress' ) . '</p>'
			. '<p>' . esc_html__( 'Related options live elsewhere: the map location for the [hyperpress_map] shortcode is set on the Contact page of this menu, and each competition venue map is set on that competition\'s edit screen.', 'hyperpress' ) . '</p>';
	}
}
