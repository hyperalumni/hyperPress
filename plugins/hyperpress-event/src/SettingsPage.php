<?php
namespace HyperPress\Event;

use HyperPress\Utils\ArchiveBannerSettings;

defined( 'ABSPATH' ) || exit;

/**
 * The Events settings page under the hyperPress menu.
 */
final class SettingsPage {

	public const ID = 'hyperpress_event';

	public static function register(): void {
		add_filter( 'mb_settings_pages', array( self::class, 'pages' ) );
	}

	/**
	 * Adds the Events page.
	 *
	 * @param array<int,array<string,mixed>> $pages Settings pages.
	 * @return array<int,array<string,mixed>>
	 */
	public static function pages( array $pages ): array {
		$pages[] = ArchiveBannerSettings::page( self::ID, Settings::OPTION, __( 'Events', 'hyperpress' ) );
		return $pages;
	}
}
