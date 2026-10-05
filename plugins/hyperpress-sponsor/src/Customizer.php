<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Code_Editor_Control;

final class Customizer {

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize_sponsor_banner' ) );
	}

	public static function customize_sponsor_banner( $wp_customize ): void {
		$wp_customize->add_section(
			'hyperpress_sponsor',
			array(
				'title'    => __( 'Sponsors', 'hyperpress' ),
				'priority' => 108,
			)
		);

		$wp_customize->add_setting(
			'hyperpress_sponsor_banner_subtitle',
			array(
				'default'           => 'Helping Make HYPER Happen',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'wp_kses_post',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Code_Editor_Control(
				$wp_customize,
				'hyperpress_sponsor_banner_subtitle',
				array(
					'label'    => __( 'Sponsor Banner Subtitle', 'hyperpress' ),
					'settings' => 'hyperpress_sponsor_banner_subtitle',
					'section'  => 'hyperpress_sponsor',
				)
			)
		);
	}
}
