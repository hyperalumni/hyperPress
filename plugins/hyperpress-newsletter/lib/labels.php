<?php

namespace HYPER_Press_Newsletter;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
if ( ! function_exists( '\HYPER_Press_Newsletter\hyperpress_labels_newsletter' ) ) :
	add_filter( 'hyperpress_labels_content', '\HYPER_Press_Newsletter\hyperpress_labels_newsletter' );

	function hyperpress_labels_newsletter( $labels ) {
		$hyper_newsletter = 'newsletter';
		$hyper_season     = 'season';
		if ( is_singular( $hyper_newsletter ) || is_post_type_archive( $hyper_newsletter ) || ( is_main_query() && get_post_type() === $hyper_newsletter ) ) {
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
			// Get season information
			$seasons = wp_get_object_terms( get_the_ID(), $hyper_season, [
				'orderby' => 'name',
				'order'   => 'DESC'
			] );
			if ( ! empty( $seasons ) ) {
				$season = $seasons[0];

				$labels[] = [
					'label'   => $season->name,
					'title'   => 'Posted During',
					'classes' => [ 'warning' ],
					'url'     => get_category_link( $season->term_id ),
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
