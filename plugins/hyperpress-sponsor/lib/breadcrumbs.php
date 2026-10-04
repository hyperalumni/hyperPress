<?php

namespace HYPER_Press_Sponsor;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Sponsor\hyperpress_breadcrumbs_sponsor' ) ) :
	function hyperpress_breadcrumbs_sponsor( $breadcrumbs ) {
		if ( function_exists( 'hyperpress_breadcrumbs_custom_post_type' ) ) {
			return hyperpress_breadcrumbs_custom_post_type( $breadcrumbs, 'sponsor' );
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', '\HYPER_Press_Sponsor\hyperpress_breadcrumbs_sponsor' );
endif;
