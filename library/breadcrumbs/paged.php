<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_paged' );

function hyperpress_breadcrumbs_paged( $breadcrumbs ) {
	$paged = get_query_var( 'paged' );
	if ( ! empty( $paged ) ) {

		$breadcrumbs[] = [
			"title"   => __( 'Page', 'hyperpress' ) . ' ' . $paged,
			"classes" => [
				'li'    => [ 'item-paged', 'item-paged-' . $paged ],
				'bread' => [ 'bread-paged', 'bread-paged-' . $paged ]
			]
		];
	}

	return $breadcrumbs;
}
