<?php
namespace HyperPress\Social;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Control;

final class Customizer {

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize_socials' ) );
	}

	public static function customize_socials( $wp_customize ): void {
		$wp_customize->add_section(
			'hyperpress_social',
			array(
				'title'    => 'Socials',
				'priority' => 105, // Before Widgets.
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_firstinspires',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_firstinspires',
				array(
					'label'       => __( 'FIRST Inspires URL', 'hyperpress' ),
					'settings'    => 'hyperpress_social_firstinspires',
					'section'     => 'hyperpress_social',
					'type'        => 'url',
					'input_attrs' => array(),
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_thebluealliance',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_thebluealliance',
				array(
					'label'    => __( 'TheBlueAlliance URL', 'hyperpress' ),
					'settings' => 'hyperpress_social_thebluealliance',
					'section'  => 'hyperpress_social',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_facebook',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_facebook',
				array(
					'label'    => __( 'Facebook URL', 'hyperpress' ),
					'settings' => 'hyperpress_social_facebook',
					'section'  => 'hyperpress_social',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_instagram',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_instagram',
				array(
					'label'    => __( 'Instagram URL', 'hyperpress' ),
					'settings' => 'hyperpress_social_instagram',
					'section'  => 'hyperpress_social',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_youtube',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_youtube',
				array(
					'label'    => __( 'YouTube URL', 'hyperpress' ),
					'settings' => 'hyperpress_social_youtube',
					'section'  => 'hyperpress_social',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_twitter',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_twitter',
				array(
					'label'    => __( 'Twitter URL', 'hyperpress' ),
					'settings' => 'hyperpress_social_twitter',
					'section'  => 'hyperpress_social',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_github',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_github',
				array(
					'label'    => __( 'GitHub URL', 'hyperpress' ),
					'settings' => 'hyperpress_social_github',
					'section'  => 'hyperpress_social',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_snapchat',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_snapchat',
				array(
					'label'    => __( 'Snapchat URL', 'hyperpress' ),
					'settings' => 'hyperpress_social_snapchat',
					'section'  => 'hyperpress_social',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_tiktok',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_tiktok',
				array(
					'label'    => __( 'TikTok URL', 'hyperpress' ),
					'settings' => 'hyperpress_social_tiktok',
					'section'  => 'hyperpress_social',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_discord',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_discord',
				array(
					'label'    => __( 'Discord URL', 'hyperpress' ),
					'settings' => 'hyperpress_social_discord',
					'section'  => 'hyperpress_social',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_contact',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'absint',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_contact',
				array(
					'label'    => __( 'Contact Page', 'hyperpress' ),
					'type'     => 'dropdown-pages',
					'settings' => 'hyperpress_social_contact',
					'section'  => 'hyperpress_social',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_social_add_calendar',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_social_add_calendar',
				array(
					'label'    => __( 'Add Calendar URL', 'hyperpress' ),
					'settings' => 'hyperpress_social_add_calendar',
					'section'  => 'hyperpress_social',
					'type'     => 'url',
				)
			)
		);
	}
}
