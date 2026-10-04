<?php

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

use HyperPress\Utils\Controls\CategoryDropdownControl;
use HyperPress\Utils\Controls\PostTypeDropdownControl;

add_action( 'customize_register', 'hyperpress_customize_homepage' );

function hyperpress_customize_homepage( $wp_customize ): void {
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'home_banner_button_text',
		array(
			'label'    => __( 'Homepage Banner Button Text', 'hyperpress' ),
			'settings' => 'hyperpress_home_banner_button_text',
			'section'  => 'hyperpress_homepage',
		) ) );


	$wp_customize->add_setting( 'hyperpress_home_blog_categories', array(
		'type'              => 'theme_mod',
		'sanitize_callback' => '',
	) );
	$wp_customize->add_control( new CategoryDropdownControl( $wp_customize, 'hyperpress_home_blog_categories', array(
		'section'     => 'hyperpress_homepage',
		'label'       => __( 'Homepage Blog Category', 'hyperpress' ),
		'description' => __( 'Select the category that the homepage will show posts from', 'hyperpress' ),
		'settings'    => 'hyperpress_home_blog_categories',
	) ) );

	$wp_customize->add_setting( 'hyperpress_home_blog_post_types', array(
		'type'              => 'theme_mod',
		'default'           => array( 'post' ),
		'sanitize_callback' => '',
	) );
	$wp_customize->add_control( new PostTypeDropdownControl( $wp_customize, 'hyperpress_home_blog_post_types', array(
		'section'     => 'hyperpress_homepage',
		'label'       => __( 'Homepage Blog Post Types', 'hyperpress' ),
		'description' => __( 'Select the Post Types that the homepage will display', 'hyperpress' ),
		'settings'    => 'hyperpress_home_blog_post_types',
	) ) );
}
