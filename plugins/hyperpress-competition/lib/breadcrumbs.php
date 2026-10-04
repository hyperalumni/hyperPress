<?php

namespace HYPER_Press_Competition;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Competition\hyperpress_breadcrumbs_competition' ) ) :
	add_filter( 'hyperpress_breadcrumbs_content', '\HYPER_Press_Competition\hyperpress_breadcrumbs_competition' );

	function hyperpress_breadcrumbs_competition( $breadcrumbs ) {
		if ( function_exists( 'hyperpress_breadcrumbs_custom_post_type' ) ) {
			return hyperpress_breadcrumbs_custom_post_type( $breadcrumbs, 'competition' );
		}

		return $breadcrumbs;
	}
endif;
