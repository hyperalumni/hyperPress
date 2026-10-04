<?php
namespace HyperPress\Countdown;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Color_Control;

final class Customizer {

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize_countdown_colors' ) );
	}

	public static function customize_countdown_colors( $wp_customize ): void {
		// COUNTDOWN COLORS
		$wp_customize->add_setting( 'hyperpress_countdown_days_color',
			array(
				'type'              => 'theme_mod',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hyperpress_countdown_days_color',
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
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hyperpress_countdown_hours_color',
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
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hyperpress_countdown_minutes_color',
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
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hyperpress_countdown_seconds_color',
			array(
				'label'    => __( 'Countdown Seconds Color', 'hyperpress' ),
				'settings' => 'hyperpress_countdown_seconds_color',
				'section'  => 'colors',
			) ) );
	}
}
