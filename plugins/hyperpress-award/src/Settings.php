<?php
namespace HyperPress\Award;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The Award settings page option, its defaults and accessors.
 */
final class Settings {

	public const OPTION                  = 'hyperpress_award';
	public const BANNER_SUBTITLE         = 'hyperpress_award_banner_subtitle';
	public const BANNER_SUBTITLE_DEFAULT = '';

	/**
	 * Default values, keyed by field id.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			self::BANNER_SUBTITLE => self::BANNER_SUBTITLE_DEFAULT,
		);
	}

	/**
	 * Filter on default_option_hyperpress_award: the defaults, unless the caller passed its own.
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
	 * The award banner subtitle.
	 *
	 * Option row missing: the default_option filter makes get_option() return the defaults, so the default
	 * text shows until the page is saved. Row present but key missing: Meta Box drops the key of an empty
	 * field, so the editor cleared it and the subtitle is ''. Option not an array, or value not a string:
	 * the default.
	 */
	public static function banner_subtitle(): string {
		$option = get_option( self::OPTION );
		if ( ! is_array( $option ) ) {
			return self::BANNER_SUBTITLE_DEFAULT;
		}
		if ( ! array_key_exists( self::BANNER_SUBTITLE, $option ) ) {
			return '';
		}
		return is_string( $option[ self::BANNER_SUBTITLE ] ) ? $option[ self::BANNER_SUBTITLE ] : self::BANNER_SUBTITLE_DEFAULT;
	}
}
