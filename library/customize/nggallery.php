<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_customize_nggallery' ) ) :
	function hyperpress_customize_nggallery( $wp_customize ): void {
		$wp_customize->add_setting( 'hyperpress_nggallery_photos_page',
			array(
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'nggallery_photos_page',
			array(
				'label'    => __( 'Page Displaying Photos', 'hyperpress' ),
				'description' => __( 'For breadcrumb generation', 'hyperpress' ),
				'settings' => 'hyperpress_nggallery_photos_page',
				'type'     => 'dropdown-pages',
				'section'  => 'hyperpress',
			) ) );
	}

	add_action( 'customize_register', 'hyperpress_customize_nggallery' );
endif;

