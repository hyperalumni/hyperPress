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
			// The blog page colour is added by hyperpress_banner_blog_color() (priority 95).
			$banner['type'] = 'archive';
		}
		$banner['title'] = __( 'Archive', 'hyperpress' );
	}

	return $banner;
}
