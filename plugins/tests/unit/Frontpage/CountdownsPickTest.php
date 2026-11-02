<?php
namespace HyperPress\Tests\Unit\Frontpage;

use DateTimeImmutable;
use DateTimeZone;
use HyperPress\Frontpage\Countdowns;
use PHPUnit\Framework\TestCase;

final class CountdownsPickTest extends TestCase {

	private function when( string $when ): DateTimeImmutable {
		return new DateTimeImmutable( $when, new DateTimeZone( 'UTC' ) );
	}

	private function now(): DateTimeImmutable {
		return $this->when( '2026-11-02 12:00:00' );
	}

	/**
	 * @param array<string,mixed> $overrides Keys to replace.
	 * @return array<string,mixed>
	 */
	private function candidate( string $id, string $start, array $overrides = array() ): array {
		return array_merge(
			array(
				'id'         => $id,
				'start'      => $this->when( $start ),
				'show_from'  => null,
				'show_until' => null,
				'priority'   => 10,
				'render'     => static fn(): string => $id,
			),
			$overrides
		);
	}

	private function picked_id( array $candidates ): ?string {
		$picked = Countdowns::pick( $candidates, $this->now() );

		return null === $picked ? null : $picked['id'];
	}

	public function test_returns_null_for_an_empty_list(): void {
		$this->assertNull( Countdowns::pick( array(), $this->now() ) );
	}

	public function test_a_window_is_open_only_strictly_between_the_bounds(): void {
		$start = '2026-12-01 00:00:00';
		$open  = fn( string $from, string $until ) => $this->picked_id(
			array( $this->candidate( 'a', $start, array( 'show_from' => $this->when( $from ), 'show_until' => $this->when( $until ) ) ) )
		);

		$this->assertNull( $open( '2026-11-02 12:00:01', '2026-11-03 00:00:00' ), 'before show_from' );
		$this->assertNull( $open( '2026-11-02 12:00:00', '2026-11-03 00:00:00' ), 'exactly at show_from' );
		$this->assertSame( 'a', $open( '2026-11-02 11:59:59', '2026-11-03 00:00:00' ), 'inside' );
		$this->assertNull( $open( '2026-11-01 00:00:00', '2026-11-02 12:00:00' ), 'exactly at show_until' );
		$this->assertNull( $open( '2026-11-01 00:00:00', '2026-11-02 11:59:59' ), 'after show_until' );
	}

	public function test_either_bound_may_be_missing(): void {
		$this->assertSame( 'a', $this->picked_id( array( $this->candidate( 'a', '2026-12-01 00:00:00' ) ) ) );
		$this->assertSame( 'a', $this->picked_id( array( $this->candidate( 'a', '2026-12-01 00:00:00', array( 'show_from' => $this->when( '2026-11-01 00:00:00' ) ) ) ) ) );
		$this->assertSame( 'a', $this->picked_id( array( $this->candidate( 'a', '2026-12-01 00:00:00', array( 'show_until' => $this->when( '2026-12-02 00:00:00' ) ) ) ) ) );
	}

	public function test_the_earliest_start_wins(): void {
		$this->assertSame(
			'soon',
			$this->picked_id( array( $this->candidate( 'later', '2026-12-10 00:00:00' ), $this->candidate( 'soon', '2026-11-20 00:00:00' ) ) )
		);
	}

	public function test_priority_then_id_break_ties(): void {
		$start = '2026-12-01 00:00:00';

		$this->assertSame(
			'b',
			$this->picked_id( array( $this->candidate( 'a', $start ), $this->candidate( 'b', $start, array( 'priority' => 20 ) ) ) )
		);
		$this->assertSame(
			'a',
			$this->picked_id( array( $this->candidate( 'b', $start ), $this->candidate( 'a', $start ) ) )
		);
	}

	public function test_a_started_but_open_candidate_outranks_future_ones(): void {
		$this->assertSame(
			'started',
			$this->picked_id( array( $this->candidate( 'future', '2026-11-20 00:00:00' ), $this->candidate( 'started', '2026-10-01 00:00:00' ) ) )
		);
	}

	public function test_a_candidate_past_its_fallback_after_loses_to_any_open_primary(): void {
		$stale = $this->candidate( 'stale', '2026-10-01 00:00:00', array( 'fallback_after' => $this->when( '2026-10-01 23:59:59' ) ) );

		$this->assertSame( 'next', $this->picked_id( array( $stale, $this->candidate( 'next', '2026-12-25 00:00:00' ) ) ) );
	}

	public function test_a_fallback_is_shown_when_it_is_the_only_open_candidate(): void {
		$stale = $this->candidate( 'stale', '2026-10-01 00:00:00', array( 'fallback_after' => $this->when( '2026-10-01 23:59:59' ) ) );

		$this->assertSame( 'stale', $this->picked_id( array( $stale ) ) );
	}

	public function test_among_fallbacks_the_latest_start_wins(): void {
		$old    = $this->candidate( 'old', '2026-08-01 00:00:00', array( 'fallback_after' => $this->when( '2026-08-01 23:59:59' ) ) );
		$recent = $this->candidate( 'recent', '2026-10-01 00:00:00', array( 'fallback_after' => $this->when( '2026-10-01 23:59:59' ) ) );

		$this->assertSame( 'recent', $this->picked_id( array( $old, $recent ) ) );
	}

	public function test_closed_or_not_yet_open_candidates_never_displace_a_fallback(): void {
		$stale      = $this->candidate( 'stale', '2026-10-01 00:00:00', array( 'fallback_after' => $this->when( '2026-10-01 23:59:59' ) ) );
		$not_yet    = $this->candidate( 'not-yet', '2026-12-01 00:00:00', array( 'show_from' => $this->when( '2026-11-30 00:00:00' ) ) );
		$closed     = $this->candidate( 'closed', '2026-10-15 00:00:00', array( 'show_until' => $this->when( '2026-10-16 00:00:00' ) ) );

		$this->assertSame( 'stale', $this->picked_id( array( $not_yet, $closed, $stale ) ) );
	}

	public function test_a_future_fallback_after_keeps_the_candidate_primary(): void {
		$today = $this->candidate( 'today', '2026-11-02 09:00:00', array( 'fallback_after' => $this->when( '2026-11-02 23:59:59' ) ) );

		$this->assertSame( 'today', $this->picked_id( array( $this->candidate( 'next', '2026-12-25 00:00:00' ), $today ) ) );
	}

	public function test_malformed_candidates_are_ignored(): void {
		$good = $this->candidate( 'good', '2026-12-01 00:00:00' );

		$bad = array(
			'not an array',
			42,
			array( 'id' => 'x' ),
			$this->candidate( 'no-start', '2026-11-01 00:00:00', array( 'start' => '2026-11-01' ) ),
			$this->candidate( 'bad-render', '2026-11-01 00:00:00', array( 'render' => 'definitely_not_a_function' ) ),
			$this->candidate( 'bad-from', '2026-11-01 00:00:00', array( 'show_from' => '2026-01-01' ) ),
			$this->candidate( 'bad-until', '2026-11-01 00:00:00', array( 'show_until' => 5 ) ),
			$this->candidate( 'bad-fallback', '2026-11-01 00:00:00', array( 'fallback_after' => 7 ) ),
			$this->candidate( 'bad-id', '2026-11-01 00:00:00', array( 'id' => 12 ) ),
		);

		$this->assertSame( 'good', $this->picked_id( array_merge( $bad, array( $good ) ) ) );
	}
}
