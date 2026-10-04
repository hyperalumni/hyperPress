<?php
namespace HyperPress\Shortcodes\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class Emphasis {

	public static function register(): void {
		add_shortcode( 'hyper_emphasis', array( self::class, 'render' ) );
	}

	public static function render( $atts = array(), $content = '' ): string {
		return '<span class="emphasis">' . do_shortcode( $content ) . '</span>';
	}
}
