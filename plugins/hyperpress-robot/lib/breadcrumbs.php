<?php

namespace HYPER_Press_Robot;
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Robot\hyperpress_breadcrumbs_robot' ) ) :
	add_filter( 'hyperpress_breadcrumbs_content', '\HYPER_Press_Robot\hyperpress_breadcrumbs_robot' );

	function hyperpress_breadcrumbs_robot( $breadcrumbs ) {
		if ( function_exists( 'hyperpress_breadcrumbs_custom_post_type' ) ) {
			return hyperpress_breadcrumbs_custom_post_type( $breadcrumbs, 'robot' );
		}

		return $breadcrumbs;
	}
endif;
