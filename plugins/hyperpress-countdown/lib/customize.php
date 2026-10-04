<?php

namespace HYPER_Press_Countdown;

use WP_Customize_Color_Control;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Countdown\hyperpress_customize_countdown_colors' ) ) :
	add_action( 'customize_register', '\HYPER_Press_Countdown\hyperpress_customize_countdown_colors' );

	function hyperpress_customize_countdown_colors( $wp_customize ): void {
		// COUNTDOWN COLORS
		$wp_customize->add_setting( 'hyperpress_countdown_days_color',
			array(
				'type'              => 'theme_mod',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'countdown_days_color',
			array(
				'label'    => __( 'Countdown Days Color', 'hyperpress' ),
				'settings' => 'hyperpress_countdown_days_color',
				'section'  => 'colors',
			) ) );

		$wp_customize->add_setting( 'hyperpress_countdown_hours_color',
			array(
				'type'              => 'theme_mod',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'countdown_hours_color',
			array(
				'label'    => __( 'Countdown Hours Color', 'hyperpress' ),
				'settings' => 'hyperpress_countdown_hours_color',
				'section'  => 'colors',
			) ) );

		$wp_customize->add_setting( 'hyperpress_countdown_minutes_color',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'countdown_minutes_color',
			array(
				'label'    => __( 'Countdown Minutes Color', 'hyperpress' ),
				'settings' => 'hyperpress_countdown_minutes_color',
				'section'  => 'colors',
			) ) );

		$wp_customize->add_setting( 'hyperpress_countdown_seconds_color',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'countdown_seconds_color',
			array(
				'label'    => __( 'Countdown Seconds Color', 'hyperpress' ),
				'settings' => 'hyperpress_countdown_seconds_color',
				'section'  => 'colors',
			) ) );
	}
endif;
