<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The Season settings page option, its defaults and accessors.
 */
final class Settings {

	public const OPTION          = 'hyperpress_season';
	public const CURRENT         = 'hyperpress_season_current';
	public const BANNER_SUBTITLE = 'hyperpress_season_banner_subtitle';
	public const POST_TYPES      = 'hyperpress_season_post_types';

	/**
	 * Default values, keyed by field id.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			self::CURRENT         => '',
			self::BANNER_SUBTITLE => '',
			self::POST_TYPES      => array( 'post' ),
		);
	}

	/**
	 * Filter on default_option_hyperpress_season: the defaults, unless the caller passed its own.
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
	 * The saved option as an array (empty when absent or not an array).
	 *
	 * @return array<string,mixed>
	 */
	private static function saved(): array {
		$option = get_option( self::OPTION, array() );
		return is_array( $option ) ? $option : array();
	}

	/**
	 * Slug of the current season, or an empty string.
	 */
	public static function current(): string {
		$value = self::saved()[ self::CURRENT ] ?? '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * The season banner subtitle, or an empty string.
	 */
	public static function banner_subtitle(): string {
		$value = self::saved()[ self::BANNER_SUBTITLE ] ?? '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Post types that use the Season taxonomy.
	 *
	 * The default (posts) applies only while nothing is saved. Once the settings
	 * row exists, a missing key means the list was cleared (Meta Box drops the
	 * key for an empty multi-select), so it gives no post types; a key that
	 * holds something other than an array gives the default.
	 *
	 * @return string[]
	 */
	public static function post_types(): array {
		$option = get_option( self::OPTION, false );
		if ( ! is_array( $option ) ) {
			return self::defaults()[ self::POST_TYPES ];
		}
		if ( ! array_key_exists( self::POST_TYPES, $option ) ) {
			return array();
		}
		if ( ! is_array( $option[ self::POST_TYPES ] ) ) {
			return self::defaults()[ self::POST_TYPES ];
		}

		return array_values(
			array_filter(
				$option[ self::POST_TYPES ],
				static fn( $post_type ) => is_string( $post_type ) && '' !== $post_type
			)
		);
	}
}
