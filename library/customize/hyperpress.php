<?php
if ( ! function_exists( 'hyperpress_customize_section' ) ) :
	function hyperpress_customize_section( $wp_customize ): void {
		$wp_customize->add_section( 'hyperpress', array(
			'title'    => 'hyperPress',
			'priority' => 105, // Before Widgets.
		) );
	}

	add_action( 'customize_register', 'hyperpress_customize_section' );
endif;
