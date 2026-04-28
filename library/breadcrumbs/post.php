<?php
if ( ! function_exists( 'hyperpress_breadcrumb_post' ) ) :
	function hyperpress_breadcrumb_post( $breadcrumbs ) {
		if ( is_singular( 'post' ) ) {
			// Get the query & post information
			global $post;
			// Get category information
			$categories      = get_the_category();
			$breadcrumbs[] = [
				'title'   => $categories[0]->cat_name,
				'url'     => get_category_link( $categories[0]->term_id ),
				'classes' => [
					'li'    => [
						'item-cat',
						'item-cat-' . $categories[0]->term_id,
						'item-cat-' . $categories[0]->category_nicename
					],
					'bread' => [
						'bread-cat',
						'bread-cat-' . $categories[0]->term_id,
						'bread-cat-' . $categories[0]->category_nicename
					]
				],
			];


			// add current post
			$breadcrumbs[] = [
				"title"   => get_the_title(),
				"url"     => get_permalink(),
				"classes" => [
					'li'    => [ 'item-post', 'item-post-' . $post->ID ],
					'bread' => [ 'bread-post', 'bread-post-' . $post->ID ]
				]
			];
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_post' );
endif;
