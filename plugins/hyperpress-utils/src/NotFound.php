<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Answers 404 for a request whose taxonomy query var names only terms that do not exist.
 *
 * Core 404s a taxonomy archive when its term is unknown (`/competition-types/nope/`) but not a post-type archive
 * filtered by an unknown term (`/seasons/season-31/competitions/nope/`, `?hyper_competition_type=nope`), which would
 * be an empty 200 page.
 */
final class NotFound {

	/**
	 * Make the main query a 404 when one of these taxonomies has an unknown term in the request.
	 *
	 * @param string[] $taxonomies Taxonomy keys.
	 */
	public static function register( array $taxonomies ): void {
		add_filter(
			'pre_handle_404',
			static function ( $preempt, $wp_query ) use ( $taxonomies ) {
				if ( true === $preempt || ! $wp_query instanceof \WP_Query || ! $wp_query->is_main_query() || ! self::has_unknown_term( $taxonomies ) ) {
					return $preempt;
				}
				$wp_query->set_404();
				status_header( 404 );
				nocache_headers();

				return true;
			},
			10,
			2
		);
	}

	/**
	 * Whether a listed taxonomy has a non-empty string in its own query var but no term resolves from it.
	 *
	 * Unregistered taxonomies, ones without a query var, and array values are skipped. A list such as "nope,district"
	 * is known when any piece resolves (see RequestTerm::get()).
	 *
	 * @param string[] $taxonomies Taxonomy keys.
	 */
	public static function has_unknown_term( array $taxonomies ): bool {
		foreach ( $taxonomies as $taxonomy ) {
			$object = get_taxonomy( (string) $taxonomy );
			if ( ! $object || ! is_string( $object->query_var ) || '' === $object->query_var ) {
				continue;
			}
			$value = get_query_var( $object->query_var );
			if ( ! is_string( $value ) || '' === $value ) {
				continue;
			}
			if ( null === RequestTerm::get( (string) $taxonomy ) ) {
				return true;
			}
		}

		return false;
	}
}
