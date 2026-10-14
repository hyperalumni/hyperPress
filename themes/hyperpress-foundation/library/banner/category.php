<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

// Priority 80: this specific fallback runs after the plugin banners (priority 10) and before the generic archive fallback (priority 90), so a plugin claims its own archive first.
add_filter( 'hyperpress_banner_content', 'hyperpress_banner_category', 80 );

function hyperpress_banner_category( $banner ) {
	if ( empty( $banner['type'] ) && is_category() ) {
		$banner['type']     = 'category';
		$banner['subtitle'] = __( 'Category', 'hyperpress' ) . ': <span class="emphasis">' . esc_html( single_cat_title( '', false ) ) . '</span>';
	}

	return $banner;
}
