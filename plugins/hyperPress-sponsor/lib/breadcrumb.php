<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_breadcrumb_sponsor' ) ) :
	function hyperpress_breadcrumb_sponsor( $breadcrumbs ) {
		$hyper_sponsor = 'sponsor';
		if ( is_singular( $hyper_sponsor ) || is_tax( $hyper_sponsor . '_season' ) || is_post_type_archive( $hyper_sponsor ) ) {
			$hyper_sponsor_data = get_post_type_object( $hyper_sponsor );
			$sponsorLabel       = $hyper_sponsor_data->labels->name;

			if ( is_post_type_archive( $hyper_sponsor ) ) {
				$breadcrumbs[] = [
					"title"   => $sponsorLabel,
					"classes" => [
						'li'    => [
							'item-archive-' . $sponsorLabel
						],
						'bread' => [
							'bread-archive-' . $sponsorLabel
						]
					]
				];
			} else {
				$breadcrumbs[] = [
					"title"   => $sponsorLabel,
					'url'     => get_post_type_archive_link( $hyper_sponsor ),
					"classes" => [
						'li'    => [
							'item-seasons',
							'item-seasons-' . $hyper_sponsor
						],
						'bread' => [
							'bread-seasons',
							'bread-seasons-' . $hyper_sponsor
						]
					]
				];

				if ( is_singular( $hyper_sponsor ) ) {
					global $post;
					$seasons = get_the_terms( $post->ID, $hyper_sponsor . '_season' );
					if ( ! empty( $seasons ) ) {
						// reverse the array so we use the latest season
						$seasons      = array_reverse( $seasons );
						$seasonName   = $seasons[0]->name;
						$seasonTermID = $seasons[0]->term_id;
					}

					// add sponsor season
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
					// add current sponsor
					$breadcrumbs[] = [
						"title"   => get_the_title(),
						"classes" => [
							'li'    => [
								'item-sponsor',
								'item-sponsor-' . $post->ID
							],
							'bread' => [
								'bread-sponsor',
								'bread-sponsor-' . $post->ID
							]
						]
					];
				} else if ( is_tax( $hyper_sponsor . '_season' ) ) {
					$breadcrumbs[] = [
						"title"   => single_term_title( '', false ),
						"classes" => [
							'li'    => [
								'item-sponsor',
								'item-sponsor-' . get_queried_object()->slug
							],
							'bread' => [
								'bread-sponsor',
								'bread-sponsor-' . get_queried_object()->slug
							]
						]
					];
				}
			}

		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_sponsor' );
endif;
