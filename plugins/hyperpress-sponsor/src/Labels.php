<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

use HyperPress\Season\SortedSeasons;

final class Labels {

	public static function register(): void {
		add_filter( 'hyperpress_labels_content', array( self::class, 'labels_sponsor' ) );
	}

	public static function labels_sponsor( $labels ) {
		$hyper_sponsor = Plugin::POST_TYPE;
		if ( is_singular( $hyper_sponsor ) || is_post_type_archive( $hyper_sponsor ) || ( is_main_query() && get_post_type() === $hyper_sponsor ) ) {
			// Get season information.
			$seasons = SortedSeasons::get();
			if ( ! empty( $seasons ) ) {
				$season = $seasons[0];
				$count  = count( $seasons );
			}

			if ( ! empty( $season ) ) {
				$season_link = get_term_link( $season );
				$labels[]    = array(
					'label'   => $season->name,
					'title'   => 'Last Sponsored',
					'classes' => array( 'warning' ),
					'url'     => is_wp_error( $season_link ) ? '' : $season_link,
					'rel'     => '',
				);
			}

			$level = rwmb_meta( 'hyperpress_sponsor_tier' );
			if ( ! empty( $level ) ) {
				$labels[] = array(
					'label'   => $level->name,
					'title'   => 'Sponsorship Tier' . ( $level->description ? ': ' . $level->description : '' ),
					'classes' => array( 'info' ),
					'url'     => '',
					'rel'     => '',
				);
			}

			if ( ! empty( $count ) ) {
				$labels[] = array(
					'label'   => $count . ' Season' . ( $count > 1 ? 's' : '' ),
					'title'   => 'Sponsored For',
					'classes' => array( 'primary' ),
					'url'     => '',
					'rel'     => '',
				);
			}
		}

		return $labels;
	}
}
