<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_post' );

function hyperpress_banner_post( $banner ) {
	if ( is_singular( 'post' ) ) {
		$banner['type'] = 'post';

		$blog_page_id = get_option( 'page_for_posts' );
		if ( $blog_page_id > 0 ) {
			// if the blog page is not the front page, use its values
			$banner['title']           = get_the_title( $blog_page_id );
			$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color', '', $blog_page_id );

			$blog_post_subtitle = rwmb_meta( 'hyperpress_banner_subtitle' );
			if ( ! empty( $blog_post_subtitle ) ) {
				// if the post has a subtitle set, use it
				$banner['subtitle'] = $blog_post_subtitle;
			} else {
				// if the blog page has a subtitle set, use it
				$blog_page_subtitle = rwmb_meta( 'hyperpress_banner_subtitle', '', $blog_page_id );
				if ( ! empty( $blog_page_subtitle ) ) {
					$banner['subtitle'] = $blog_page_subtitle;
				}
			}
		}
	}

	return $banner;
}
