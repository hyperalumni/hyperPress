<?php
if ( ! function_exists( 'hyperpress_customize_copyright' ) ) :
	function hyperpress_customize_copyright( $wp_customize ): void {
		$wp_customize->add_setting( 'hyperpress_site_copyright_name',
			array(
				'default'   => get_bloginfo( 'name' ),
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			) );
		$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'site_copyright_name',
			array(
				'label'    => __( 'Copyright Name', 'hyperpress' ),
				'settings' => 'hyperpress_site_copyright_name',
				'section'  => 'title_tagline',
			) ) );
	}

	add_action( 'customize_register', 'hyperpress_customize_copyright' );
endif;
