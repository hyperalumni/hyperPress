<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Control;

final class Customizer {

	public const SUBTITLE_SETTING = 'hyperpress_competition_banner_subtitle';

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize_competition' ) );
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
