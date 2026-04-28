<?php
if ( ! function_exists( 'hyperpress_customize_gear_colors' ) ) :
	function hyperpress_customize_gear_colors( $wp_customize ): void {
		// HYPER Gear colors
		$wp_customize->add_setting( 'hyperpress_gear_blue',
			array(
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'gear_blue',
			array(
				'label'    => __( 'HYPER Gear Blue', 'hyperpress' ),
				'settings' => 'hyperpress_gear_blue',
				'section'  => 'colors',
			) ) );
		$wp_customize->add_setting( 'hyperpress_gear_orange',
			array(
				'default'           => '#F15622',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'gear_orange',
			array(
				'label'    => __( 'HYPER Gear Orange', 'hyperpress' ),
				'settings' => 'hyperpress_gear_orange',
				'section'  => 'colors',
			) ) );
		$wp_customize->add_setting( 'hyperpress_gear_grey',
			array(
				'default'           => '#222222',
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'gear_grey',
			array(
				'label'    => __( 'HYPER Gear Grey', 'hyperpress' ),
				'settings' => 'hyperpress_gear_grey',
				'section'  => 'colors',
			) ) );
	}

	add_action( 'customize_register', 'hyperpress_customize_gear_colors' );
endif;
