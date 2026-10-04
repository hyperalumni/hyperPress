<?php

namespace HYPER_Press_Award;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Award\hyperpress_breadcrumb_award' ) ) :
	add_filter( 'hyperpress_breadcrumbs_content', '\HYPER_Press_Award\hyperpress_breadcrumb_award' );

	function hyperpress_breadcrumb_award( $breadcrumbs ) {
		if ( function_exists( 'hyperpress_breadcrumbs_custom_post_type' ) ) {
			return hyperpress_breadcrumbs_custom_post_type( $breadcrumbs, 'award' );
		}

		return $breadcrumbs;
	}
endif;
