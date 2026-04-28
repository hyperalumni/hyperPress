<?php
if ( ! function_exists( 'hyperpress_breadcrumb_month' ) ) :
	function hyperpress_breadcrumb_month( $breadcrumbs ) {
		if ( is_month() ) {
			$archivesText = __( 'Archives', 'hyperpress' );
			// add year
			$year          = get_the_time( 'Y' );
			$breadcrumbs[] = [
				"title"   => $year . ' ' . $archivesText,
				"url"     => get_year_link( $year ),
				"classes" => [
					'li'    => [
						'item-year',
						'item-year-' . $year
					],
					'bread' => [
						'bread-year',
						'bread-year-' . $year
					]
				]
			];

			// add month
			$monthNumber   = get_the_time( 'm' );
			$monthName     = get_the_time( 'M' );
			$breadcrumbs[] = [
				"title"   => $monthName . ' ' . $archivesText,
				"url"     => get_month_link( $year, $monthNumber ),
				"classes" => [
					'li'    => [
						'item-month',
						'item-month-' . $monthNumber,
						'item-month-' . $monthName
					],
					'bread' => [
						'bread-month',
						'bread-month-' . $monthNumber,
						'bread-month-' . $monthName
					]
				]
			];
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_month' );
endif;
