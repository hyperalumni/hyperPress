<?php
namespace HyperPress\Tests\Integration\Frontpage;

use DateTimeImmutable;
use HyperPress\Frontpage\Countdowns;
use WP_UnitTestCase;

final class CountdownsHtmlTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$now = new DateTimeImmutable( '2026-11-02 12:00:00', wp_timezone() );
		add_filter( 'hyperpress_frontpage_now', static fn() => $now );
		// Start from a clean candidate list: the real plugins also offer countdowns and competitions.
		remove_all_filters( Countdowns::CANDIDATES_FILTER );
	}

	private function offer( array $candidate ): void {
		add_filter(
			Countdowns::CANDIDATES_FILTER,
			static function ( $list ) use ( $candidate ) {
				$list   = is_array( $list ) ? $list : array();
				$list[] = $candidate;
				return $list;
			}
		);
	}

	/**
	 * @param callable(): mixed $render Render callback.
	 * @return array<string,mixed>
	 */
	private function candidate( string $id, callable $render, string $start = '2026-12-01 00:00:00' ): array {
		return array(
			'id'         => $id,
			'start'      => new DateTimeImmutable( $start, wp_timezone() ),
			'show_from'  => null,
			'show_until' => null,
			'priority'   => 10,
			'render'     => $render,
		);
	}

	public function test_returns_the_chosen_candidates_html(): void {
		$this->offer( $this->candidate( 'a', static fn() => '<p>chosen</p>' ) );

		$this->assertSame( '<p>chosen</p>', Countdowns::html() );
	}

	public function test_returns_the_incoming_value_when_nothing_is_open(): void {
		$this->assertSame( '', Countdowns::html() );
		$this->assertSame( 'kept', Countdowns::html( 'kept' ) );
	}

	public function test_a_render_that_throws_or_returns_a_non_string_is_ignored(): void {
		$this->offer( $this->candidate( 'throws', static function () {
			throw new \RuntimeException( 'boom' );
		} ) );
		$this->assertSame( '', Countdowns::html() );

		remove_all_filters( Countdowns::CANDIDATES_FILTER );
		$this->offer( $this->candidate( 'array', static fn() => array( 'x' ) ) );
		$this->assertSame( '', Countdowns::html() );
	}

	public function test_the_theme_filter_is_wired(): void {
		$this->offer( $this->candidate( 'a', static fn() => '<p>via the theme filter</p>' ) );

		$this->assertSame( '<p>via the theme filter</p>', apply_filters( Countdowns::HTML_FILTER, '' ) );
	}

	public function test_a_third_party_filter_returning_a_non_array_is_ignored(): void {
		add_filter( Countdowns::CANDIDATES_FILTER, static fn() => 'nonsense' );

		$this->assertSame( '', Countdowns::html() );
	}

	public function test_only_the_render_of_the_chosen_candidate_is_called(): void {
		$called = array();
		$this->offer( $this->candidate( 'later', static function () use ( &$called ) {
			$called[] = 'later';
			return 'later';
		}, '2026-12-20 00:00:00' ) );
		$this->offer( $this->candidate( 'sooner', static function () use ( &$called ) {
			$called[] = 'sooner';
			return 'sooner';
		}, '2026-11-20 00:00:00' ) );

		$this->assertSame( 'sooner', Countdowns::html() );
		$this->assertSame( array( 'sooner' ), $called );
	}

	public function test_the_clock_falls_back_to_real_time_when_the_filter_returns_something_else(): void {
		remove_all_filters( 'hyperpress_frontpage_now' );
		add_filter( 'hyperpress_frontpage_now', static fn() => 'not a date' );

		$this->assertInstanceOf( DateTimeImmutable::class, Countdowns::now() );
	}
}
