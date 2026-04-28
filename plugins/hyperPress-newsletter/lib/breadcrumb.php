<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_breadcrumb_newsletter' ) ) :
	function hyperpress_breadcrumb_newsletter( $breadcrumbs ) {
		$hyper_newsletter = 'newsletter';
		if ( is_singular( $hyper_newsletter ) || is_tax( $hyper_newsletter . '_season' ) || is_post_type_archive( $hyper_newsletter ) ) {
			$hyper_newsletter_data = get_post_type_object( $hyper_newsletter );
			$newsletterLabel       = $hyper_newsletter_data->labels->name;
			if ( is_post_type_archive( $hyper_newsletter ) ) {
				$breadcrumbs[] = [
					"title"   => $newsletterLabel,
					"classes" => [
						'li'    => [
							'item-archive-' . $newsletterLabel
						],
						'bread' => [
							'bread-archive-' . $newsletterLabel
						]
					]
				];
			} else {
				$breadcrumbs[] = [
					"title"   => $newsletterLabel,
					'url'     => get_post_type_archive_link( $hyper_newsletter ),
					"classes" => [
						'li'    => [
							'item-seasons',
							'item-seasons-' . $hyper_newsletter
						],
						'bread' => [
							'bread-seasons',
							'bread-seasons-' . $hyper_newsletter
						]
					]
				];
				if ( is_singular( $hyper_newsletter ) ) {
					global $post;
					$seasons = get_the_terms( $post->ID, $hyper_newsletter . '_season' );
					// reverse the array so we use the latest season
					$seasons      = array_reverse( $seasons );
					$seasonName   = $seasons[0]->name;
					$seasonTermID = $seasons[0]->term_id;

					// add newsletter season
					if ( ! empty( $seasons ) ) {
						$breadcrumbs[] = [
							"title"   => $seasonName,
							"url"     => get_category_link( $seasonTermID ),
							"classes" => [
								'li'    => [ 'item-season', 'item-season-' . $seasonTermID ],
								'bread' => [ 'bread-season', 'bread-season-' . $seasonTermID ]
							]
						];
					}

					// add current newsletter
					$breadcrumbs[] = [
						"title"   => get_the_title(),
						"classes" => [
							'li'    => [ 'item-newsletter', 'item-newsletter-' . $post->ID ],
							'bread' => [ 'bread-newsletter', 'bread-newsletter-' . $post->ID ]
						]
					];
				} else {
					$season        = get_queried_object();
					$breadcrumbs[] = [
						"title"   => $season->name,
						"classes" => [
							'li'    => [ 'item-newsletter', 'item-newsletter-' . $season->slug ],
							'bread' => [ 'bread-newsletter', 'bread-newsletter-' . $season->slug ]
						]
					];
				}
			}
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_newsletter' );
endif;
