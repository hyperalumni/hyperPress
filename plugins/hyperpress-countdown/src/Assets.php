<?php
namespace HyperPress\Countdown;

defined( 'ABSPATH' ) || exit;

final class Assets {

	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_scripts' ) );
	}

	public static function register_scripts(): void {
		// Register TimeCircles
		wp_register_script( 'hyperpress-countdown-timecircles', plugin_dir_url( __DIR__ ) . '/assets/js/TimeCircles.js', array( 'jquery' ), '1.5.3', false );
		wp_register_style( 'hyperpress-countdown-timecircles', plugin_dir_url( __DIR__ ) . '/assets/css/TimeCircles.css', array(), '1.5.3', 'screen' );
	}
}
