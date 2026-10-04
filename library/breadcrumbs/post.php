<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_post' );

function hyperpress_breadcrumbs_post( $breadcrumbs ) {
	if ( is_singular( 'post' ) ) {
		// Get the season information
		$seasons = \HyperPress\Season\SortedSeasons::get();
		if ( ! empty( $seasons ) ) {
			$season = $seasons[0];
			if ( ! empty( $season ) ) {
				$breadcrumbs[] = [
					"title"   => $season->name,
					'url'     => get_category_link( $season->term_id ),
					"classes" => [
						'li'    => [
							'item-' . $season->slug
						],
						'bread' => [
							'bread-' . $season->slug
						]
					]
				];
			}
		}

		// Get category information
		$categories = get_the_category();
		if ( ! empty( $categories ) ) {
			$category      = $categories[0];
			$breadcrumbs[] = [
				'title'   => $category->cat_name,
				'url'     => get_category_link( $category->term_id ),
				'classes' => [
					'li'    => [
						'item-cat',
						'item-cat-' . $category->term_id,
						'item-cat-' . $category->category_nicename
					],
					'bread' => [
						'bread-cat',
						'bread-cat-' . $category->term_id,
						'bread-cat-' . $category->category_nicename
					]
				],
			];
		}


		// add current post
		$breadcrumbs[] = [
			"title"   => get_the_title(),
			"url"     => get_permalink(),
			"classes" => [
				'li'    => [ 'item-post', 'item-post-' . get_the_ID() ],
				'bread' => [ 'bread-post', 'bread-post-' . get_the_ID() ]
			]
		];
	}

	return $breadcrumbs;
}
