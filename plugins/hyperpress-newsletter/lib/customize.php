<?php

namespace HYPER_Press_Newsletter;

use WP_Customize_Code_Editor_Control;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Newsletter\hyperpress_customize_newsletter_banner' ) ) :
	add_action( 'customize_register', '\HYPER_Press_Newsletter\hyperpress_customize_newsletter_banner' );

	function hyperpress_customize_newsletter_banner( $wp_customize ): void {
		$wp_customize->add_setting( 'hyperpress_newsletter_banner_subtitle',
			array(
				'default'   => 'Updates on Team HYPER from our Marketing Team',
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			) );
		$wp_customize->add_control( new WP_Customize_Code_Editor_Control( $wp_customize, 'newsletter_banner_subtitle',
			array(
				'label'    => __( 'Newsletter Banner Subtitle', 'hyperpress' ),
				'settings' => 'hyperpress_newsletter_banner_subtitle',
				'section'  => 'hyperpress_newsletter',
			) ) );
	}
endif;

