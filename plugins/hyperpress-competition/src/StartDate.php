<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the competition start date, stored as wall-clock text in the site timezone.
 */
final class StartDate {

	public const FIELD = 'hyperpress_competition_event_start_date';

	/**
	 * The start as an instant, or null when empty, not text or not a valid date.
	 *
	 * Reads the stored text directly (the field saves 'Y-m-d H:i'), not rwmb_meta(), which may reformat it.
	 *
	 * @param int $post_id Competition post ID.
	 */
	public static function instant( int $post_id ): ?\DateTimeImmutable {
		$text = get_post_meta( $post_id, self::FIELD, true );

		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			return null;
		}
		$text = trim( $text );

		foreach ( array( '!Y-m-d H:i', '!Y-m-d H:i:s' ) as $format ) {
			$instant = \DateTimeImmutable::createFromFormat( $format, $text, wp_timezone() );
			$errors  = \DateTimeImmutable::getLastErrors();
			if ( $instant instanceof \DateTimeImmutable && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ) {
				return $instant;
			}
		}

		return null;
	}
}
