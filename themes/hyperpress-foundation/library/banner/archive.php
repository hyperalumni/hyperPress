<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

// Priority 90: this generic fallback runs after the plugin banners (priority 10), so a plugin claims its archive first.
add_filter( 'hyperpress_banner_content', 'hyperpress_banner_archive', 90 );

function hyperpress_banner_archive( $banner ) {
	if ( empty( $banner['type'] ) ) {
		if ( is_archive() ) {
			$banner['type']  = 'archive';
			$banner['title'] = __( 'Archive', 'hyperpress' );

			$blog_page_id = get_option( 'page_for_posts' );
			if ( $blog_page_id > 0 ) {
				// if the blog page is not the front page, use its values
				$banner['backgroundColor'] = hyperpress_meta( 'hyperpress_banner_background_color', $blog_page_id );
			}
		}
	}

	return $banner;
}
