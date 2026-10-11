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
	 * @param array  $terms    List of entries, each one of:
	 *                         - a name string: `'Alpha'`;
	 *                         - `name => array( entries )` for a term with children: `'Group' => array( 'One', 'Two' )`;
	 *                         - a spec array with a `name` key, an optional `description` and optional
	 *                           `children` (a list of entries in any of these formats):
	 *                           `array( 'name' => 'FIRST', 'description' => '...', 'children' => array( 'FRC' ) )`.
	 *                         A description is set only on a new term or an existing term whose description is empty.
	 */
	public static function seed( string $taxonomy, array $terms ): void {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}
		self::seed_entries( $taxonomy, $terms, 0 );
	}

	/**
	 * Seed a list of entries under a parent (0 for top level).
	 *
	 * @param string $taxonomy  Taxonomy key.
	 * @param array  $entries   Entries in any format accepted by seed().
	 * @param int    $parent_id Parent term ID for new terms; 0 for top level.
	 */
	private static function seed_entries( string $taxonomy, array $entries, int $parent_id ): void {
		foreach ( $entries as $key => $value ) {
			$description = '';
			$children    = array();
			if ( is_array( $value ) && isset( $value['name'] ) ) {
				$name        = (string) $value['name'];
				$description = (string) ( $value['description'] ?? '' );
				$children    = (array) ( $value['children'] ?? array() );
			} elseif ( is_array( $value ) ) {
				$name     = (string) $key;
				$children = $value;
			} else {
				$name = (string) $value;
			}
			$term_id = self::ensure_term( $taxonomy, $name, $parent_id, $description );
			if ( $term_id && $children ) {
				self::seed_entries( $taxonomy, $children, $term_id );
			}
		}
	}

	/**
	 * Seed once per distinct set of terms, so terms an admin later deletes are not resurrected
	 * until the lists change. The seeded version is a hash of the terms, so editing a default list
	 * in code seeds again automatically. Call from `init` (after the taxonomies register) and from
	 * activation.
	 *
	 * Nothing is seeded or recorded while any listed taxonomy is unregistered, so it retries later.
	 *
	 * @param string $option           Option storing the hash of the last seeded terms, e.g. hyperpress_competition_terms_seeded.
	 * @param array  $terms_by_taxonomy Map of taxonomy key => terms (see seed()).
	 */
	public static function ensure( string $option, array $terms_by_taxonomy ): void {
		$hash = md5( (string) wp_json_encode( $terms_by_taxonomy ) );
		if ( get_option( $option ) === $hash ) {
			return;
		}
		foreach ( array_keys( $terms_by_taxonomy ) as $taxonomy ) {
			if ( ! taxonomy_exists( (string) $taxonomy ) ) {
				return;
			}
		}
		foreach ( $terms_by_taxonomy as $taxonomy => $terms ) {
			self::seed( (string) $taxonomy, $terms );
		}
		update_option( $option, $hash );
	}

	/**
	 * ID of the existing term with this name, or of a newly created one; 0 on failure.
	 *
	 * @param string $taxonomy  Taxonomy key.
	 * @param string $name      Term name.
	 * @param int    $parent_id   Parent term ID for a new term; 0 for top level.
	 * @param string $description Description for a new term, or for an existing term that has none.
	 */
	private static function ensure_term( string $taxonomy, string $name, int $parent_id, string $description = '' ): int {
		$existing = get_term_by( 'slug', sanitize_title( $name ), $taxonomy );
		if ( ! $existing ) {
			$existing = get_term_by( 'name', $name, $taxonomy );
		}
		if ( $existing ) {
			if ( '' !== $description && '' === $existing->description ) {
				wp_update_term( (int) $existing->term_id, $taxonomy, array( 'description' => $description ) );
			}
			return (int) $existing->term_id;
		}
		$result = wp_insert_term(
			$name,
			$taxonomy,
			array(
				'parent'      => $parent_id,
				'description' => $description,
			)
		);

		return is_wp_error( $result ) ? 0 : (int) $result['term_id'];
	}
}
