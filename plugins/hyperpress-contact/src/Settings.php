<?php
namespace HyperPress\Contact;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Reads the Contact settings page option.
 */
final class Settings {

	public const OPTION        = 'hyperpress_contact';
	public const ADDRESSES     = 'hyperpress_contact_addresses';
	public const MAP_ADDRESS   = 'hyperpress_contact_map_address';
	public const MAP           = 'hyperpress_contact_map';
	public const FIELDS        = array( 'slug', 'heading', 'name', 'extra', 'street', 'city', 'state', 'zip', 'phone' );
	private const DEFAULT_ZOOM = 15;
	private const MAX_ZOOM     = 21;

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
	 * Parses "lat,lng[,zoom]". Pure: no WordPress functions.
	 *
	 * @param string $value Raw setting value.
	 * @return array{lat:float,lng:float,zoom:int}|null
	 */
	public static function parse_map( string $value ): ?array {
		$parts = array_map( 'trim', explode( ',', $value ) );
		if ( count( $parts ) < 2 || count( $parts ) > 3 ) {
			return null;
		}
		foreach ( $parts as $part ) {
			if ( ! is_numeric( $part ) ) {
				return null;
			}
		}

		$lat = (float) $parts[0];
		$lng = (float) $parts[1];
		if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
			return null;
		}

		$zoom = isset( $parts[2] ) ? (int) $parts[2] : self::DEFAULT_ZOOM;
		$zoom = max( 0, min( self::MAX_ZOOM, $zoom ) );

		return array(
			'lat'  => $lat,
			'lng'  => $lng,
			'zoom' => $zoom,
		);
	}
}
