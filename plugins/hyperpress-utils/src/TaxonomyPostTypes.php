<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Reads the post types a taxonomy is attached to.
 */
final class TaxonomyPostTypes {

	/**
	 * Post types the taxonomy is attached to right now.
	 *
	 * @param string $taxonomy Taxonomy key.
	 * @return string[] Post type names; empty when the taxonomy is not registered.
	 */
	public static function for_taxonomy( string $taxonomy ): array {
		$object = get_taxonomy( $taxonomy );
		if ( ! $object || ! is_array( $object->object_type ) ) {
			return array();
		}

		return array_values( array_map( 'strval', $object->object_type ) );
	}
}
