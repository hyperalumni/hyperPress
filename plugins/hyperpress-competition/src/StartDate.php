<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * Parses wall-clock date text in the site timezone.
 */
final class StartDate {

	/**
	 * Wall-clock text ('Y-m-d H:i' or 'Y-m-d H:i:s') read in the site timezone, or null when blank, not text or not a valid date.
	 *
	 * @param mixed $text Candidate text.
	 */
	public static function parse( mixed $text ): ?\DateTimeImmutable {
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
