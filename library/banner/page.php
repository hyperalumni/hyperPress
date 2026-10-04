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
			$parentIDs = array_reverse( get_post_ancestors( get_the_ID() ) );
			if ( ! empty( $parentIDs ) && ! empty( $parentIDs[0] ) ) {
				$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color', '', $parentIDs[0] );
			}
		}


		$pageSubtitle = rwmb_meta( 'hyperpress_banner_subtitle' );
		if ( ! empty( $pageSubtitle ) ) {
			$banner['subtitle'] = $pageSubtitle;
		}
	}

	return $banner;
}
