<?php

namespace HYPER_Press_Sponsor;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Sponsor\hyperpress_sponsor_load_widgets' ) ) :

	function hyperpress_sponsor_load_widgets(): void {
		require_once( 'display-sponsors.php' );
		register_widget( '\HYPER_Press_Sponsor\hyperpress_display_sponsors_widget' );
	}

	add_action( 'widgets_init', '\HYPER_Press_Sponsor\hyperpress_sponsor_load_widgets' );
endif;
