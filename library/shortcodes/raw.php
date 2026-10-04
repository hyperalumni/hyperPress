<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyper_raw_content_shortcode' ) ) :
	add_shortcode( 'raw', 'hyper_raw_content_shortcode' );

	function hyper_raw_content_shortcode( $atts = null, $content = '' ): array|string|null {
		$content = do_shortcode( shortcode_unautop( $content ) );

		return preg_replace( '#^<\/p>|^<br \/>|<p>$#', '', $content );
	}
endif;
