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
	 * @param string $taxonomy Taxonomy key.
	 * @return \WP_Term|null The term, or null for an empty var, unknown slug or unregistered taxonomy.
	 */
	public static function get( string $taxonomy ): ?\WP_Term {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return null;
		}
		$value = get_query_var( $taxonomy );
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		// A hierarchical query var can hold a path such as "qualifying/district"; the last segment is the term.
		$slug = is_taxonomy_hierarchical( $taxonomy ) ? wp_basename( $value ) : $value;
		$term = get_term_by( 'slug', $slug, $taxonomy );

		return $term instanceof \WP_Term ? $term : null;
	}
}
