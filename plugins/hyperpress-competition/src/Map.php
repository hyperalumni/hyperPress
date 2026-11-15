<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * A full-width, non-interactive OpenStreetMap of the venue under the banner on the competition single page.
 */
final class Map {

	public static function register(): void {
		add_action( 'hyperpress_after_banner', array( self::class, 'render' ) );
	}

	/**
	 * Echoes the map of the queried competition, or nothing when it has no address or a valid location.
	 */
	public static function render(): void {
		if ( ! is_singular( Plugin::POST_TYPE ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( $post_id <= 0 ) {
			return;
		}

		echo \HyperPress\Utils\VenueMap::for_post( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside VenueMap.
			$post_id,
			'hyperpress_competition_venue_name',
			'hyperpress_competition_venue_address',
			'hyperpress_competition_venue_map'
		);
	}
}
