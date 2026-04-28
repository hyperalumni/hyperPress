<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_banner_newsletter' ) ) :
	function hyperpress_banner_newsletter( $banner ) {
		$hyper_newsletter = 'newsletter';
		if ( is_singular( $hyper_newsletter ) ) {
			$banner['type'] = $hyper_newsletter;
			$blogPageID     = get_option( 'page_for_posts' );

			if ( empty( $banner['title'] ) ) {
				$banner['title'] = 'Newsletter';
			}

			$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color' );
			if ( empty( $banner['backgroundColor'] ) && $blogPageID > 0 ) {
				// if the blog page is not the front page, use its values
				$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color', '', $blogPageID );
			}
			$subtitle           = get_theme_mod( 'hyperpress_newsletter_banner_subtitle' );
			$banner['subtitle'] = get_theme_mod( 'hyperpress_newsletter_banner_subtitle' );
		}

		if ( is_post_type_archive( $hyper_newsletter ) ) {
			$banner['type']     = $hyper_newsletter;
			$banner['title']    = __( 'Newsletters', 'hyperpress' );
			$banner['subtitle'] = get_theme_mod( 'hyperpress_newsletter_banner_subtitle' );

		}
		if ( is_tax( $hyper_newsletter . '_season' ) ) {
			$banner['type']  = $hyper_newsletter;
			$banner['title'] = __( 'Newsletters', 'hyperpress' );
			$subtitle        = get_theme_mod( 'hyperpress_newsletter_banner_subtitle' );
			if ( ! empty( $subtitle ) ) {
				$banner['subtitle'] = $subtitle . ' from ' . get_term( get_queried_object_id() )->name;
			}
		}
		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_newsletter' );
endif;
