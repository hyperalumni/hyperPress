<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_front' );

function hyperpress_banner_front( $banner ) {
	if ( is_front_page() ) {
		$banner['type']            = 'front';
		$banner['title']           = hyperpress_frontpage_setting( 'hyperpress_frontpage_banner_title', '' );
		$banner['subtitle']        = hyperpress_frontpage_setting( 'hyperpress_frontpage_banner_subtitle', '' );
		$banner['backgroundColor'] = hyperpress_frontpage_setting( 'hyperpress_frontpage_banner_background_color', '' );
		$banner['buttonText']      = hyperpress_frontpage_setting( 'hyperpress_frontpage_banner_button_text', '' );

		// An empty id would make get_post_permalink() link to the current post; the template hides the button without a link.
		$button_page = hyperpress_frontpage_setting( 'hyperpress_frontpage_banner_button_page', '' );
		$button_page = is_scalar( $button_page ) ? absint( $button_page ) : 0;

		$banner['buttonLink'] = $button_page > 0 ? get_post_permalink( $button_page ) : '';
	}

	return $banner;
}
