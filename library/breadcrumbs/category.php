<?php
if ( ! function_exists( 'hyperpress_breadcrumb_category' ) ) :
	function hyperpress_breadcrumb_category( $breadcrumbs ) {
		if ( is_category() ) {
			// Get the query & post information
			global $post;
			$category = get_the_category();

			$breadcrumbs[] = [
				"title"   => __( 'Category', 'hyperpress' ),
				"classes" => [ 'li' => [], 'bread' => [] ]
			];

			// add category
			$breadcrumbs[] = [
				"title"   => $category[0]->cat_name,
				"url"     => get_category_link( $category[0] ),
				"classes" => [
					'li'    => [
						'item-category',
						'item-category-' . $category[0]->term_id,
						'item-category-' . $category[0]->category_nicename
					],
					'bread' => [
						'bread-category',
						'bread-category-' . $category[0]->term_id,
						'bread-category-' . $category[0]->category_nicename
					]
				]
			];
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_category' );
endif;
