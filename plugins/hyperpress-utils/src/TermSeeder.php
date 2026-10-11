<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Idempotent default-term seeding for a taxonomy.
 *
 * Only creates what is missing. Existing terms are matched by slug, then name, anywhere in the
 * taxonomy, and are never renamed, re-parented or deleted.
 */
final class TermSeeder {

	/**
	 * Create any missing terms (and children) in a registered taxonomy.
	 *
	 * @param string $taxonomy Taxonomy key. Silently skipped when it is not registered.
	 * @param array  $terms    List of entries: a name string, or `name => array( child names )`.
	 *                         Children are one level deep and may be given as a name list.
	 */
	public static function seed( string $taxonomy, array $terms ): void {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}
		foreach ( $terms as $key => $value ) {
			if ( is_array( $value ) ) {
				$parent_id = self::ensure_term( $taxonomy, (string) $key, 0 );
				if ( ! $parent_id ) {
					continue;
				}
				foreach ( $value as $child ) {
					self::ensure_term( $taxonomy, (string) $child, $parent_id );
				}
			} else {
				self::ensure_term( $taxonomy, (string) $value, 0 );
			}
		}
	}

	/**
	 * Seed once per seed version, so terms an admin later deletes are not resurrected.
	 * Call from `init` (after the taxonomy registers) and from activation.
	 *
	 * @param string $option   Option storing the last seeded version, e.g. hyperpress_competition_terms_seeded.
	 * @param string $version  Seed version; bump it to seed newly added defaults.
	 * @param string $taxonomy Taxonomy key.
	 * @param array  $terms    See seed().
	 */
	public static function ensure( string $option, string $version, string $taxonomy, array $terms ): void {
		if ( ! taxonomy_exists( $taxonomy ) || get_option( $option ) === $version ) {
			return;
		}
		self::seed( $taxonomy, $terms );
		update_option( $option, $version );
	}

	/**
	 * ID of the existing term with this name, or of a newly created one; 0 on failure.
	 *
	 * @param string $taxonomy  Taxonomy key.
	 * @param string $name      Term name.
	 * @param int    $parent_id Parent term ID for a new term; 0 for top level.
	 */
	private static function ensure_term( string $taxonomy, string $name, int $parent_id ): int {
		$existing = get_term_by( 'slug', sanitize_title( $name ), $taxonomy );
		if ( ! $existing ) {
			$existing = get_term_by( 'name', $name, $taxonomy );
		}
		if ( $existing ) {
			return (int) $existing->term_id;
		}
		$result = wp_insert_term( $name, $taxonomy, array( 'parent' => $parent_id ) );

		return is_wp_error( $result ) ? 0 : (int) $result['term_id'];
	}
}
