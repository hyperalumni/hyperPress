<?php

namespace HYPER_Press_Newsletter;
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Newsletter\hyperpress_banner_newsletter' ) ) :
	add_filter( 'hyperpress_banner_content', '\HYPER_Press_Newsletter\hyperpress_banner_newsletter' );

	function hyperpress_banner_newsletter( $banner ) {
		$hyper_newsletter = 'newsletter';
		$hyper_season     = 'season';

		if ( is_singular( $hyper_newsletter ) || is_post_type_archive( $hyper_newsletter ) ) {
			$banner['type']            = $hyper_newsletter;
			$subtitle                  = get_theme_mod( 'hyperpress_newsletter_banner_subtitle' );
			$banner['subtitle']        = $subtitle;
			$banner['backgroundColor'] = get_theme_mod( 'hyperpress_hyper_orange' );

			if ( is_singular( $hyper_newsletter ) ) {


				if ( empty( $banner['title'] ) ) {
					// Get season information
					$seasons = wp_get_object_terms( get_the_ID(), $hyper_season, [
						'orderby' => 'name',
						'order'   => 'DESC'
					] );
					if ( ! empty( $seasons ) ) {
						$season = $seasons[0];

						$banner['title'] = $season->name . ': ' . get_the_title();
					} else {
						$banner['title'] = 'Newsletter';
					}


				}

				$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color' );
				$blogPageID                = get_option( 'page_for_posts' );

				if ( empty( $banner['backgroundColor'] ) && $blogPageID > 0 ) {
					// if the blog page is not the front page, use its values
					$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color', '', $blogPageID );
				}
			}

			if ( is_post_type_archive( $hyper_newsletter ) ) {
				$banner['title'] = __( 'Newsletters', 'hyperpress' );
				if ( is_tax( $hyper_season ) ) {
					if ( ! empty( $subtitle ) ) {
						$banner['subtitle'] = $subtitle . ' from ' . get_term( get_queried_object_id() )->name;
					}
				}
			}

		}

		return $banner;
	}
endif;
