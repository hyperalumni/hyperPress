<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_month' );

function hyperpress_breadcrumbs_month( $breadcrumbs ) {
	if ( is_month() ) {
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

		// add month
		$month_number  = get_the_time( 'm' );
		$month_name    = get_the_time( 'M' );
		$breadcrumbs[] = array(
			'title'   => $month_name . ' ' . $archives_text,
			'url'     => get_month_link( $year, $month_number ),
			'classes' => array(
				'li'    => array(
					'item-month',
					'item-month-' . $month_number,
					'item-month-' . $month_name,
				),
				'bread' => array(
					'bread-month',
					'bread-month-' . $month_number,
					'bread-month-' . $month_name,
				),
			),
		);
	}

	return $breadcrumbs;
}
