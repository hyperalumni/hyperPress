<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyper_emphasis_shortcode' ) ) :
	add_shortcode( 'hyper_emphasis', 'hyper_emphasis_shortcode' );

	function hyper_emphasis_shortcode( $atts, $content = "" ): string {
		return '<span class="emphasis">' . do_shortcode( $content ) . '</span>';

	}
endif;
