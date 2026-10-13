<?php
/**
 * The Labels settings page option and its helpers.
 *
 * @package HyperPress\Label
 */

namespace HyperPress\Label;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The option behind the Labels settings page, the listed post types and the save normalizer.
 */
final class Settings {

	public const OPTION = 'hyperpress_label';

	/**
	 * Hidden field posted with every page save; see normalize().
	 */
	public const MARKER = 'hyperpress_label_saved';

	/**
	 * Post types that get fields, as name => plural label.
	 *
	 * Public types except `attachment`, plus any type named in a label's default types.
	 *
	 * @return array<string,string>
	 */
	public static function post_types(): array {
		$types = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $name => $object ) {
			if ( 'attachment' !== $name ) {
				$types[ $name ] = (string) $object->labels->name;
			}
		}

		$registry = Registry::instance();
		foreach ( $registry->ids() as $id ) {
			foreach ( $registry->default_post_types( $id ) as $name ) {
				if ( ! is_string( $name ) || '' === $name || isset( $types[ $name ] ) ) {
					continue;
				}
				$object         = get_post_type_object( $name );
				$types[ $name ] = null !== $object ? (string) $object->labels->name : $name;
			}
		}

		return $types;
	}

	/**
	 * The saved option; an empty array when it is missing or not an array.
	 *
	 * @return array<string,mixed>
	 */
	public static function saved(): array {
		$option = get_option( self::OPTION );
		return is_array( $option ) ? $option : array();
	}

	/**
	 * Sanitize the chosen label IDs: keep registered IDs only, without duplicates.
	 *
	 * @param mixed $value Submitted value.
	 * @return string[]
	 */
	public static function sanitize_labels( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$registered = Registry::instance()->ids();
		$clean      = array();
		foreach ( $value as $id ) {
			if ( is_string( $id ) && in_array( $id, $registered, true ) && ! in_array( $id, $clean, true ) ) {
				$clean[] = $id;
			}
		}
		return $clean;
	}

	/**
	 * Make cleared values explicit when the page is saved.
	 *
	 * Meta Box drops the key of an empty field but keeps the option row, so a cleared checkbox
	 * or list would look like "never configured". A save from the page carries the marker; then
	 * every listed post type without a key gets `false` / `array()`. Other writes are untouched.
	 *
	 * @param mixed $value Option value about to be saved.
	 * @return mixed
	 */
	public static function normalize( $value ) {
		if ( ! is_array( $value ) || ! array_key_exists( self::MARKER, $value ) ) {
			return $value;
		}
		unset( $value[ self::MARKER ] );

		foreach ( array_keys( self::post_types() ) as $type ) {
			$enabled = Resolver::enabled_key( $type );
			$labels  = Resolver::labels_key( $type );
			if ( ! array_key_exists( $enabled, $value ) ) {
				$value[ $enabled ] = false;
			}
			if ( ! array_key_exists( $labels, $value ) ) {
				$value[ $labels ] = array();
			}
		}

		return $value;
	}
}
