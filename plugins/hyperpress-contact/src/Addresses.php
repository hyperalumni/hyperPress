<?php
namespace HyperPress\Contact;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Sanitizes the repeatable address list before it is saved.
 *
 * Meta Box calls a cloneable group's sanitize callback once per clone with that clone's
 * associative entry, so the callback is sanitize_entry(). Slugs can only be made unique
 * across the whole list, which normalize_option() does when the option is saved.
 */
final class Addresses {

	public static function register(): void {
		add_filter( 'pre_update_option_' . Settings::OPTION, array( self::class, 'normalize_option' ) );
	}

	/**
	 * Makes the slugs unique across the saved address list; any other value passes through.
	 *
	 * @param mixed $value The option value about to be saved.
	 * @return mixed
	 */
	public static function normalize_option( $value ) {
		if ( is_array( $value ) && isset( $value[ Settings::ADDRESSES ] ) && is_array( $value[ Settings::ADDRESSES ] ) ) {
			$value[ Settings::ADDRESSES ] = self::sanitize( $value[ Settings::ADDRESSES ] );
		}
		return $value;
	}

	/**
	 * Cleans one address entry: tags stripped, slug generated when empty. Empty entries become array().
	 *
	 * Declares only the first parameter on purpose: Meta Box also passes the field, old value and object id.
	 *
	 * @param mixed $entry Raw submitted entry.
	 * @return array<string,string>
	 */
	public static function sanitize_entry( $entry ): array {
		if ( ! is_array( $entry ) ) {
			return array();
		}

		$clean = array();
		foreach ( Settings::FIELDS as $field ) {
			$raw             = isset( $entry[ $field ] ) && is_scalar( $entry[ $field ] ) ? (string) $entry[ $field ] : '';
			$clean[ $field ] = 'slug' === $field ? self::slug( $raw ) : sanitize_text_field( $raw );
		}

		$has_content = false;
		foreach ( Settings::FIELDS as $field ) {
			if ( 'slug' !== $field && '' !== $clean[ $field ] ) {
				$has_content = true;
				break;
			}
		}
		if ( ! $has_content ) {
			return array();
		}

		if ( '' === $clean['slug'] ) {
			$clean['slug'] = self::slug( $clean['heading'] );
		}
		if ( '' === $clean['slug'] ) {
			$clean['slug'] = 'address';
		}
		return $clean;
	}

	/**
	 * Cleans the whole list: each entry as sanitize_entry(), empty ones dropped, slugs unique, order kept.
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
			$entry = self::sanitize_entry( $group );
			if ( array() === $entry ) {
				continue;
			}

			$base   = $entry['slug'];
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

	/**
	 * A typeable slug: accents removed, and "" when only percent-encoded characters would remain.
	 *
	 * @param string $text Text to turn into a slug.
	 */
	private static function slug( string $text ): string {
		$slug = sanitize_title( remove_accents( $text ) );
		return false !== strpos( $slug, '%' ) ? '' : $slug;
	}
}
