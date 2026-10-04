<?php
namespace HYPER_Press_Program;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

use HYPER_Press_Utils\HYPERpress_Dropdown_Post_Type_Control;

if ( ! function_exists( '\HYPER_Press_Program\hyperpress_customize_program' ) ) :
	add_action( 'customize_register', '\HYPER_Press_Program\hyperpress_customize_program' );

	function hyperpress_customize_program( $wp_customize ): void {
		if ( class_exists( '\HYPER_Press_Utils\HYPERpress_Dropdown_Post_Type_Control' ) ) {
			$wp_customize->add_setting( 'hyperpress_program_post_types', [
				'type'              => 'theme_mod',
				'default'           => [ 'post' ],
				'sanitize_callback' => '',
			] );
			$wp_customize->add_control( new HYPERpress_Dropdown_Post_Type_Control( $wp_customize, 'program_post_types', [
				'section'     => 'hyperpress_program',
				'label'       => __( 'Programs Post Types', 'hyperpress' ),
				'description' => __( 'Select the Post Types that will use the Program Taxonomy', 'hyperpress' ),
				'settings'    => 'hyperpress_program_post_types',
			] ) );
		}
	}
endif;