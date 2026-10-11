<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_404' );

function hyperpress_breadcrumbs_404( $breadcrumbs ) {
	if ( is_404() ) {
		$breadcrumbs[] = array(
			'title'   => __( 'Error 404', 'hyperpress' ),
			'classes' => array(
				'li'    => array(
					'item-error',
					'item-error-404',
				),
				'bread' => array(
					'bread-error',
					'bread-error-404',
				),
			),
		);
	}

	return $breadcrumbs;
}
