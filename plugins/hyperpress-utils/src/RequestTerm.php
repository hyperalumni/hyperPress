<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The term a taxonomy has in the current request.
 *
 * Note that get_queried_object() returns one arbitrary term when a request carries two taxonomies, so this reads the
 * taxonomy's own query var instead. It does not check is_tax(); callers keep their own guard.
 */
final class RequestTerm {

	/**
	 * Resolve the request's term for a taxonomy.
	 *
	 * Reads the taxonomy's own query var (which need not equal its key). Like WP_Query::parse_tax_query(), it accepts a
	 * list: slugs separated by commas (OR) or by a literal plus sign (AND), each trimmed, empty pieces ignored. A
	 * hierarchical taxonomy's piece may be a path such as "qualifying/district"; its last segment is the slug. With a
	 * list, the first slug (in the order given, not sorted as core does) that resolves to a term wins, so an unknown
	 * first slug falls through to the next known one.
	 *
	 * @param string $taxonomy Taxonomy key.
	 * @return \WP_Term|null The term, or null for an empty or array-valued var, only unknown slugs, an unregistered
	 *                       taxonomy or one without a query var.
	 */
	public static function get( string $taxonomy ): ?\WP_Term {
		$object = get_taxonomy( $taxonomy );
		if ( ! $object || ! is_string( $object->query_var ) || '' === $object->query_var ) {
			return null;
		}
		$value = get_query_var( $object->query_var );
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		$hierarchical = is_taxonomy_hierarchical( $taxonomy );
		foreach ( preg_split( '/[,+]+/', $value ) as $piece ) {
			$slug = trim( $piece );
			if ( $hierarchical ) {
				$slug = trim( wp_basename( $slug ) );
			}
			if ( '' === $slug ) {
				continue;
			}
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term instanceof \WP_Term ) {
				return $term;
			}
		}

		return null;
	}
}
