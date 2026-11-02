<?php
namespace HyperPress\Tests\Integration\Competition;

use DateTimeImmutable;
use HyperPress\Competition\FrontpageCountdown;
use HyperPress\Competition\Schedule;
use HyperPress\Competition\Settings;
use HyperPress\Countdown\Assets;
use HyperPress\Frontpage\Countdowns;
use WP_UnitTestCase;

final class FrontpageCountdownTest extends WP_UnitTestCase {

	private const FILTER = 'hyperpress_frontpage_countdown_candidates';

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
		$this->clock( '2030-01-01 12:00:00' );
	}

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_competition_now' );
		delete_option( Settings::OPTION );
		update_option( 'timezone_string', '' );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	private function clock( string $when ): void {
		remove_all_filters( 'hyperpress_competition_now' );
		$now = new DateTimeImmutable( $when, wp_timezone() );
		add_filter( 'hyperpress_competition_now', static fn() => $now );
	}

	private function moment( string $when ): DateTimeImmutable {
		return new DateTimeImmutable( $when, wp_timezone() );
	}

	/**
	 * @param list<array<string,mixed>> $days Schedule rows.
	 * @param array<string,mixed>       $post Post overrides.
	 */
	private function competition( array $days, array $post = array() ): int {
		$id = self::factory()->post->create( array_merge( array( 'post_type' => 'hyper_competition', 'post_status' => 'publish', 'post_title' => 'UNH District Event' ), $post ) );
		update_post_meta( $id, Schedule::FIELD, $days );

		return $id;
	}

	/** @return array<int,array<string,mixed>> */
	private function candidates(): array {
		return FrontpageCountdown::candidates( array() );
	}

	public function test_the_filter_is_hooked(): void {
		$this->assertSame( 10, has_filter( self::FILTER, array( FrontpageCountdown::class, 'candidates' ) ) );
	}

	public function test_offers_a_candidate_for_a_scheduled_competition(): void {
		$id = $this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ), array( 'label' => 'Day 2', 'start' => '2030-03-11 09:00' ) ) );

		$list = $this->candidates();

		$this->assertCount( 1, $list );
		$this->assertSame( "competition-$id", $list[0]['id'] );
		$this->assertSame( '2030-03-10 09:00', $list[0]['start']->format( 'Y-m-d H:i' ) );
		$this->assertSame( '2030-01-09 09:00', $list[0]['show_from']->format( 'Y-m-d H:i' ), '60 days before the first counted day' );
		$this->assertSame( '2030-03-11 20:00', $list[0]['show_until']->format( 'Y-m-d H:i' ), 'the last counted day ends at the default 8 pm' );
		$this->assertSame( 10, $list[0]['priority'] );
		$this->assertNull( $list[0]['fallback_after'] ?? null );
	}

	public function test_the_lead_time_setting_moves_the_start_of_the_window_exactly(): void {
		update_option( Settings::OPTION, array( Settings::FRONTPAGE => '1', Settings::FRONTPAGE_LEAD_DAYS => '45' ) );
		$this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ) ) );
		$list = $this->candidates();

		$this->assertNull( Countdowns::pick( $list, $this->moment( '2030-01-24 09:00:00' ) ), 'exactly 45 days before' );
		$this->assertNotNull( Countdowns::pick( $list, $this->moment( '2030-01-24 09:00:01' ) ) );
		$this->assertNull( Countdowns::pick( $list, $this->moment( '2030-01-24 08:59:59' ) ) );
	}

	public function test_the_window_closes_when_the_last_counted_day_ends(): void {
		$this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ) ) );
		$list = $this->candidates();

		$this->assertNotNull( Countdowns::pick( $list, $this->moment( '2030-03-10 19:59:59' ) ) );
		$this->assertNull( Countdowns::pick( $list, $this->moment( '2030-03-10 20:00:00' ) ) );
	}

	public function test_lead_days_keep_the_wall_clock_time_across_a_dst_change(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-20 09:00' ) ) );

		$from = $this->candidates()[0]['show_from'];

		$this->assertSame( '2030-01-19 09:00', $from->format( 'Y-m-d H:i' ) ); // The clocks changed on 10 March; 09:00 stays 09:00.
	}

	public function test_the_target_moves_to_the_next_counted_day_once_a_day_ends(): void {
		$this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ), array( 'label' => 'Day 2', 'start' => '2030-03-11 09:00' ) ) );

		$this->clock( '2030-03-10 21:00:00' );

		$this->assertSame( '2030-03-11 09:00', $this->candidates()[0]['start']->format( 'Y-m-d H:i' ) );
	}

	public function test_days_marked_skip_are_not_counted(): void {
		$this->competition( array( array( 'label' => 'Setup', 'start' => '2030-03-09 09:00', 'skip' => '1' ), array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ) ) );

		$list = $this->candidates();

		$this->assertSame( '2030-03-10 09:00', $list[0]['start']->format( 'Y-m-d H:i' ) );
		$this->assertSame( '2030-01-09 09:00', $list[0]['show_from']->format( 'Y-m-d H:i' ) );
	}

	public function test_nothing_is_offered_when_the_setting_is_off(): void {
		$this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ) ) );
		update_option( Settings::OPTION, array() );

		$this->assertSame( array(), $this->candidates() );
	}

	public function test_competitions_without_a_counted_day_or_not_public_are_skipped(): void {
		$this->competition( array() );
		$this->competition( array( array( 'label' => 'Setup', 'start' => '2030-03-09 09:00', 'skip' => '1' ) ) );
		$this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ) ), array( 'post_status' => 'draft' ) );
		$this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ) ), array( 'post_status' => 'private' ) );

		$this->assertSame( array(), $this->candidates() );
	}

	public function test_earlier_candidates_are_kept_and_this_one_is_appended(): void {
		$id = $this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ) ) );

		$list = FrontpageCountdown::candidates( array( array( 'id' => 'earlier' ) ) );

		$this->assertSame( array( 'earlier', "competition-$id" ), array_column( $list, 'id' ) );
		$this->assertSame( array( "competition-$id" ), array_column( FrontpageCountdown::candidates( 'nonsense' ), 'id' ), 'a non-array input counts as an empty list' );
	}

	public function test_render_returns_the_countdown_with_the_competition_title(): void {
		$this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ) ) );

		$html = ( $this->candidates()[0]['render'] )();

		$this->assertStringContainsString( 'countdown align-center', $html );
		$this->assertStringContainsString( 'UNH District Event: Day 1', $html );
	}

	public function test_render_shows_the_date_of_the_day_being_counted_to(): void {
		$this->competition( array( array( 'label' => 'Day 1', 'start' => '2030-03-10 09:00' ), array( 'label' => 'Day 2', 'start' => '2030-03-11 09:00' ) ) );

		$this->assertStringContainsString( '<time>Sunday March 10, 2030 @ 09:00AM</time>', ( $this->candidates()[0]['render'] )() );

		$this->clock( '2030-03-10 21:00:00' ); // Day 1 is over: the countdown and its date move to Day 2.

		$this->assertStringContainsString( '<time>Monday March 11, 2030 @ 09:00AM</time>', ( $this->candidates()[0]['render'] )() );
	}
}
