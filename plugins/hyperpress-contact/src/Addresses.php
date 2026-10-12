<?php
namespace HyperPress\Contact;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Sanitizes the repeatable address list before it is saved.
 */
final class Addresses {

	/**
	 * Cleans the submitted address groups: tags stripped, slugs unique, empty entries dropped.
	 *
	 * @param mixed $groups Raw submitted groups.
	 * @return array<int,array<string,string>>
	 */
	public static function sanitize( $groups ): array {
		if ( ! is_array( $groups ) ) {
			return array();
		}

		$clean = array();
		$used  = array();
		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$entry = array();
			foreach ( Settings::FIELDS as $field ) {
				$raw             = isset( $group[ $field ] ) && is_scalar( $group[ $field ] ) ? (string) $group[ $field ] : '';
				$entry[ $field ] = 'slug' === $field ? sanitize_title( $raw ) : sanitize_text_field( $raw );
			}

			$has_content = false;
			foreach ( Settings::FIELDS as $field ) {
				if ( 'slug' !== $field && '' !== $entry[ $field ] ) {
					$has_content = true;
					break;
				}
			}
			if ( ! $has_content ) {
				continue;
			}

			$base = $entry['slug'];
			if ( '' === $base ) {
				$base = sanitize_title( $entry['heading'] );
			}
			if ( '' === $base ) {
				$base = 'address';
			}

			$slug   = $base;
			$suffix = 2;
			while ( isset( $used[ $slug ] ) ) {
				$slug = $base . '-' . $suffix;
				++$suffix;
			}
			$used[ $slug ] = true;

			$entry['slug'] = $slug;
			$clean[]       = $entry;
		}
		return $clean;
	}
}
