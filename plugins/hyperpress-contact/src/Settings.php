<?php
namespace HyperPress\Contact;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Reads the Contact settings page option.
 */
final class Settings {

	public const OPTION      = 'hyperpress_contact';
	public const ADDRESSES   = 'hyperpress_contact_addresses';
	public const MAP_ADDRESS = 'hyperpress_contact_map_address';
	public const MAP         = 'hyperpress_contact_map';
	public const FIELDS      = array( 'slug', 'heading', 'name', 'extra', 'street', 'city', 'state', 'zip', 'phone' );

	/**
	 * All saved addresses, each normalized to the nine string keys.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function addresses(): array {
		$option = get_option( self::OPTION );
		if ( ! is_array( $option ) || ! isset( $option[ self::ADDRESSES ] ) || ! is_array( $option[ self::ADDRESSES ] ) ) {
			return array();
		}

		$addresses = array();
		foreach ( $option[ self::ADDRESSES ] as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$address = array();
			foreach ( self::FIELDS as $field ) {
				$address[ $field ] = isset( $entry[ $field ] ) && is_scalar( $entry[ $field ] ) ? (string) $entry[ $field ] : '';
			}
			$addresses[] = $address;
		}
		return $addresses;
	}

	/**
	 * One saved address by slug.
	 *
	 * @param string $slug Address slug.
	 * @return array<string,string>|null
	 */
	public static function address( string $slug ): ?array {
		foreach ( self::addresses() as $address ) {
			if ( $address['slug'] === $slug ) {
				return $address;
			}
		}
		return null;
	}

	/**
	 * The saved map position.
	 *
	 * @return array{lat:float,lng:float,zoom:int}|null
	 */
	public static function map(): ?array {
		$option = get_option( self::OPTION );
		if ( ! is_array( $option ) || ! isset( $option[ self::MAP ] ) || ! is_string( $option[ self::MAP ] ) ) {
			return null;
		}
		return self::parse_map( $option[ self::MAP ] );
	}

	/**
	 * Parses "lat,lng[,zoom]" through the shared venue map parser (no WordPress calls).
	 *
	 * @param string $value Raw setting value.
	 * @return array{lat:float,lng:float,zoom:int}|null
	 */
	public static function parse_map( string $value ): ?array {
		return \HyperPress\Utils\VenueMap::parse( $value );
	}
}
