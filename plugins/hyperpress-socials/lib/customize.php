<?php

namespace HYPER_Press_Socials;

use WP_Customize_Control;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Socials\hyperpress_customize_socials' ) ) :
	function hyperpress_customize_socials( $wp_customize ): void {
		$wp_customize->add_section( 'socials', array(
			'title'    => 'Socials',
			'priority' => 105, // Before Widgets.
		) );

		$wp_customize->add_setting( 'hyperpress_socials_firstinspires',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_firstinspires',
			array(
				'label'       => __( 'FIRST Inspires URL', 'hyperpress' ),
				'settings'    => 'hyperpress_socials_firstinspires',
				'section'     => 'socials',
				'type'        => 'url',
				'input_attrs' => array()
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_thebluealliance',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_thebluealliance',
			array(
				'label'    => __( 'TheBlueAlliance URL', 'hyperpress' ),
				'settings' => 'hyperpress_socials_thebluealliance',
				'section'  => 'socials',
				'type'     => 'url'
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_facebook',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_facebook',
			array(
				'label'    => __( 'Facebook URL', 'hyperpress' ),
				'settings' => 'hyperpress_socials_facebook',
				'section'  => 'socials',
				'type'     => 'url',
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_instagram',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_instagram',
			array(
				'label'    => __( 'Instagram URL', 'hyperpress' ),
				'settings' => 'hyperpress_socials_instagram',
				'section'  => 'socials',
				'type'     => 'url',
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_youtube',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_youtube',
			array(
				'label'    => __( 'YouTube URL', 'hyperpress' ),
				'settings' => 'hyperpress_socials_youtube',
				'section'  => 'socials',
				'type'     => 'url',
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_twitter',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_twitter',
			array(
				'label'    => __( 'Twitter URL', 'hyperpress' ),
				'settings' => 'hyperpress_socials_twitter',
				'section'  => 'socials',
				'type'     => 'url',
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_github',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_github',
			array(
				'label'    => __( 'GitHub URL', 'hyperpress' ),
				'settings' => 'hyperpress_socials_github',
				'section'  => 'socials',
				'type'     => 'url',
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_snapchat',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_snapchat',
			array(
				'label'    => __( 'Snapchat URL', 'hyperpress' ),
				'settings' => 'hyperpress_socials_snapchat',
				'section'  => 'socials',
				'type'     => 'url',
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_tiktok',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_tiktok',
			array(
				'label'    => __( 'TikTok URL', 'hyperpress' ),
				'settings' => 'hyperpress_socials_tiktok',
				'section'  => 'socials',
				'type'     => 'url',
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_discord',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_discord',
			array(
				'label'    => __( 'Discord URL', 'hyperpress' ),
				'settings' => 'hyperpress_socials_discord',
				'section'  => 'socials',
				'type'     => 'url',
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_contact',
			array(
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_contact',
			array(
				'label'    => __( 'Contact Page', 'hyperpress' ),
				'type'     => 'dropdown-pages',
				'settings' => 'hyperpress_socials_contact',
				'section'  => 'socials',
			) ) );

		$wp_customize->add_setting( 'hyperpress_socials_add_calendar',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_text_field',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'socials_add_calendar',
			array(
				'label'    => __( 'Add Calendar URL', 'hyperpress' ),
				'settings' => 'hyperpress_socials_add_calendar',
				'section'  => 'socials',
				'type'     => 'url',
			) ) );
	}

	add_action( 'customize_register', '\HYPER_Press_Socials\hyperpress_customize_socials' );
endif;
