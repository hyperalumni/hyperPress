<?php
namespace HyperPress\Program;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The Program settings page option, its defaults and accessors.
 */
final class Settings {

	public const OPTION     = 'hyperpress_program';
	public const POST_TYPES = 'hyperpress_program_post_types';

	/**
	 * Default values, keyed by field id.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			self::POST_TYPES => array( 'post' ),
		);
	}

	/**
	 * Filter on default_option_hyperpress_program: the defaults, unless the caller passed its own.
	 *
	 * @param mixed  $fallback       The default value.
	 * @param string $option         Option name.
	 * @param bool   $passed_default Whether get_option() was given an explicit default.
	 * @return mixed
	 */
	public static function default_option( $fallback, $option = '', $passed_default = false ) {
		unset( $option );
		return $passed_default ? $fallback : self::defaults();
	}

	/**
	 * Post types that use the Program taxonomy.
	 *
	 * @return string[]
	 */
	public static function post_types(): array {
		$option = get_option( self::OPTION, array() );
		$saved  = is_array( $option ) ? $option : array();
		if ( ! isset( $saved[ self::POST_TYPES ] ) || ! is_array( $saved[ self::POST_TYPES ] ) ) {
			return self::defaults()[ self::POST_TYPES ];
		}

		return array_values(
			array_filter(
				$saved[ self::POST_TYPES ],
				static fn( $post_type ) => is_string( $post_type ) && '' !== $post_type
			)
		);
	}
}
