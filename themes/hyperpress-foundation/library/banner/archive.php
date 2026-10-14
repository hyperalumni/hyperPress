<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

// Priority 90: this generic fallback runs after the plugin banners (priority 10) and the specific archive fallbacks (priority 80), so a plugin claims its archive first.
add_filter( 'hyperpress_banner_content', 'hyperpress_banner_archive', 90 );

function hyperpress_banner_archive( $banner ) {
	// The generic "Archive" title also covers the tag, category and date archives, which only set a type and a subtitle.
	// Without it the banner template falls back to the title of the first post of the archive.
	if ( is_archive() && empty( $banner['title'] ) ) {
		if ( empty( $banner['type'] ) ) {
			$banner['type'] = 'archive';

			$blog_page_id = get_option( 'page_for_posts' );
			if ( $blog_page_id > 0 ) {
				// if the blog page is not the front page, use its values
				$banner['backgroundColor'] = hyperpress_meta( 'hyperpress_banner_background_color', $blog_page_id );
			}
		}
		$banner['title'] = __( 'Archive', 'hyperpress' );
	}

	return $banner;
}
