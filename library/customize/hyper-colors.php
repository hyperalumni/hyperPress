<?php
if ( ! function_exists( 'hyperpress_customize_hyper_colors' ) ) :
	function hyperpress_customize_hyper_colors( $wp_customize ): void {
// HYPER Logo colors
		$wp_customize->add_setting( 'hyperpress_hyper_red',
			array(
				'default'           => '#C52026',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hyper_red',
			array(
				'label'    => __( 'HYPER Logo Red', 'hyperpress' ),
				'settings' => 'hyperpress_hyper_red',
				'section'  => 'colors',
			) ) );

		$wp_customize->add_setting( 'hyperpress_hyper_orange',
			array(
				'default'           => '#F15623',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hyper_orange',
			array(
				'label'    => __( 'HYPER Logo Orange', 'hyperpress' ),
				'settings' => 'hyperpress_hyper_orange',
				'section'  => 'colors',
			) ) );

		$wp_customize->add_setting( 'hyperpress_hyper_green',
			array(
				'default'           => '#049655',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hyper_green',
			array(
				'label'    => __( 'HYPER Logo Green', 'hyperpress' ),
				'settings' => 'hyperpress_hyper_green',
				'section'  => 'colors',
			) ) );

		$wp_customize->add_setting( 'hyperpress_hyper_blue',
			array(
				'default'           => '#0B67B2',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hyper_blue',
			array(
				'label'    => __( 'HYPER Logo Blue', 'hyperpress' ),
				'settings' => 'hyperpress_hyper_blue',
				'section'  => 'colors',
			) ) );

		$wp_customize->add_setting( 'hyperpress_hyper_purple',
			array(
				'default'           => '#9B3092',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hyper_purple',
			array(
				'label'    => __( 'HYPER Logo Purple', 'hyperpress' ),
				'settings' => 'hyperpress_hyper_purple',
				'section'  => 'colors',
			) ) );
	}

	add_action( 'customize_register', 'hyperpress_customize_hyper_colors' );
endif;
