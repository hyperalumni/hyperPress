<?php

namespace HYPER_Press_Countdown;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Countdown\hyperpress_countdown_scripts' ) ) :
	add_action( 'wp_enqueue_scripts', '\HYPER_Press_Countdown\hyperpress_countdown_scripts' );

	function hyperpress_countdown_scripts(): void {
		// Register TimeCircles
		wp_register_script( 'timecircles', plugin_dir_url( __DIR__ ) . '/assets/js/TimeCircles.js', array( 'jquery' ), '1.5.3', false );
		wp_register_style( 'timecircles', plugin_dir_url( __DIR__ ) . '/assets/css/TimeCircles.css', array(), '1.5.3', 'screen' );
	}
endif;