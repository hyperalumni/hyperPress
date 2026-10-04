<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_author' );

function hyperpress_breadcrumbs_author( $breadcrumbs ) {
	if ( is_author() ) {
		$breadcrumbs[] = array(
			'title'   => __( 'Author', 'hyperpress' ),
			'classes' => array(
				'li'    => array( 'item-author' ),
				'bread' => array( 'bread-author' ),
			),
		);

		$breadcrumbs[] = array(
			'title'   => get_the_author_meta('display_name'),
			'url'     => get_the_author_meta('user_url'),
			'classes' => array(
				'li'    => array(
					'item-author',
					'item-author-' . get_the_author_meta('user_nicename'),
				),
				'bread' => array(
					'bread-author',
					'bread-author-' . get_the_author_meta('user_nicename'),
				),
			),
		);
	}

	return $breadcrumbs;
}
