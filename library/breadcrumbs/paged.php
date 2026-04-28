<?php
if ( ! function_exists( 'hyperpress_breadcrumb_paged' ) ) :
	function hyperpress_breadcrumb_paged( $breadcrumbs ) {
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

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_paged' );
endif;
