<?php
namespace HyperPress\Robot;

use HyperPress\Utils\ArchiveBannerSettings;

defined( 'ABSPATH' ) || exit;

/**
 * The Robot settings page under the hyperPress menu.
 */
final class SettingsPage {

	public const ID = 'hyperpress_robot';

	public static function register(): void {
		add_filter( 'mb_settings_pages', array( self::class, 'pages' ) );
	}

	/**
	 * Adds the Robot page.
	 *
	 * @param array<int,array<string,mixed>> $pages Settings pages.
	 * @return array<int,array<string,mixed>>
	 */
	public static function pages( array $pages ): array {
		$pages[] = ArchiveBannerSettings::page( self::ID, Settings::OPTION, __( 'Robot', 'hyperpress' ) );
		return $pages;
	}
}
