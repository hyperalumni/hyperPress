<?php
namespace HyperPress\Tests\Integration\Countdown;

use DateTimeImmutable;
use HyperPress\Countdown\Window;
use WP_UnitTestCase;

final class WindowTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb'] );
		update_option( 'timezone_string', '' );
		parent::tear_down();
	}

	/**
	 * @param array<string,mixed> $meta Stored meta (Meta Box stub).
	 * @param array<string,mixed> $post Post overrides.
	 */
	private function countdown( array $meta = array(), array $post = array() ): int {
		$GLOBALS['hyperpress_test_rwmb'] = array_merge( array( 'hyperpress_countdown_event_date_time' => '2030-01-01 10:00' ), $meta );

		return self::factory()->post->create( array_merge( array( 'post_type' => 'hyper_countdown', 'post_status' => 'publish' ), $post ) );
	}

	private function moment( string $when ): DateTimeImmutable {
		return new DateTimeImmutable( $when, wp_timezone() );
	}

	public function test_for_post_returns_the_start_and_the_window(): void {
		$id = $this->countdown(
			array(
				'hyperpress_countdown_hide_before' => '2029-12-01 08:00',
				'hyperpress_countdown_hide_after'  => '2030-01-02 20:00',
			)
		);

		$window = Window::for_post( $id );

		$this->assertSame( '2030-01-01 10:00', $window['start']->format( 'Y-m-d H:i' ) );
		$this->assertSame( '2029-12-01 08:00', $window['show_from']->format( 'Y-m-d H:i' ) );
		$this->assertSame( '2030-01-02 20:00', $window['show_until']->format( 'Y-m-d H:i' ) );
	}

	public function test_empty_or_unparseable_visibility_dates_are_no_bound(): void {
		$empty = Window::for_post( $this->countdown() );
		$this->assertNull( $empty['show_from'] );
		$this->assertNull( $empty['show_until'] );

		$garbage = Window::for_post( $this->countdown( array( 'hyperpress_countdown_hide_before' => 'garbage', 'hyperpress_countdown_hide_after' => 'nonsense' ) ) );
		$this->assertNull( $garbage['show_from'] );
		$this->assertNull( $garbage['show_until'] );
	}

	public function test_a_missing_or_unparseable_event_date_gives_null(): void {
		$this->assertNull( Window::for_post( $this->countdown( array( 'hyperpress_countdown_event_date_time' => '' ) ) ) );
		$this->assertNull( Window::for_post( $this->countdown( array( 'hyperpress_countdown_event_date_time' => 'not a date' ) ) ) );
	}

	public function test_only_published_countdowns_or_readable_private_ones_have_a_window(): void {
		$this->assertNull( Window::for_post( $this->countdown( array(), array( 'post_status' => 'draft' ) ) ) );
		$this->assertNull( Window::for_post( self::factory()->post->create( array( 'post_type' => 'post', 'post_status' => 'publish' ) ) ) );
		$this->assertNull( Window::for_post( 0 ) );

		$private = $this->countdown( array(), array( 'post_status' => 'private' ) );
		wp_set_current_user( 0 );
		$this->assertNull( Window::for_post( $private ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertNotNull( Window::for_post( $private ) );
	}

	public function test_dates_are_read_in_the_site_timezone(): void {
		update_option( 'timezone_string', 'America/New_York' );

		$window = Window::for_post( $this->countdown() );

		$this->assertSame( -18000, $window['start']->getOffset() );
		$this->assertSame( 'America/New_York', $window['start']->getTimezone()->getName() );
	}

	public function test_is_open_is_strict_on_both_sides_and_bounds_are_optional(): void {
		$from  = $this->moment( '2030-01-01 00:00:00' );
		$until = $this->moment( '2030-01-02 00:00:00' );

		$this->assertTrue( Window::is_open( null, null, $this->moment( '2030-06-01' ) ) );
		$this->assertTrue( Window::is_open( $from, null, $this->moment( '2030-01-01 00:00:01' ) ) );
		$this->assertFalse( Window::is_open( $from, null, $from ) );
		$this->assertTrue( Window::is_open( null, $until, $this->moment( '2030-01-01 23:59:59' ) ) );
		$this->assertFalse( Window::is_open( null, $until, $until ) );
		$this->assertTrue( Window::is_open( $from, $until, $this->moment( '2030-01-01 12:00:00' ) ) );
		$this->assertFalse( Window::is_open( $from, $until, $this->moment( '2029-12-31 23:59:59' ) ) );
		$this->assertFalse( Window::is_open( $from, $until, $this->moment( '2030-01-02 00:00:01' ) ) );
	}
}
