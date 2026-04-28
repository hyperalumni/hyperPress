<?php
if ( ! function_exists( 'hyperpress_breadcrumb_year' ) ) :
	function hyperpress_breadcrumb_year( $breadcrumbs ) {
		if ( is_year() ) {
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
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_year' );
endif;
