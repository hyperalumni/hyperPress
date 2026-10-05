<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_blog' );

function hyperpress_banner_blog( $banner ) {
	if ( is_home() && ! is_front_page() ) {
		$blog_page_id = get_option( 'page_for_posts' );

		$banner['type'] = 'blog';

		if ( $blog_page_id > 0 ) {
			// if the blog page is not the front page, use its values
			$banner['title']           = get_the_title( $blog_page_id );
			$banner['backgroundColor'] = hyperpress_meta( 'hyperpress_banner_background_color', $blog_page_id );

			$page_subtitle = hyperpress_meta( 'hyperpress_banner_subtitle', $blog_page_id );
			if ( ! empty( $page_subtitle ) ) {
				$banner['subtitle'] = $page_subtitle;
			}
		}
	}

	return $banner;
}
