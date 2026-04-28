<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_breadcrumb_award' ) ) :
	function hyperpress_breadcrumb_award( $breadcrumbs ) {
		$hyper_award = 'award';
		if ( is_singular( $hyper_award ) || is_tax( $hyper_award . '_season' ) || is_post_type_archive( $hyper_award ) ) {
			$hyper_award_data = get_post_type_object( $hyper_award );
			$awardLabel       = $hyper_award_data->labels->name;

			if ( is_post_type_archive( $hyper_award ) ) {
				$breadcrumbs[] = [
					"title"   => $awardLabel,
					"classes" => [
						'li'    => [
							'item-archive-' . $awardLabel
						],
						'bread' => [
							'bread-archive-' . $awardLabel
						]
					]
				];
			} else {
				$breadcrumbs[] = [
					"title"   => $awardLabel,
					'url'     => get_post_type_archive_link( $hyper_award ),
					"classes" => [
						'li'    => [
							'item-seasons',
							'item-seasons-' . $hyper_award
						],
						'bread' => [
							'bread-seasons',
							'bread-seasons-' . $hyper_award
						]
					]
				];
				if ( is_singular( $hyper_award ) ) {
				global $post;
				$seasons      = get_the_terms( $post->ID, $hyper_award . '_season' );
				// reverse the array so we use the latest season
				$seasons      = array_reverse( $seasons );
				$seasonName   = $seasons[0]->name;
				$seasonTermID = $seasons[0]->term_id;

					// Single Newsletter
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
							'li'    => [ 'item-award', 'item-award-' . $post->ID ],
							'bread' => [ 'bread-award', 'bread-award-' . $post->ID ]
						]
					];
				} else {
					$season        = get_queried_object();
					$breadcrumbs[] = [
						"title"   => $season->name,
						"classes" => [
							'li'    => [ 'item-award', 'item-award-' . $season->slug ],
							'bread' => [ 'bread-award', 'bread-award-' . $season->slug ]
						]
					];
				}
			}
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_award' );
endif;
