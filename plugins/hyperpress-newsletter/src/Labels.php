<?php
namespace HyperPress\Newsletter;

defined( 'ABSPATH' ) || exit;

final class Labels {

	public static function register(): void {
		add_filter( 'hyperpress_labels_content', array( self::class, 'labels_newsletter' ) );
	}

	public static function labels_newsletter( $labels ) {
		$hyper_newsletter = Plugin::POST_TYPE;
		$hyper_season     = 'hyper_season';
		if ( is_singular( $hyper_newsletter ) || is_post_type_archive( $hyper_newsletter ) || ( is_main_query() && get_post_type() === $hyper_newsletter ) ) {
			$labels[] = array(
				/* translators: 1: post date, 2: post time. */
				'label'   => sprintf( __( '%1$s @ %2$s', 'hyperpress' ), get_the_date(), get_the_time() ),
				'title'   => 'Posted At',
				'classes' => array( 'dark' ),
				'url'     => '',
				'rel'     => '',
			);
			// Add author information.
			$labels[] = array(
				'label'   => get_the_author(),
				'title'   => 'Posted By',
				'classes' => array( 'primary', 'byline', 'author' ),
				'url'     => get_author_posts_url( get_the_author_meta( 'ID' ) ),
				'rel'     => 'author',
			);
			// Get season information.
			$seasons = wp_get_object_terms(
				get_the_ID(),
				$hyper_season,
				array(
					'orderby' => 'name',
					'order'   => 'DESC',
				)
			);
			if ( ! is_wp_error( $seasons ) && ! empty( $seasons ) ) {
				$season = $seasons[0];

				$labels[] = array(
					'label'   => $season->name,
					'title'   => 'Posted During',
					'classes' => array( 'warning' ),
					'url'     => get_category_link( $season->term_id ),
					'rel'     => '',
				);
			}
			$comments_number = (int) get_comments_number();
			$labels[]        = array(
				'label'   => $comments_number . ( 1 === $comments_number ? ' comment' : ' comments' ),
				'title'   => '',
				'classes' => array( 'neutral' ),
				'url'     => get_comments_link(),
				'rel'     => '',
			);
		}

		return $labels;
	}
}
