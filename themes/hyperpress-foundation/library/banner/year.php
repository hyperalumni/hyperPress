<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

// Priority 80: this specific fallback runs after the plugin banners (priority 10) and before the generic archive fallback (priority 90), so a plugin claims its own archive first.
add_filter( 'hyperpress_banner_content', 'hyperpress_banner_year', 80 );

function hyperpress_banner_year( $banner ) {
	if ( empty( $banner['type'] ) && is_year() ) {
		$banner['type']     = 'year';
		$banner['subtitle'] = __( 'Year', 'hyperpress' ) . ': <span class="emphasis">' . esc_html( get_the_time( 'Y' ) ) . '</span>';
	}

	return $banner;
}
