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
		add_filter( self::CANDIDATES_FILTER, array( self::class, 'selected_countdown_candidates' ), 5 );
	}

	/**
	 * Candidates for the one-off countdowns chosen in the Front Page "Countdowns" field.
	 *
	 * The window is the countdown's Hide Before / Hide After. A countdown with no Hide After stays through the end of
	 * its event's day and after that only while nothing else is open, so a finished one never blocks the next one
	 * but is not replaced by an empty front page. Does nothing when the countdown plugin is not active.
	 *
	 * @param mixed $candidates Candidates offered so far.
	 * @return array<int,mixed>
	 */
	public static function selected_countdown_candidates( $candidates ): array {
		$candidates = is_array( $candidates ) ? $candidates : array();
		if ( ! class_exists( '\HyperPress\Countdown\Window' ) ) {
			return $candidates;
		}

		$option = get_option( Settings::OPTION );
		$ids    = is_array( $option ) ? ( $option[ Settings::COUNTDOWNS ] ?? array() ) : array();
		if ( ! is_array( $ids ) ) {
			return $candidates;
		}

		$seen = array();
		foreach ( $ids as $value ) {
			$id = is_scalar( $value ) ? absint( $value ) : 0;
			if ( 0 === $id || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;

			$window = \HyperPress\Countdown\Window::for_post( $id );
			if ( null === $window ) {
				continue;
			}

			$candidates[] = array(
				'id'             => 'countdown-' . $id,
				'start'          => $window['start'],
				'show_from'      => $window['show_from'],
				'show_until'     => $window['show_until'],
				'fallback_after' => null === $window['show_until'] ? $window['start']->setTime( 23, 59, 59 ) : null,
				'priority'       => 10,
				// The picker has already applied the window, so the shortcode must not judge it again with another clock.
				'render'         => static function () use ( $id ): string {
					if ( ! shortcode_exists( 'hyperpress_countdown' ) ) {
						return '';
					}

					return do_shortcode( '[hyperpress_countdown id="' . $id . '" ignoreshowhide="true" /]' );
				},
			);
		}

		return $candidates;
	}

	/**
	 * The HTML of the best countdown that renders, or `$html` unchanged when there is none.
	 *
	 * Candidates are tried in ranked order: one whose `render` returns nothing, returns a non-string or throws is
	 * skipped (a throw also fires `hyperpress_frontpage_countdown_error` with the candidate id and the error), so a
	 * broken first choice never blanks a usable second one.
	 *
	 * @param mixed $html Incoming value of the theme filter.
	 */
	public static function html( $html = '' ): string {
		$html       = is_string( $html ) ? $html : '';
		$candidates = apply_filters( self::CANDIDATES_FILTER, array() );
		if ( ! is_array( $candidates ) ) {
			return $html;
		}

		foreach ( self::rank( $candidates, self::now() ) as $candidate ) {
			try {
				$output = ( $candidate['render'] )();
			} catch ( \Throwable $error ) {
				do_action( 'hyperpress_frontpage_countdown_error', $candidate['id'], $error );
				continue;
			}
			if ( is_string( $output ) && '' !== $output ) {
				return $output;
			}
		}

		return $html;
	}

	/**
	 * The current time in the site timezone; the `hyperpress_frontpage_now` filter lets tests control the clock.
	 */
	public static function now(): \DateTimeImmutable {
		$now = apply_filters( 'hyperpress_frontpage_now', new \DateTimeImmutable( 'now', wp_timezone() ) );

		return $now instanceof \DateTimeImmutable ? $now : new \DateTimeImmutable( 'now', wp_timezone() );
	}

	/**
	 * Choose the candidate to show: the first of `rank()`.
	 *
	 * @param array<int,mixed>   $candidates Candidates; anything malformed is ignored.
	 * @param \DateTimeImmutable $now        The moment to judge the windows at.
	 * @return array<string,mixed>|null The chosen candidate, or null.
	 */
	public static function pick( array $candidates, \DateTimeImmutable $now ): ?array {
		return self::rank( $candidates, $now )[0] ?? null;
	}

	/**
	 * The usable candidates in the order they should be tried.
	 *
	 * A window is open when `show_from < now` and `now < show_until` (both strict, each optional). Open candidates that
	 * are not past their `fallback_after` are primary: earliest `start` first, then highest `priority`, then `id` as a
	 * string. They are followed by the open fallbacks, the most recently started first (same tie-breaks). Closed or
	 * not-yet-open candidates are left out.
	 *
	 * @param array<int,mixed>   $candidates Candidates; anything malformed is ignored.
	 * @param \DateTimeImmutable $now        The moment to judge the windows at.
	 * @return array<int,array<string,mixed>>
	 */
	public static function rank( array $candidates, \DateTimeImmutable $now ): array {
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

		usort( $primary, static fn( array $a, array $b ): int => self::compare( $a, $b, 1 ) );
		usort( $fallbacks, static fn( array $a, array $b ): int => self::compare( $a, $b, -1 ) );

		return array_merge( $primary, $fallbacks );
	}

	/**
	 * Order two candidates: by start (`$direction` 1 earliest first, -1 latest first), then higher priority, then id.
	 *
	 * @param array<string,mixed> $a         A valid candidate.
	 * @param array<string,mixed> $b         A valid candidate.
	 * @param int                 $direction 1 or -1.
	 */
	private static function compare( array $a, array $b, int $direction ): int {
		$by_start = $direction * ( $a['start']->getTimestamp() <=> $b['start']->getTimestamp() );
		if ( 0 !== $by_start ) {
			return $by_start;
		}

		$by_priority = self::priority( $b ) <=> self::priority( $a );
		if ( 0 !== $by_priority ) {
			return $by_priority;
		}

		return strcmp( $a['id'], $b['id'] );
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
