<?php
if ( ! function_exists( 'hyperpress_breadcrumb_404' ) ) :
	function hyperpress_breadcrumb_404( $breadcrumbs ) {
		if ( is_404() ) {
			$breadcrumbs[] = [
				"title"   => __( 'Error 404', 'hyperpress' ),
				"classes" => [
					'li'    => [
						'item-error',
						'item-error-404'
					],
					'bread' => [
						'bread-error',
						'bread-error-404'
					]
				]
			];
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_404' );
endif;
