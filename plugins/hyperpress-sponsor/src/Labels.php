<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

use HyperPress\Label\Registry;
use HyperPress\Season\SortedSeasons;
use HyperPress\Utils\Meta;

/**
 * Registers the sponsor labels with hyperpress-label, when that plugin is active.
 */
final class Labels {

	public static function register(): void {
		add_action( 'hyperpress_label_register', array( self::class, 'register_labels' ) );
	}

	/**
	 * Add the sponsor labels to the registry.
	 *
	 * @param Registry $registry Label registry.
	 */
	public static function register_labels( Registry $registry ): void {
		$labels = array(
			'sponsor.last_sponsored' => array( 'Last Sponsored', 'last_sponsored', 10 ),
			'sponsor.tier'           => array( 'Sponsorship Tier', 'tier', 20 ),
			'sponsor.season_count'   => array( 'Sponsored For', 'season_count', 30 ),
		);
		foreach ( $labels as $id => list( $title, $method, $priority ) ) {
			$registry->add(
				$id,
				array(
					'group'              => 'Sponsor',
					'title'              => $title,
					'callback'           => array( self::class, $method ),
					'default_post_types' => array( Plugin::POST_TYPE ),
					'priority'           => $priority,
				)
			);
		}
	}

	/**
	 * Label for the newest season of the current sponsor; empty when it has none.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function last_sponsored(): array {
		$seasons = SortedSeasons::get();
		if ( empty( $seasons ) ) {
			return array();
		}
		$season      = $seasons[0];
		$season_link = get_term_link( $season );

		return array(
			array(
				'label'   => $season->name,
				'title'   => 'Last Sponsored',
				'classes' => array( 'warning' ),
				'url'     => is_wp_error( $season_link ) ? '' : $season_link,
				'rel'     => '',
			),
		);
	}

	/**
	 * Label for the sponsorship tier; empty when none is set.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function tier(): array {
		$level = Meta::get( 'hyperpress_sponsor_tier' );
		if ( empty( $level ) ) {
			return array();
		}

		return array(
			array(
				'label'   => $level->name,
				'title'   => 'Sponsorship Tier' . ( $level->description ? ': ' . $level->description : '' ),
				'classes' => array( 'info' ),
				'url'     => '',
				'rel'     => '',
			),
		);
	}

	/**
	 * Label counting the seasons sponsored; empty when there are none.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function season_count(): array {
		$seasons = SortedSeasons::get();
		if ( empty( $seasons ) ) {
			return array();
		}
		$count = count( $seasons );

		return array(
			array(
				'label'   => $count . ' Season' . ( $count > 1 ? 's' : '' ),
				'title'   => 'Sponsored For',
				'classes' => array( 'primary' ),
				'url'     => '',
				'rel'     => '',
			),
		);
	}
}
