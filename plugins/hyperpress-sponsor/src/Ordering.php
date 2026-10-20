<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

use WP_Query;

/**
 * Orders the sponsor archive (and its season-scoped variant) by the sponsor's newest season, then its
 * highest tier, then menu order, title and ID. Only the main query of a sponsor archive without an
 * explicit orderby is touched.
 */
final class Ordering {

	private const SEASON_TAXONOMY = 'hyper_season';

	public static function register(): void {
		add_filter( 'posts_clauses', array( self::class, 'order_clauses' ), 10, 2 );
	}

	/**
	 * Replace the ORDER BY of the sponsor archive's main query.
	 *
	 * @param array    $clauses SQL clauses of the query.
	 * @param WP_Query $query   The query.
	 */
	public static function order_clauses( $clauses, $query ): array {
		if ( ! $query instanceof WP_Query
			|| is_admin()
			|| ! $query->is_main_query()
			|| ! $query->is_post_type_archive( Plugin::POST_TYPE )
			|| '' !== (string) $query->get( 'orderby' )
		) {
			return (array) $clauses;
		}

		global $wpdb;

		// Newest season: the highest trailing number of the sponsor's seasons (same rule as the season
		// plugin's SeasonOrder). A season without a number counts as -1, so numbered seasons come first
		// and a sponsor without any season (NULL) comes last under DESC.
		$season = $wpdb->prepare(
			"( SELECT MAX( CASE WHEN st.name REGEXP '^(.* )?[0-9]+$' THEN CAST( SUBSTRING_INDEX( st.name, ' ', -1 ) AS SIGNED ) ELSE -1 END )
				FROM {$wpdb->term_relationships} str
				INNER JOIN {$wpdb->term_taxonomy} stt ON stt.term_taxonomy_id = str.term_taxonomy_id AND stt.taxonomy = %s
				INNER JOIN {$wpdb->terms} st ON st.term_id = stt.term_id
				WHERE str.object_id = {$wpdb->posts}.ID )",
			self::SEASON_TAXONOMY
		);

		// Highest tier: the greatest tier slug (level-4 > level-1), like the sponsor widget; NULL sorts last.
		$tier = $wpdb->prepare(
			"( SELECT MAX( tt2.slug )
				FROM {$wpdb->term_relationships} ttr
				INNER JOIN {$wpdb->term_taxonomy} ttt ON ttt.term_taxonomy_id = ttr.term_taxonomy_id AND ttt.taxonomy = %s
				INNER JOIN {$wpdb->terms} tt2 ON tt2.term_id = ttt.term_id
				WHERE ttr.object_id = {$wpdb->posts}.ID )",
			Plugin::TIER
		);

		$clauses            = (array) $clauses;
		$clauses['orderby'] = "{$season} DESC, {$tier} DESC, {$wpdb->posts}.menu_order ASC, {$wpdb->posts}.post_title ASC, {$wpdb->posts}.ID ASC";

		return $clauses;
	}
}
