<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_year' );

function hyperpress_breadcrumbs_year( $breadcrumbs ) {
	if ( is_year() ) {
		$archives_text = __( 'Archives', 'hyperpress' );
		// add year
		$year          = get_the_time( 'Y' );
		$breadcrumbs[] = array(
			'title'   => $year . ' ' . $archives_text,
			'url'     => get_year_link( $year ),
			'classes' => array(
				'li'    => array(
					'item-year',
					'item-year-' . $year,
				),
				'bread' => array(
					'bread-year',
					'bread-year-' . $year,
				),
			),
		);
	}

	return $breadcrumbs;
}
