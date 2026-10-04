<?php

namespace HYPER_Press_Season;
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
if ( ! function_exists( '\HYPER_Press_Season\hyperpress_breadcrumbs_season' ) ) :
	add_filter( 'hyperpress_breadcrumbs_content', '\HYPER_Press_Season\hyperpress_breadcrumbs_season' );

	function hyperpress_breadcrumbs_season( $breadcrumbs ) {
		if ( is_tax( 'season' ) && ! is_post_type_archive() ) {
			$breadcrumbs[] = [
				'title'   => 'Seasons',
				'classes' => [
					'li'    => [],
					'bread' => []
				]
			];
			$breadcrumbs[] = [
				'title'   => get_queried_object()->name,
				'classes' => [
					'li'    => [],
					'bread' => []
				]
			];
		}

		return $breadcrumbs;
	}
endif;
