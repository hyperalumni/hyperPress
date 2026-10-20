<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

use WP_Query;

/**
 * Orders the sponsor archive (and its season-scoped variant) by the sponsor's newest season, then its
 * highest tier, then menu order, title and ID. Inside a season-scoped archive every sponsor belongs to
 * that season, so the season key is dropped and the tier leads. Only the main query of a sponsor archive without an
 * explicit orderby is touched.
 */
final class Ordering {

	private const SEASON_TAXONOMY = 'hyper_season';

	/** Query var that opts a secondary sponsor query into this ordering. */
	public const ORDER_VAR = 'hyperpress_sponsor_order';

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
			|| $query->is_feed()
		) {
			return (array) $clauses;
		}

		// The main query of the sponsor archive, or a secondary sponsor query that opted in through ORDER_VAR.
		$applies = $query->is_main_query()
			? $query->is_post_type_archive( Plugin::POST_TYPE )
			: ( (bool) $query->get( self::ORDER_VAR ) && Plugin::POST_TYPE === $query->get( 'post_type' ) );
		$orderby = $query->get( 'orderby' );
		if ( ! $applies || is_array( $orderby ) || '' !== (string) $orderby ) {
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

		// Highest-ranking tier, by the rule of tier_compare(): the number after the last hyphen of the slug
		// (level-10 > level-4), then slugs without a number by slug. The key is '1' + zero-padded number +
		// slug for numbered tiers and '0' + slug for the others, so MAX() picks the best; NULL sorts last.
		$tier = $wpdb->prepare(
			"( SELECT MAX( CASE WHEN tt2.slug REGEXP '^(.*-)?[0-9]+$' THEN CONCAT( '1', LPAD( SUBSTRING_INDEX( tt2.slug, '-', -1 ), 20, '0' ), tt2.slug ) ELSE CONCAT( '0', tt2.slug ) END )
				FROM {$wpdb->term_relationships} ttr
				INNER JOIN {$wpdb->term_taxonomy} ttt ON ttt.term_taxonomy_id = ttr.term_taxonomy_id AND ttt.taxonomy = %s
				INNER JOIN {$wpdb->terms} tt2 ON tt2.term_id = ttt.term_id
				WHERE ttr.object_id = {$wpdb->posts}.ID )",
			Plugin::TIER
		);

		// In a season-scoped archive (/seasons/<slug>/sponsors/) all sponsors share the season: skip the
		// season key so a sponsor with a newer extra season does not jump above the others.
		$season_key = ! self::is_season_scoped( $query ) ? "{$season} DESC, " : '';

		$clauses            = (array) $clauses;
		$clauses['orderby'] = "{$season_key}{$tier} DESC, {$wpdb->posts}.menu_order ASC, {$wpdb->posts}.post_title ASC, {$wpdb->posts}.ID ASC";

		return $clauses;
	}

	/**
	 * Sort comparator for tier terms (or slugs), highest first: tiers with a trailing number (after the last
	 * hyphen, or the whole slug) before the others, higher number first, then slug descending. The SQL in
	 * order_clauses() ranks tiers the same way.
	 *
	 * @param \WP_Term|string $a Tier term or slug.
	 * @param \WP_Term|string $b Tier term or slug.
	 */
	public static function tier_compare( $a, $b ): int {
		return self::tier_rank( $b ) <=> self::tier_rank( $a );
	}

	/**
	 * Sort key of a tier, compared element by element.
	 *
	 * @param \WP_Term|string $tier Tier term or slug.
	 * @return array{0:int,1:int,2:string} Numbered flag, number, slug.
	 */
	private static function tier_rank( $tier ): array {
		$slug = $tier instanceof \WP_Term ? $tier->slug : (string) $tier;

		return preg_match( '/^(?:.*-)?([0-9]+)$/', $slug, $m ) ? array( 1, (int) $m[1], $slug ) : array( 0, 0, $slug );
	}

	/**
	 * Sort comparator for season terms (or names), newest first: the trailing number of a name matching
	 * `^(.* )?[0-9]+$` (higher first), seasons without a number after all numbered ones, then name descending.
	 * The SQL in order_clauses() ranks seasons by the same number.
	 *
	 * @param \WP_Term|string $a Season term or name.
	 * @param \WP_Term|string $b Season term or name.
	 */
	public static function season_compare( $a, $b ): int {
		return self::season_rank( $b ) <=> self::season_rank( $a );
	}

	/**
	 * Sort key of a season, compared element by element.
	 *
	 * @param \WP_Term|string $season Season term or name.
	 * @return array{0:int,1:string} Trailing number (-1 when absent), name.
	 */
	private static function season_rank( $season ): array {
		$name = $season instanceof \WP_Term ? $season->name : (string) $season;

		return preg_match( '/^(?:.* )?([0-9]+)$/', $name, $m ) ? array( (int) $m[1], $name ) : array( -1, $name );
	}

	/**
	 * Whether the query is limited to one season (/seasons/<slug>/sponsors/), judged from the query's own
	 * season query var: a comma/plus separated list in which at least one slug is an existing season.
	 *
	 * @param WP_Query $query The query.
	 */
	public static function is_season_scoped( WP_Query $query ): bool {
		$taxonomy = get_taxonomy( self::SEASON_TAXONOMY );
		if ( ! $taxonomy || ! is_string( $taxonomy->query_var ) || '' === $taxonomy->query_var ) {
			return false;
		}
		$value = $query->get( $taxonomy->query_var );
		if ( ! is_string( $value ) || '' === $value ) {
			return false;
		}
		foreach ( preg_split( '/[,+]+/', $value ) as $piece ) {
			$slug = trim( $piece );
			if ( is_taxonomy_hierarchical( self::SEASON_TAXONOMY ) ) {
				$slug = trim( wp_basename( $slug ) );
			}
			if ( '' !== $slug && get_term_by( 'slug', $slug, self::SEASON_TAXONOMY ) instanceof \WP_Term ) {
				return true;
			}
		}

		return false;
	}
}
