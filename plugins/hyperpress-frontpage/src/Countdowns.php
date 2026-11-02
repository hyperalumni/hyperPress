<?php
namespace HyperPress\Frontpage;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Chooses the one countdown the front page shows from candidates that plugins offer through a filter.
 *
 * A candidate is an array:
 * - id             string, unique and stable (the last tie-break).
 * - start          DateTimeImmutable, the instant it counts to (used for ordering).
 * - show_from      ?DateTimeImmutable, the window opens after this instant (null: no lower bound).
 * - show_until     ?DateTimeImmutable, the window closes at this instant (null: no upper bound).
 * - fallback_after ?DateTimeImmutable, optional soft end: once now is past it, the candidate is shown only when
 *                  no other candidate is open (null or absent: never a fallback).
 * - priority       int, tie-breaker, higher wins (default 10).
 * - render         callable(): string, the countdown HTML; only the chosen candidate is rendered.
 */
final class Countdowns {

	public const CANDIDATES_FILTER = 'hyperpress_frontpage_countdown_candidates';
	public const HTML_FILTER       = 'hyperpress_frontpage_countdown_html';

	public static function register(): void {
		add_filter( self::HTML_FILTER, array( self::class, 'html' ) );
	}

	/**
	 * The HTML of the chosen countdown, or `$html` unchanged when there is none (or it cannot be rendered).
	 *
	 * @param mixed $html Incoming value of the theme filter.
	 */
	public static function html( $html = '' ): string {
		$html       = is_string( $html ) ? $html : '';
		$candidates = apply_filters( self::CANDIDATES_FILTER, array() );
		if ( ! is_array( $candidates ) ) {
			return $html;
		}

		$picked = self::pick( $candidates, self::now() );
		if ( null === $picked ) {
			return $html;
		}

		try {
			$output = ( $picked['render'] )();
		} catch ( \Throwable $error ) {
			return $html;
		}

		return is_string( $output ) ? $output : $html;
	}

	/**
	 * The current time in the site timezone; the `hyperpress_frontpage_now` filter lets tests control the clock.
	 */
	public static function now(): \DateTimeImmutable {
		$now = apply_filters( 'hyperpress_frontpage_now', new \DateTimeImmutable( 'now', wp_timezone() ) );

		return $now instanceof \DateTimeImmutable ? $now : new \DateTimeImmutable( 'now', wp_timezone() );
	}

	/**
	 * Choose the candidate to show.
	 *
	 * A window is open when `show_from < now` and `now < show_until` (both strict, each optional). Open candidates that
	 * are not past their `fallback_after` are primary: the one with the earliest `start` wins, then the highest
	 * `priority`, then the lowest `id`. When there is no primary candidate, the open fallback with the latest `start`
	 * wins (same tie-breaks). Closed or not-yet-open candidates never count.
	 *
	 * @param array<int,mixed>   $candidates Candidates; anything malformed is ignored.
	 * @param \DateTimeImmutable $now        The moment to judge the windows at.
	 * @return array<string,mixed>|null The chosen candidate, or null.
	 */
	public static function pick( array $candidates, \DateTimeImmutable $now ): ?array {
		$primary   = array();
		$fallbacks = array();

		foreach ( $candidates as $candidate ) {
			if ( ! self::is_valid( $candidate ) ) {
				continue;
			}
			$from  = $candidate['show_from'] ?? null;
			$until = $candidate['show_until'] ?? null;
			if ( null !== $from && ! ( $from < $now ) ) {
				continue;
			}
			if ( null !== $until && ! ( $now < $until ) ) {
				continue;
			}

			$fallback_after = $candidate['fallback_after'] ?? null;
			if ( null !== $fallback_after && $now > $fallback_after ) {
				$fallbacks[] = $candidate;
			} else {
				$primary[] = $candidate;
			}
		}

		if ( array() !== $primary ) {
			usort(
				$primary,
				static fn( array $a, array $b ): int => array( $a['start']->getTimestamp(), -self::priority( $a ), $a['id'] )
					<=> array( $b['start']->getTimestamp(), -self::priority( $b ), $b['id'] )
			);

			return $primary[0];
		}

		if ( array() !== $fallbacks ) {
			usort(
				$fallbacks,
				static fn( array $a, array $b ): int => array( -$a['start']->getTimestamp(), -self::priority( $a ), $a['id'] )
					<=> array( -$b['start']->getTimestamp(), -self::priority( $b ), $b['id'] )
			);

			return $fallbacks[0];
		}

		return null;
	}

	/**
	 * Whether a value is a usable candidate.
	 *
	 * @param mixed $candidate Value from the filter.
	 */
	private static function is_valid( $candidate ): bool {
		if ( ! is_array( $candidate ) || ! isset( $candidate['id'] ) || ! is_string( $candidate['id'] ) || '' === $candidate['id'] ) {
			return false;
		}
		if ( ! ( $candidate['start'] ?? null ) instanceof \DateTimeImmutable || ! is_callable( $candidate['render'] ?? null ) ) {
			return false;
		}
		foreach ( array( 'show_from', 'show_until', 'fallback_after' ) as $key ) {
			$bound = $candidate[ $key ] ?? null;
			if ( null !== $bound && ! $bound instanceof \DateTimeImmutable ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * A candidate's priority; 10 unless it carries an integer.
	 *
	 * @param array<string,mixed> $candidate A valid candidate.
	 */
	private static function priority( array $candidate ): int {
		return isset( $candidate['priority'] ) && is_int( $candidate['priority'] ) ? $candidate['priority'] : 10;
	}
}
