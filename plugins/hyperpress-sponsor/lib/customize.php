<?php

namespace HYPER_Press_Sponsor;
require_once ABSPATH . 'wp-includes/class-wp-customize-control.php';
require_once ABSPATH . 'wp-includes/customize/class-wp-customize-code-editor-control.php';

use WP_Customize_Code_Editor_Control;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Sponsor\hyperpress_customize_sponsor_banner' ) ) :
	function hyperpress_customize_sponsor_banner( $wp_customize ): void {
		$wp_customize->add_setting( 'hyperpress_sponsor_banner_subtitle',
			array(
				'default'   => 'Helping Make HYPER Happen',
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			) );
		$wp_customize->add_control( new WP_Customize_Code_Editor_Control( $wp_customize, 'sponsor_banner_subtitle',
			array(
				'label'    => __( 'Sponsor Banner Subtitle', 'hyperpress' ),
				'settings' => 'hyperpress_sponsor_banner_subtitle',
				'section'  => 'hyperpress_sponsor',
			) ) );
	}

	add_action( 'customize_register', '\HYPER_Press_Sponsor\hyperpress_customize_sponsor_banner' );
endif;

