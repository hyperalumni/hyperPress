<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Meta;

/**
 * Reads the competition start date, stored as wall-clock text in the site timezone.
 */
final class StartDate {

	public const FIELD = 'hyperpress_competition_event_start_date';

	/**
	 * The start as an instant, or null when empty, not text or unparseable.
	 *
	 * @param int $post_id Competition post ID.
	 */
	public static function instant( int $post_id ): ?\DateTimeImmutable {
		$text = Meta::get( self::FIELD, $post_id );

		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			return null;
		}

		try {
			return new \DateTimeImmutable( $text, wp_timezone() );
		} catch ( \Exception $e ) {
			return null;
		}
	}
}
