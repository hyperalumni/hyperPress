<?php
namespace HyperPress\Newsletter;

use HyperPress\Utils\ArchiveBannerSettings;

defined( 'ABSPATH' ) || exit;

/**
 * The Newsletter settings page under the hyperPress menu.
 */
final class SettingsPage {

	public const ID = 'hyperpress_newsletter';

	public static function register(): void {
		add_filter( 'mb_settings_pages', array( self::class, 'pages' ) );
	}

	/**
	 * Adds the Newsletter page.
	 *
	 * @param array<int,array<string,mixed>> $pages Settings pages.
	 * @return array<int,array<string,mixed>>
	 */
	public static function pages( array $pages ): array {
		$pages[] = ArchiveBannerSettings::page( self::ID, Settings::OPTION, __( 'Newsletter', 'hyperpress' ) );
		return $pages;
	}
}
