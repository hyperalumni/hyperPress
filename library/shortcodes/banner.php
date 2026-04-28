<?php

if ( ! function_exists( 'hyper_add_banner_shortcode' ) ) :
	function hyper_add_banner_shortcode( $atts, $content = "" ) {
		extract( shortcode_atts( [ 'anchor' => '', 'title' => '', 'subtitle' => '' ], $atts ) );
		if ( ! empty( $title ) || ! empty( $subtitle ) ) {
			$output = '<header role="banner" class="banner">';
			if ( ! empty( $anchor ) ) {
				$output .= '<span class="anchor" id="' . $anchor . '"></span>';
			}
			$output .= '<div class="main-container">';

			if ( ! empty( $title ) ) {
				$output .= '<h2 class="entry-title">' . $title . '</h2>';
			}
			if ( ! empty( $subtitle ) ) {
				$output .= '<h4 class="subtitle">' . $subtitle . '</h4>';
			}
			$output .= '</div>';
			$output .= '</header>';

			return $output;
		}

		return '';
	}

	add_shortcode( 'hyper_banner', 'hyper_add_banner_shortcode' );
endif;
