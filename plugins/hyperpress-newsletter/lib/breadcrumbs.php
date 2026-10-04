<?php

namespace HYPER_Press_Newsletter;
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Newsletter\hyperpress_breadcrumbs_newsletter' ) ) :
	add_filter( 'hyperpress_breadcrumbs_content', '\HYPER_Press_Newsletter\hyperpress_breadcrumbs_newsletter' );

	function hyperpress_breadcrumbs_newsletter( $breadcrumbs ) {
		if ( function_exists( 'hyperpress_breadcrumbs_custom_post_type' ) ) {
			return hyperpress_breadcrumbs_custom_post_type( $breadcrumbs, 'newsletter' );
		}

		return $breadcrumbs;
	}
endif;
