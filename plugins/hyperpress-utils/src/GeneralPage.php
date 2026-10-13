<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The General settings page under the hyperPress menu.
 *
 * Holds site-wide settings shared by several plugins, starting with the Google Maps key.
 */
final class GeneralPage {

	public const ID        = 'hyperpress_general';
	public const OPTION    = 'hyperpress_general';
	public const FIELD_KEY = 'hyperpress_general_google_maps_api_key';

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
					'id'                => self::FIELD_KEY,
					'name'              => __( 'Google Maps API key', 'hyperpress' ),
					'type'              => 'text',
					'desc'              => __( 'Restrict the key by HTTP referrer (domain) in Google Cloud, and enable the Maps JavaScript API and the Geocoding API for it.', 'hyperpress' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		);
		return $boxes;
	}
}
