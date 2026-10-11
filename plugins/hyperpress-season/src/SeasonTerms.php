<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use DateTimeInterface;

/**
 * The default season terms: Season 1 (1995-1996) up to the current season.
 */
final class SeasonTerms {

	/** First season starts in this year. */
	private const FIRST_YEAR = 1995;

	/** A season is seeded from August 1, a month before it starts, so content for September already has its season. */
	private const START_MONTH = 8;

	/**
	 * Season specs for TermSeeder, oldest first. A new season is added from August 1.
	 *
	 * @param DateTimeInterface $now Today, in the site timezone.
	 * @return array<int,array{name:string,description:string}>
	 */
	public static function default_terms( DateTimeInterface $now ): array {
		$year = (int) $now->format( 'Y' );
		if ( (int) $now->format( 'n' ) < self::START_MONTH ) {
			--$year;
		}

		$terms = array();
		for ( $number = 1; $number <= $year - self::FIRST_YEAR + 1; $number++ ) {
			$start   = self::FIRST_YEAR + $number - 1;
			$terms[] = array(
				'name'        => 'Season ' . $number,
				'description' => $start . '-' . ( $start + 1 ),
			);
		}

		return $terms;
	}
}
