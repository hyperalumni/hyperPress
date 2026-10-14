<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

// Priority 80: this specific fallback runs after the plugin banners (priority 10) and before the generic archive fallback (priority 90), so a plugin claims its own archive first.
add_filter( 'hyperpress_banner_content', 'hyperpress_banner_day', 80 );

function hyperpress_banner_day( $banner ) {
	if ( empty( $banner['type'] ) && is_day() ) {
		$banner['type']     = 'day';
		$banner['subtitle'] = __( 'Day', 'hyperpress' ) . ': <span class="emphasis">' . esc_html( get_the_time( 'F d, Y' ) ) . '</span>';
	}

	return $banner;
}
