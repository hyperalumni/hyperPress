<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Control;

final class Customizer {

	public const API_KEY_SETTING  = 'hyperpress_competition_google_maps_api_key';
	public const SUBTITLE_SETTING = 'hyperpress_competition_banner_subtitle';

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize_competition' ) );
	}

	/**
	 * The Google Maps API key used by the venue map field. Empty until an administrator enters one.
	 */
	public static function google_maps_api_key(): string {
		return (string) get_theme_mod( self::API_KEY_SETTING, '' );
	}

	public static function customize_competition( $wp_customize ): void {
		$wp_customize->add_section(
			'hyperpress_competition',
			array(
				'title'    => __( 'Competitions', 'hyperpress' ),
				'priority' => 106,
			)
		);

		$wp_customize->add_setting(
			self::API_KEY_SETTING,
			array(
				'default'           => '',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				self::API_KEY_SETTING,
				array(
					'label'       => __( 'Google Maps API key', 'hyperpress' ),
					'description' => __( 'Used by the venue map on the competition edit screen. Restrict the key to your admin domain in the Google Cloud console.', 'hyperpress' ),
					'settings'    => self::API_KEY_SETTING,
					'section'     => 'hyperpress_competition',
					'type'        => 'text',
				)
			)
		);

		$wp_customize->add_setting(
			self::SUBTITLE_SETTING,
			array(
				'default'           => '',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'wp_kses_post',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				self::SUBTITLE_SETTING,
				array(
					'label'       => __( 'Banner subtitle', 'hyperpress' ),
					'description' => __( 'Shown under the title on the competitions pages.', 'hyperpress' ),
					'settings'    => self::SUBTITLE_SETTING,
					'section'     => 'hyperpress_competition',
					'type'        => 'text',
				)
			)
		);
	}
}
