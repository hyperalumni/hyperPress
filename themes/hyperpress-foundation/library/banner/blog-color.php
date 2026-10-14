<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

// Priority 95: after the plugin banners (10), the specific archive fallbacks (80) and the generic archive fallback (90), so the type is known.
add_filter( 'hyperpress_banner_content', 'hyperpress_banner_blog_color', 95 );

/**
 * Blog-related banners default to the background colour of the Posts page.
 *
 * Keyed on the banner type, so plugin banners and search, 404, front, page and attachment are never touched,
 * and a colour that is already set is never overridden.
 */
function hyperpress_banner_blog_color( $banner ) {
	// Blog-related banner types: the generic archive and the category, tag, year, month, day and author archives.
	$blog_types = array( 'archive', 'category', 'tag', 'year', 'month', 'day', 'author' );

	if ( ! in_array( $banner['type'] ?? '', $blog_types, true ) || ! empty( $banner['backgroundColor'] ) ) {
		return $banner;
	}

	$blog_page_id = (int) get_option( 'page_for_posts' );
	if ( $blog_page_id <= 0 ) {
		return $banner;
	}

	$color = hyperpress_meta( 'hyperpress_banner_background_color', $blog_page_id );
	if ( is_string( $color ) && '' !== $color ) {
		$banner['backgroundColor'] = $color;
	}

	return $banner;
}
