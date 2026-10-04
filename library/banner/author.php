<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_author' );

function hyperpress_banner_author( $banner ) {
	if ( is_author() ) {
		$banner['type']     = 'author';
		$banner['title']    = __( 'Published By ', 'hyperpress' ) . ( ! empty( get_the_author_meta('user_firstname') ) && ! empty( get_the_author_meta('user_lastname') )) ? get_the_author_meta('user_firstname') . ' ' . get_the_author_meta('user_lastname') : get_the_author_meta('display_name');
		$banner['subtitle'] = __( 'Author', 'hyperpress' ) . ': <span class="emphasis">' . get_the_author_meta('user_nicename') . '</span>';
	}

	return $banner;
}
