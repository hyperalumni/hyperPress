<?php
namespace HyperPress\Socials;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Control;

final class Customizer {

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize_socials' ) );
	}

	public static function customize_socials( $wp_customize ): void {
		$wp_customize->add_section(
			'hyperpress_socials',
			array(
				'title'    => 'Socials',
				'priority' => 105, // Before Widgets.
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_firstinspires',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_firstinspires',
				array(
					'label'       => __( 'FIRST Inspires URL', 'hyperpress' ),
					'settings'    => 'hyperpress_socials_firstinspires',
					'section'     => 'hyperpress_socials',
					'type'        => 'url',
					'input_attrs' => array(),
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_thebluealliance',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_thebluealliance',
				array(
					'label'    => __( 'TheBlueAlliance URL', 'hyperpress' ),
					'settings' => 'hyperpress_socials_thebluealliance',
					'section'  => 'hyperpress_socials',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_facebook',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_facebook',
				array(
					'label'    => __( 'Facebook URL', 'hyperpress' ),
					'settings' => 'hyperpress_socials_facebook',
					'section'  => 'hyperpress_socials',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_instagram',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_instagram',
				array(
					'label'    => __( 'Instagram URL', 'hyperpress' ),
					'settings' => 'hyperpress_socials_instagram',
					'section'  => 'hyperpress_socials',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_youtube',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_youtube',
				array(
					'label'    => __( 'YouTube URL', 'hyperpress' ),
					'settings' => 'hyperpress_socials_youtube',
					'section'  => 'hyperpress_socials',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_twitter',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_twitter',
				array(
					'label'    => __( 'Twitter URL', 'hyperpress' ),
					'settings' => 'hyperpress_socials_twitter',
					'section'  => 'hyperpress_socials',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_github',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_github',
				array(
					'label'    => __( 'GitHub URL', 'hyperpress' ),
					'settings' => 'hyperpress_socials_github',
					'section'  => 'hyperpress_socials',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_snapchat',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_snapchat',
				array(
					'label'    => __( 'Snapchat URL', 'hyperpress' ),
					'settings' => 'hyperpress_socials_snapchat',
					'section'  => 'hyperpress_socials',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_tiktok',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_tiktok',
				array(
					'label'    => __( 'TikTok URL', 'hyperpress' ),
					'settings' => 'hyperpress_socials_tiktok',
					'section'  => 'hyperpress_socials',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_discord',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_discord',
				array(
					'label'    => __( 'Discord URL', 'hyperpress' ),
					'settings' => 'hyperpress_socials_discord',
					'section'  => 'hyperpress_socials',
					'type'     => 'url',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_contact',
			array(
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_contact',
				array(
					'label'    => __( 'Contact Page', 'hyperpress' ),
					'type'     => 'dropdown-pages',
					'settings' => 'hyperpress_socials_contact',
					'section'  => 'hyperpress_socials',
				)
			)
		);

		$wp_customize->add_setting(
			'hyperpress_socials_add_calendar',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_socials_add_calendar',
				array(
					'label'    => __( 'Add Calendar URL', 'hyperpress' ),
					'settings' => 'hyperpress_socials_add_calendar',
					'section'  => 'hyperpress_socials',
					'type'     => 'url',
				)
			)
		);
	}
}
