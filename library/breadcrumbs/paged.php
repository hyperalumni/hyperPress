<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_paged' );

function hyperpress_breadcrumbs_paged( $breadcrumbs ) {
	$paged = get_query_var( 'paged' );
	if ( ! empty( $paged ) ) {

		$breadcrumbs[] = array(
			'title'   => __( 'Page', 'hyperpress' ) . ' ' . $paged,
			'classes' => array(
				'li'    => array( 'item-paged', 'item-paged-' . $paged ),
				'bread' => array( 'bread-paged', 'bread-paged-' . $paged ),
			),
		);
	}

	return $breadcrumbs;
}
