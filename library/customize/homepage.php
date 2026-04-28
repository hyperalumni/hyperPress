<?php
if ( ! function_exists( 'hyperpress_customize_homepage' ) ) :
	function hyperpress_customize_homepage( $wp_customize ): void {
		require_once 'controls/customize-category-control.php';
		require_once 'controls/customize-post-type-control.php';

		// HOMEPAGE BANNER
		$wp_customize->add_setting( 'hyperpress_home_banner_title',
			array(
				'default'   => 'HYPER',
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'home_banner_title',
			array(
				'label'    => __( 'Homepage Banner Title', 'hyperpress' ),
				'settings' => 'hyperpress_home_banner_title',
				'section'  => 'static_front_page',
			) ) );

		$wp_customize->add_setting( 'hyperpress_home_banner_subtitle',
			array(
				'default'   => 'Dedicated to improving &amp; expanding the abilities of <span class="emphasis">Team HYPER</span>',
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			) );
		$wp_customize->add_control( new WP_Customize_Code_Editor_Control( $wp_customize, 'home_banner_subtitle',
			array(
				'label'    => __( 'Homepage Banner Subtitle', 'hyperpress' ),
				'settings' => 'hyperpress_home_banner_subtitle',
				'section'  => 'static_front_page',
			) ) );

		$wp_customize->add_setting( 'hyperpress_home_banner_button_text',
			array(
				'default'   => 'Learn More',
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'home_banner_button_text',
			array(
				'label'    => __( 'Homepage Banner Button Text', 'hyperpress' ),
				'settings' => 'hyperpress_home_banner_button_text',
				'section'  => 'static_front_page',
			) ) );

		$wp_customize->add_setting( 'hyperpress_home_banner_button_link',
			array(
				'default'           => '#',
				'type'              => 'theme_mod',
				'sanitize_callback' => 'sanitize_url'
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'home_banner_button_link',
			array(
				'label'    => __( 'Homepage Banner Button Link', 'hyperpress' ),
				'settings' => 'hyperpress_home_banner_button_link',
				'section'  => 'static_front_page',
			) ) );

		$wp_customize->add_setting( 'hyperpress_home_banner_background_color',
			array(
				'type'              => 'theme_mod',
				'sanitize_callback' => 'sanitize_hex_color',
			) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'home_banner_background_color',
			array(
				'label'    => __( 'Homepage Banner Background Color', 'hyperpress' ),
				'settings' => 'hyperpress_home_banner_background_color',
				'section'  => 'static_front_page',
			) ) );

		$wp_customize->add_setting( 'hyperpress_home_blog_categories', array(
			'type'              => 'theme_mod',
			'sanitize_callback' => '',
		) );

		$wp_customize->add_control( new HYPERpress_Dropdown_Category_Control( $wp_customize, 'home_blog_categories', array(
			'section'     => 'static_front_page',
			'label'       => __( 'Homepage Blog Category', 'hyperpress' ),
			'description' => __( 'Select the category that the homepage will show posts from', 'hyperpress' ),
			'settings'    => 'hyperpress_home_blog_categories',
		) ) );

		$wp_customize->add_setting( 'hyperpress_home_blog_post_types', array(
			'type'              => 'theme_mod',
			'default'           => array( 'post' ),
			'sanitize_callback' => '',
		) );
		$wp_customize->add_control( new HYPERpress_Dropdown_Post_Type_Control( $wp_customize, 'home_blog_post_types', array(
			'section'     => 'static_front_page',
			'label'       => __( 'Homepage Blog Post Types', 'hyperpress' ),
			'description' => __( 'Select the Post Types that the homepage will display', 'hyperpress' ),
			'settings'    => 'hyperpress_home_blog_post_types',
		) ) );
	}

	add_action( 'customize_register', 'hyperpress_customize_homepage' );
endif;
