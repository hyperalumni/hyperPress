<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

// Priority 80: this specific fallback runs after the plugin banners (priority 10) and before the generic archive fallback (priority 90), so a plugin claims its own archive first.
add_filter( 'hyperpress_banner_content', 'hyperpress_banner_month', 80 );

function hyperpress_banner_month( $banner ) {
	if ( empty( $banner['type'] ) && is_month() ) {
		$banner['type']     = 'month';
		$banner['subtitle'] = __( 'Month', 'hyperpress' ) . ': <span class="emphasis">' . esc_html( get_the_time( 'F Y' ) ) . '</span>';
	}

	return $banner;
}
