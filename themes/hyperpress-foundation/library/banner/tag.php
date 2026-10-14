<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

// Priority 80: this specific fallback runs after the plugin banners (priority 10) and before the generic archive fallback (priority 90), so a plugin claims its own archive first.
add_filter( 'hyperpress_banner_content', 'hyperpress_banner_tag', 80 );

function hyperpress_banner_tag( $banner ) {
	if ( empty( $banner['type'] ) && is_tag() ) {
		$banner['type']     = 'tag';
		$banner['subtitle'] = __( 'Tag', 'hyperpress' ) . ': <span class="emphasis">' . esc_html( single_tag_title( '', false ) ) . '</span>';
	}

	return $banner;
}
