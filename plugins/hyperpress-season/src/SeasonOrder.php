<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

/**
 * Lists seasons newest first. Term names are "Season 1" ... "Season 32", which WordPress orders as
 * text (Season 1, Season 10, ..., Season 2). This reorders every hyper_season term query that asks
 * for orderby name, which is also what the REST API, the post edit term checklists and Quick Edit use
 * by default. Any other explicit orderby (term_id, slug, count, ...) is left alone. The terms a post
 * lists (get_the_terms(), e.g. the wp-admin Season column) are also sorted, because core primes that
 * cache with one query over all of the post type's taxonomies, which the clause filter skips.
 */
final class SeasonOrder {

	/**
	 * Numbered names first (highest number first), then any name without a trailing number by name.
	 */
	private const ORDER_BY = "ORDER BY (t.name REGEXP '^(.* )?[0-9]+$') DESC, CAST(SUBSTRING_INDEX(t.name, ' ', -1) AS UNSIGNED) DESC, t.name ASC, t.term_id ASC";

	public static function register(): void {
		add_filter( 'terms_clauses', array( self::class, 'order_clauses' ), 10, 3 );
		add_filter( 'get_the_terms', array( self::class, 'sort_terms' ), 10, 3 );
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

	/**
	 * Sort the seasons of a post like ORDER_BY: numbered names by trailing number descending, then
	 * names without a number; ties by name, then term id.
	 *
	 * @param mixed  $terms    Terms of the post, or false/WP_Error.
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return mixed
	 */
	public static function sort_terms( $terms, $post_id, $taxonomy ) {
		if ( Plugin::TAXONOMY !== $taxonomy || ! is_array( $terms ) || array() === $terms ) {
			return $terms;
		}

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				return $terms;
			}
		}

		usort(
			$terms,
			static function ( $a, $b ): int {
				$na = self::trailing_number( $a->name );
				$nb = self::trailing_number( $b->name );

				if ( ( null === $na ) !== ( null === $nb ) ) {
					return null === $na ? 1 : -1;
				}

				// Number descending (swapped sides), then name and term id ascending.
				return array( $nb, strcasecmp( $a->name, $b->name ), $a->term_id ) <=> array( $na, 0, $b->term_id );
			}
		);

		return $terms;
	}

	/**
	 * The number a name ends with after a space (or the whole name), or null when it has none.
	 *
	 * @param string $name Term name.
	 */
	private static function trailing_number( string $name ): ?int {
		return preg_match( '/^(.* )?([0-9]+)$/', $name, $m ) ? (int) $m[2] : null;
	}
}
