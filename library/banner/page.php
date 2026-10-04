<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_page' );

function hyperpress_banner_page( $banner ) {
	if ( is_page() && ! is_front_page() && ! is_home() ) {
		$banner['type'] = 'page';

		$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color' );

		if ( empty( $banner['backgroundColor'] ) ) {
			$parent_ids = array_reverse( get_post_ancestors( get_the_ID() ) );
			if ( ! empty( $parent_ids ) && ! empty( $parent_ids[0] ) ) {
				$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color', '', $parent_ids[0] );
			}
		}

		$page_subtitle = rwmb_meta( 'hyperpress_banner_subtitle' );
		if ( ! empty( $page_subtitle ) ) {
			$banner['subtitle'] = $page_subtitle;
		}
	}

	return $banner;
}
