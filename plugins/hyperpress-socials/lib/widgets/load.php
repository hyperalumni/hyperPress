<?php

namespace HYPER_Press_Socials;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Socials\hyperpress_socials_load_widgets' ) ) :
	add_action( 'widgets_init', '\HYPER_Press_Socials\hyperpress_socials_load_widgets' );

	function hyperpress_socials_load_widgets(): void {
		require_once( 'display-socials.php' );
		register_widget( '\HYPER_Press_Socials\hyperpress_display_socials_widget' );
	}
endif;
