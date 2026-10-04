<?php
use function HYPER_Press_Season\get_sorted_seasons;
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyperpress_labels_post' ) ) :
	add_filter( 'hyperpress_labels_content', 'hyperpress_labels_post' );

	function hyperpress_labels_post( $labels ) {
		if ( is_singular( 'post' ) || ( is_main_query() && get_post_type() === 'post' ) ) {
			$labels[] = [
				'label'   => sprintf( __( '%1$s @ %2$s', 'hyperpress' ), get_the_date(), get_the_time() ),
				'title'   => 'Posted At',
				'classes' => [ 'dark' ],
				'url'     => '',
				'rel'     => ''
			];
			// add author information
			$labels[] = [
				'label'   => get_the_author(),
				'title'   => 'Posted By',
				'classes' => [ 'primary', 'byline', 'author' ],
				'url'     => get_author_posts_url( get_the_author_meta( 'ID' ) ),
				'rel'     => 'author'
			];
			// Get the season information
			$seasons = get_sorted_seasons();
			if ( ! empty( $seasons ) ) {
				$season = $seasons[0];
				if ( ! empty( $season ) ) {
					$labels[] = [
						'label'   => $season->name,
						'title'   => 'Posted During',
						'classes' => [ 'warning' ],
						'url'     => get_category_link( $season->term_id ),
						'rel'     => ''
					];
				}
			}

			// Get category information
			$categories = get_the_category();
			$category   = $categories[0];
			if ( ! empty( $category ) ) {
				$labels[] = [
					'label'   => $category->cat_name,
					'title'   => 'Posted In',
					'classes' => [ 'info' ],
					'url'     => get_category_link( $category->term_id ),
					'rel'     => ''
				];
			}
			$labels[] = [
				'label'   => get_comments_number() . ( get_comments_number() == 1 ? ' comment' : ' comments' ),
				'title'   => '',
				'classes' => [ 'neutral' ],
				'url'     => get_comments_link(),
				'rel'     => ''
			];
		}

		return $labels;
	}
endif;
