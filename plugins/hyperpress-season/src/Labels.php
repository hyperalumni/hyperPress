<?php
namespace HyperPress\Season;

use HyperPress\Label\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the season label with hyperpress-label, when that plugin is active.
 */
final class Labels {

	public static function register(): void {
		add_action( 'hyperpress_label_register', array( self::class, 'register_labels' ) );
	}

	/**
	 * Add the season label to the registry.
	 *
	 * @param Registry $registry Label registry.
	 */
	public static function register_labels( Registry $registry ): void {
		$registry->add(
			'season.posted_during',
			array(
				'group'              => 'Season',
				'title'              => 'Posted During',
				'callback'           => array( self::class, 'season_label' ),
				'default_post_types' => \HyperPress\Utils\TaxonomyPostTypes::for_taxonomy( Plugin::TAXONOMY ),
				'priority'           => 30,
			)
		);
	}

	/**
	 * Label for the newest season of the current post; empty when it has none.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function season_label(): array {
		$seasons = SortedSeasons::get();
		if ( empty( $seasons ) ) {
			return array();
		}
		$season = reset( $seasons );
		$url    = get_term_link( $season );

		return array(
			array(
				'label'   => $season->name,
				'title'   => 'Posted During',
				'classes' => array( 'warning' ),
				'url'     => is_wp_error( $url ) ? '' : $url,
				'rel'     => '',
			),
		);
	}
}
