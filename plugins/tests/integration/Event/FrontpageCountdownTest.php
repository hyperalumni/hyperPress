<?php
namespace HyperPress\Tests\Integration\Event;

use DateTimeImmutable;
use HyperPress\Countdown\Assets;
use HyperPress\Event\Fields;
use HyperPress\Event\FrontpageCountdown;
use HyperPress\Event\Settings;
use HyperPress\Frontpage\Countdowns;
use WP_UnitTestCase;

final class FrontpageCountdownTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
		delete_option( Settings::OPTION );
	}

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_event_now' );
		delete_option( Settings::OPTION );
		update_option( 'timezone_string', '' );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	private function moment( string $when ): DateTimeImmutable {
		return new DateTimeImmutable( $when, wp_timezone() );
	}

	/**
	 * @param array<string,mixed> $post Post overrides.
	 */
	private function event( string $start, string $end = '', array $post = array() ): int {
		$id = self::factory()->post->create( array_merge( array( 'post_type' => 'hyper_event', 'post_status' => 'publish', 'post_title' => 'Spring Open' ), $post ) );
		if ( '' !== $start ) {
			update_post_meta( $id, Fields::START, $start );
		}
		if ( '' !== $end ) {
			update_post_meta( $id, Fields::END, $end );
		}

		return $id;
	}

	/** @return array<int,array<string,mixed>> */
	private function candidates(): array {
		return FrontpageCountdown::candidates( array() );
	}

	public function test_the_filter_is_hooked(): void {
		$this->assertSame( 10, has_filter( 'hyperpress_frontpage_countdown_candidates', array( FrontpageCountdown::class, 'candidates' ) ) );
	}

	public function test_candidate_shape_and_window(): void {
		$id = $this->event( '2030-03-10 09:00', '2030-03-12 17:00' );

		$list = $this->candidates();

		$this->assertCount( 1, $list );
		$this->assertSame( "event-$id", $list[0]['id'] );
		$this->assertSame( '2030-03-10 09:00', $list[0]['start']->format( 'Y-m-d H:i' ) );
		$this->assertSame( '2030-02-17 09:00', $list[0]['show_from']->format( 'Y-m-d H:i' ), '21 days before the start' );
		$this->assertSame( '2030-03-12 17:00', $list[0]['show_until']->format( 'Y-m-d H:i' ) );
		$this->assertNull( $list[0]['fallback_after'] ?? null );
		$this->assertSame( 10, $list[0]['priority'] );
		$this->assertStringContainsString( 'countdown align-center', ( $list[0]['render'] )() );
	}

	public function test_lead_days_keep_wall_clock_across_dst(): void {
		update_option( 'timezone_string', 'America/New_York' );
		update_option( Settings::OPTION, array( Settings::FRONTPAGE => '1', Settings::FRONTPAGE_LEAD_DAYS => '3' ) );
		$this->event( '2026-11-02 09:00' );
		add_filter( 'hyperpress_event_now', fn(): DateTimeImmutable => $this->moment( '2026-10-01 08:00' ) );

		$this->assertSame( '2026-10-30 09:00', $this->candidates()[0]['show_from']->format( 'Y-m-d H:i' ) );
	}

	public function test_skipped_when_setting_off_no_start_draft_or_private(): void {
		$this->event( '' );
		$this->event( '2030-03-10 09:00', '', array( 'post_status' => 'draft' ) );
		$this->event( '2030-03-10 09:00', '', array( 'post_status' => 'private' ) );
		$this->assertSame( array(), $this->candidates() );

		$this->event( '2030-03-10 09:00' );
		$this->assertCount( 1, $this->candidates() );

		update_option( Settings::OPTION, array() );
		$this->assertSame( array(), $this->candidates() );
	}

	public function test_end_of_day_window_when_no_end(): void {
		$this->event( '2030-03-10 09:00' );
		$list = $this->candidates();

		$this->assertNotNull( Countdowns::pick( $list, $this->moment( '2030-03-10 20:00:00' ) ) );
		$this->assertNotNull( Countdowns::pick( $list, $this->moment( '2030-03-10 09:00:00' ) ) );
		$this->assertNull( Countdowns::pick( $list, $this->moment( '2030-03-11 00:00:00' ) ) );
	}

	public function test_earliest_start_wins_against_a_competition_candidate(): void {
		$id    = $this->event( '2030-03-10 09:00' );
		$other = array(
			'id'         => 'competition-1',
			'start'      => $this->moment( '2030-03-20 09:00' ),
			'show_from'  => $this->moment( '2030-01-01 00:00' ),
			'show_until' => $this->moment( '2030-03-21 00:00' ),
			'priority'   => 10,
			'render'     => static fn(): string => 'x',
		);
		$list  = FrontpageCountdown::candidates( array( $other ) );

		$picked = Countdowns::pick( $list, $this->moment( '2030-03-01 00:00:00' ) );

		$this->assertSame( "event-$id", $picked['id'] );
	}

	public function test_a_past_event_is_not_offered_but_a_running_one_is(): void {
		$past    = $this->event( '2030-03-01 09:00', '2030-03-02 17:00' );
		$running = $this->event( '2030-03-10 09:00', '2030-03-12 17:00' );
		add_filter( 'hyperpress_event_now', fn(): DateTimeImmutable => $this->moment( '2030-03-11 08:00' ) );

		$ids = array_column( $this->candidates(), 'id' );

		$this->assertSame( array( "event-$running" ), $ids );
		$this->assertNotContains( "event-$past", $ids );
	}
}
