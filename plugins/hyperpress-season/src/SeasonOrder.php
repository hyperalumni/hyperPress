<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

/**
 * Lists seasons newest first. Term names are "Season 1" ... "Season 32", which WordPress orders as
 * text (Season 1, Season 10, ..., Season 2). This reorders every hyper_season term query that asks
 * for orderby name, which is also what the REST API, the post edit term checklists and Quick Edit use
 * by default. Any other explicit orderby (term_id, slug, count, ...) is left alone.
 */
final class SeasonOrder {

	/**
	 * Numbered names first (highest number first), then any name without a trailing number by name.
	 */
	private const ORDER_BY = "ORDER BY (t.name REGEXP '^(.* )?[0-9]+$') DESC, CAST(SUBSTRING_INDEX(t.name, ' ', -1) AS UNSIGNED) DESC, t.name ASC, t.term_id ASC";

	public static function register(): void {
		add_filter( 'terms_clauses', array( self::class, 'order_clauses' ), 10, 3 );
	}

	/**
	 * Replace the text ordering by name with the newest-first one.
	 *
	 * @param array $clauses    SQL clauses of the term query.
	 * @param array $taxonomies Queried taxonomies.
	 * @param array $args       Term query arguments.
	 */
	public static function order_clauses( $clauses, $taxonomies, $args ): array {
		if ( array( Plugin::TAXONOMY ) !== array_values( (array) $taxonomies )
			|| 'name' !== strtolower( (string) ( $args['orderby'] ?? '' ) )
			|| 'ORDER BY t.name' !== ( $clauses['orderby'] ?? '' )
		) {
			return $clauses;
		}

		$clauses['orderby'] = self::ORDER_BY;
		$clauses['order']   = '';

		return $clauses;
	}
}
