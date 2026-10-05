<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_author' );

function hyperpress_banner_author( $banner ) {
	if ( is_author() ) {
		$author_id   = get_queried_object_id();
		$first_name  = get_the_author_meta( 'user_firstname', $author_id );
		$last_name   = get_the_author_meta( 'user_lastname', $author_id );
		$author_name = ( ! empty( $first_name ) && ! empty( $last_name ) ) ? $first_name . ' ' . $last_name : get_the_author_meta( 'display_name', $author_id );

		$banner['type']     = 'author';
		$banner['title']    = __( 'Published By ', 'hyperpress' ) . $author_name;
		$banner['subtitle'] = __( 'Author', 'hyperpress' ) . ': <span class="emphasis">' . get_the_author_meta( 'user_nicename', $author_id ) . '</span>';
	}

	return $banner;
}
