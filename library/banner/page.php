<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_page' );

function hyperpress_banner_page( $banner ) {
	if ( is_page() && ! is_front_page() && ! is_home() ) {
		$banner['type'] = 'page';

		$banner['backgroundColor'] = hyperpress_meta( 'hyperpress_banner_background_color' );

		if ( empty( $banner['backgroundColor'] ) ) {
			$parent_ids = array_reverse( get_post_ancestors( get_the_ID() ) );
			if ( ! empty( $parent_ids ) && ! empty( $parent_ids[0] ) ) {
				$banner['backgroundColor'] = hyperpress_meta( 'hyperpress_banner_background_color', $parent_ids[0] );
			}
		}

		$page_subtitle = hyperpress_meta( 'hyperpress_banner_subtitle' );
		if ( ! empty( $page_subtitle ) ) {
			$banner['subtitle'] = $page_subtitle;
		}
	}

	return $banner;
}
