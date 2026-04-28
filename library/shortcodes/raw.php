<?php

if ( ! function_exists( 'hyper_raw_content_shortcode' ) ) :
	function hyper_raw_content_shortcode( $atts = null, $content = null ) {
		$content = do_shortcode( shortcode_unautop( $content ) );
		$content = preg_replace( '#^<\/p>|^<br \/>|<p>$#', '', $content );

		return $content;
	}
	add_shortcode( 'raw', 'hyper_raw_content_shortcode' );
endif;
