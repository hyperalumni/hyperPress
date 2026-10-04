<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_tag' );

function hyperpress_banner_tag( $banner ) {
	if ( is_tag() ) {
		$banner['type']     = 'tag';
		$banner['subtitle'] = __( 'Tag', 'hyperpress' ) . ': <span class="emphasis">' . single_tag_title( '', false ) . '</span>';
	}

	return $banner;
}
