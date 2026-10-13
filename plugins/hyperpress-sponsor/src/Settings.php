<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The Sponsor settings page option, its defaults and accessors.
 */
final class Settings {

	public const OPTION                  = 'hyperpress_sponsor';
	public const BANNER_SUBTITLE         = 'hyperpress_sponsor_banner_subtitle';
	public const BANNER_SUBTITLE_DEFAULT = 'Helping Make HYPER Happen';

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
	 * Filter on default_option_hyperpress_sponsor: the defaults, unless the caller passed its own.
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
	 * The sponsor banner subtitle: the saved text, or the default when nothing valid is saved.
	 */
	public static function banner_subtitle(): string {
		$option = get_option( self::OPTION, array() );
		$value  = is_array( $option ) ? ( $option[ self::BANNER_SUBTITLE ] ?? null ) : null;
		return is_string( $value ) ? $value : self::BANNER_SUBTITLE_DEFAULT;
	}
}
