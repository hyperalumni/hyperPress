<?php
namespace HyperPress\Tests\Integration\Countdown;

use HyperPress\Countdown\Assets;
use WP_UnitTestCase;

/**
 * Pins the shortcode's Hide Before / Hide After behaviour so moving it onto Window cannot change it.
 */
final class ShortcodeWindowTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb'] );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	private function when( string $offset ): string {
		return wp_date( 'Y-m-d H:i', strtotime( $offset ) );
	}

	/** @param array<string,mixed> $meta */
	private function render( array $meta, string $attributes = '' ): string {
		$GLOBALS['hyperpress_test_rwmb'] = array_merge( array( 'hyperpress_countdown_event_date_time' => '2030-01-01 10:00' ), $meta );
		$id = self::factory()->post->create( array( 'post_type' => 'hyper_countdown', 'post_status' => 'publish', 'post_title' => 'Kickoff' ) );

		return do_shortcode( '[hyperpress_countdown id="' . $id . '" ' . $attributes . ']' );
	}

	private function shown( string $output ): bool {
		return str_contains( $output, 'countdown align-center' );
	}

	public function test_no_visibility_dates_show_the_countdown(): void {
		$this->assertTrue( $this->shown( $this->render( array() ) ) );
	}

	public function test_hide_before_in_the_future_hides_it_and_in_the_past_shows_it(): void {
		$this->assertFalse( $this->shown( $this->render( array( 'hyperpress_countdown_hide_before' => $this->when( '+2 days' ) ) ) ) );
		$this->assertTrue( $this->shown( $this->render( array( 'hyperpress_countdown_hide_before' => $this->when( '-2 days' ) ) ) ) );
	}

	public function test_hide_after_in_the_past_hides_it_and_in_the_future_shows_it(): void {
		$this->assertFalse( $this->shown( $this->render( array( 'hyperpress_countdown_hide_after' => $this->when( '-2 days' ) ) ) ) );
		$this->assertTrue( $this->shown( $this->render( array( 'hyperpress_countdown_hide_after' => $this->when( '+2 days' ) ) ) ) );
	}

	public function test_both_dates_make_a_window(): void {
		$inside = array( 'hyperpress_countdown_hide_before' => $this->when( '-1 day' ), 'hyperpress_countdown_hide_after' => $this->when( '+1 day' ) );
		$early  = array( 'hyperpress_countdown_hide_before' => $this->when( '+1 day' ), 'hyperpress_countdown_hide_after' => $this->when( '+3 days' ) );
		$late   = array( 'hyperpress_countdown_hide_before' => $this->when( '-3 days' ), 'hyperpress_countdown_hide_after' => $this->when( '-1 day' ) );

		$this->assertTrue( $this->shown( $this->render( $inside ) ) );
		$this->assertFalse( $this->shown( $this->render( $early ) ) );
		$this->assertFalse( $this->shown( $this->render( $late ) ) );
	}

	public function test_ignoreshowhide_shows_a_closed_window(): void {
		$closed = array( 'hyperpress_countdown_hide_after' => $this->when( '-2 days' ) );

		$this->assertTrue( $this->shown( $this->render( $closed, 'ignoreshowhide="true"' ) ) );
	}

	public function test_a_missing_event_date_renders_nothing_even_when_the_window_is_open(): void {
		$this->assertFalse( $this->shown( $this->render( array( 'hyperpress_countdown_event_date_time' => '' ) ) ) );
	}
}
