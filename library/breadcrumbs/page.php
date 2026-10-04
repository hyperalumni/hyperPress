<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_page' );

function hyperpress_breadcrumbs_page( $breadcrumbs ) {
	if ( is_page() ) {
		// Get the query & post information
		if ( get_post_parent() ) {
			// If child page, get parents
			// If child page, get parents
			$ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );

			foreach ( $ancestors as $ancestor ) {
				$breadcrumbs[] = array(
					'title'   => get_the_title( $ancestor ),
					'url'     => get_permalink( $ancestor ),
					'classes' => array(
						'li'    => array( 'item-page', 'item-page-' . $ancestor ),
						'bread' => array( 'bread-page', 'bread-page-' . $ancestor ),
					),
				);
			}
		}

		// add current page
		$breadcrumbs[] = array(
			'title'   => get_the_title(),
			'url'     => get_permalink(),
			'classes' => array(
				'li'    => array( 'item-page', 'item-page-' . get_the_ID() ),
				'bread' => array( 'bread-page', 'bread-page-' . get_the_ID() ),
			),
		);
	}

	return $breadcrumbs;
}
