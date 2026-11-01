<?php
namespace HyperPress\Newsletter;

use HyperPress\Utils\ArchiveBannerSettings;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The Newsletter settings page option, its defaults and accessors.
 */
final class Settings {

	public const OPTION                  = 'hyperpress_newsletter';
	public const BANNER_SUBTITLE         = 'hyperpress_newsletter_banner_subtitle';
	public const BANNER_SUBTITLE_DEFAULT = 'Updates on Team HYPER from our Marketing Team';
	public const BANNER_IMAGE            = 'hyperpress_newsletter_banner_image';

	/**
	 * Default values, keyed by field id.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return self::archive_banner()->defaults();
	}

	/**
	 * The shared archive-banner settings helper bound to this plugin's option.
	 */
	public static function archive_banner(): ArchiveBannerSettings {
		return new ArchiveBannerSettings( self::OPTION, self::BANNER_SUBTITLE, self::BANNER_SUBTITLE_DEFAULT, self::BANNER_IMAGE );
	}

	/**
	 * Filter on default_option_hyperpress_newsletter: the defaults, unless the caller passed its own.
	 *
	 * @param mixed  $fallback       The default value.
	 * @param string $option         Option name.
	 * @param bool   $passed_default Whether get_option() was given an explicit default.
	 * @return mixed
	 */
	public static function default_option( $fallback, $option = '', $passed_default = false ) {
		return self::archive_banner()->default_option( $fallback, $option, $passed_default );
	}

	/**
	 * The newsletter banner subtitle.
	 *
	 * Option row missing: the default_option filter makes get_option() return the defaults, so the default
	 * text shows until the page is saved. Row present but key missing: Meta Box drops the key of an empty
	 * field, so the editor cleared it and the subtitle is ''. Option not an array, or value not a string:
	 * the default.
	 */
	public static function banner_subtitle(): string {
		return self::archive_banner()->banner_subtitle();
	}

	/**
	 * The archive hero image attachment ID, or 0 when none is set or it is not an image.
	 */
	public static function banner_image(): int {
		return self::archive_banner()->image_id();
	}
}
