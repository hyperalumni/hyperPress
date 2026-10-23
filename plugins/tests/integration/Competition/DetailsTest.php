<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Details;
use HyperPress\Competition\Schedule;
use HyperPress\Competition\Settings;
use WP_UnitTestCase;

final class DetailsTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb_posts'] );
		remove_all_filters( 'hyperpress_competition_now' );
		delete_option( Settings::OPTION );
		delete_option( 'timezone_string' );
		parent::tear_down();
	}

	/**
	 * @param array<string,mixed>            $meta Meta fixtures.
	 * @param list<array<string,mixed>>|null $days Raw schedule rows, stored as given.
	 */
	private function competition( array $meta, ?array $days = null ): int {
		$id = self::factory()->post->create( array( 'post_type' => 'hyper_competition', 'post_status' => 'publish' ) );
		if ( null !== $days ) {
			update_post_meta( $id, Schedule::FIELD, $days );
		}
		$GLOBALS['hyperpress_test_rwmb_posts'][ $id ] = $meta;
		return $id;
	}

	public function test_text_fields_are_trimmed_strings(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_host_name'     => '  Host  ',
				'hyperpress_competition_venue_name'    => ' Arena ',
				'hyperpress_competition_venue_address' => "1 Main St\n",
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame( 'Host', $details['host'] );
		$this->assertSame( 'Arena', $details['venue'] );
		$this->assertSame( '1 Main St', $details['address'] );
	}

	public function test_blurb_is_html_and_stripped_of_scripts(): void {
		$id    = $this->competition(
			array( Details::BLURB => 'Hello <script>x()</script><a href="https://example.org">link</a>' )
		);
		$blurb = Details::blurb( $id );

		$this->assertStringContainsString( '<p>Hello', $blurb );
		$this->assertStringContainsString( '<a href="https://example.org">link</a>', $blurb );
		$this->assertStringNotContainsString( '<script', $blurb );
	}

	public function test_blurb_is_empty_when_missing_or_not_text(): void {
		$this->assertSame( '', Details::blurb( $this->competition( array() ) ) );
		$this->assertSame( '', Details::blurb( $this->competition( array( Details::BLURB => array( 'x' ) ) ) ) );
		$this->assertSame( '', Details::blurb( $this->competition( array( Details::BLURB => "  \n " ) ) ) );
	}

	public function test_for_post_does_not_carry_the_blurb(): void {
		$id = $this->competition( array( Details::BLURB => 'Hello' ) );

		$this->assertArrayNotHasKey( 'blurb', Details::for_post( $id ) );
	}

	public function test_links_keep_order_and_titles_override_labels(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_stream_url'         => array( 'url' => 'https://external.example/s', 'title' => '' ),
				'hyperpress_competition_ne_first_event_url' => array( 'url' => 'https://external.example/ne', 'title' => 'Custom' ),
				'hyperpress_competition_ftc_ma_event_url'   => array( 'url' => '', 'title' => 'Dropped' ),
				'hyperpress_competition_first_event_url'    => array( 'url' => 'https://external.example/f' ),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array(
				array( 'label' => 'Custom', 'url' => 'https://external.example/ne', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ),
				array( 'label' => 'FIRST Event', 'url' => 'https://external.example/f', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ),
				array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ),
			),
			$details['links']
		);
	}

	public function test_link_title_equal_to_its_url_uses_the_default_label(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_stream_url' => array( 'url' => 'https://external.example/s', 'title' => 'https://external.example/s' ),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array( array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ) ),
			$details['links']
		);
	}

	public function test_link_title_equal_to_its_url_apart_from_case_and_whitespace_uses_the_default_label(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_stream_url' => array( 'url' => 'https://external.example/s', 'title' => '  HTTPS://External.example/S ' ),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array( array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ) ),
			$details['links']
		);
	}

	public function test_link_title_different_from_its_url_still_overrides_the_default_label(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_stream_url' => array( 'url' => 'https://external.example/s', 'title' => 'Watch live' ),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array( array( 'label' => 'Watch live', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ) ),
			$details['links']
		);
	}

	public function test_links_with_rejected_or_empty_urls_are_dropped(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_ne_first_event_url' => array( 'url' => 'javascript:alert(1)', 'title' => 'Bad' ),
				'hyperpress_competition_ftc_ma_event_url'   => array( 'url' => '', 'title' => 'Empty' ),
				'hyperpress_competition_stream_url'         => array( 'url' => 'https://external.example/s', 'title' => '' ),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array( array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ) ),
			$details['links']
		);
	}

	public function test_links_follow_the_documented_order_with_derived_tba_link(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_stream_url'         => array( 'url' => 'https://external.example/s', 'title' => '' ),
				'hyperpress_competition_agenda_url'         => array( 'url' => 'https://external.example/a', 'title' => '' ),
				'hyperpress_competition_tba_event_key'      => '2026mabos',
				'hyperpress_competition_frc_events_url'     => array( 'url' => 'https://frc-events.firstinspires.org/2026/MABOS', 'title' => '' ),
				'hyperpress_competition_first_event_url'    => array( 'url' => 'https://external.example/f', 'title' => '' ),
				'hyperpress_competition_ftc_ma_event_url'   => array( 'url' => 'https://external.example/ftc', 'title' => '' ),
				'hyperpress_competition_ne_first_event_url' => array( 'url' => 'https://external.example/ne', 'title' => '' ),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array(
				array( 'label' => 'NE FIRST Event', 'url' => 'https://external.example/ne', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ),
				array( 'label' => 'FTC MA Event', 'url' => 'https://external.example/ftc', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ),
				array( 'label' => 'FIRST Event', 'url' => 'https://external.example/f', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ),
				array( 'label' => 'FRC Events', 'url' => 'https://frc-events.firstinspires.org/2026/MABOS', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ),
				array( 'label' => 'The Blue Alliance', 'url' => 'https://www.thebluealliance.com/event/2026mabos', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ),
				array( 'label' => 'Public Agenda', 'url' => 'https://external.example/a', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ),
				array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ),
			),
			$details['links']
		);
	}

	public function test_tba_key_is_trimmed_and_lowercased(): void {
		$id      = $this->competition( array( 'hyperpress_competition_tba_event_key' => '  2026MABos ' ) );
		$details = Details::for_post( $id );

		$this->assertSame(
			array( array( 'label' => 'The Blue Alliance', 'url' => 'https://www.thebluealliance.com/event/2026mabos', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ) ),
			$details['links']
		);
	}

	/** @return array<string,array{0:mixed}> */
	public static function invalid_tba_keys(): array {
		return array(
			'empty'            => array( '' ),
			'whitespace only'  => array( '   ' ),
			'no year'          => array( 'mabos' ),
			'year only'        => array( '2026' ),
			'inner characters' => array( '2026ma/bos' ),
			'path traversal'   => array( '2026../x' ),
			'not a string'     => array( array( '2026mabos' ) ),
		);
	}

	/** @dataProvider invalid_tba_keys */
	public function test_invalid_tba_key_adds_no_link( $key ): void {
		$id      = $this->competition( array( 'hyperpress_competition_tba_event_key' => $key ) );
		$details = Details::for_post( $id );

		$this->assertSame( array(), $details['links'] );
	}

	public function test_frc_events_and_agenda_links_use_default_labels_and_titles(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_frc_events_url' => array( 'url' => 'https://frc-events.firstinspires.org/2026/MABOS', 'title' => '' ),
				'hyperpress_competition_agenda_url'     => array( 'url' => 'https://external.example/agenda', 'title' => 'Schedule' ),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array(
				array( 'label' => 'FRC Events', 'url' => 'https://frc-events.firstinspires.org/2026/MABOS', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ),
				array( 'label' => 'Schedule', 'url' => 'https://external.example/agenda', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ),
			),
			$details['links']
		);
	}

	public function test_multiple_streams_use_their_titles_or_numbered_labels(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_stream_url' => array(
					array( 'url' => 'https://external.example/s1', 'title' => 'Main stage' ),
					array( 'url' => 'https://external.example/s2', 'title' => '' ),
					array( 'url' => 'https://external.example/s3', 'title' => 'https://external.example/s3' ),
				),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array(
				array( 'label' => 'Main stage', 'url' => 'https://external.example/s1', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ),
				array( 'label' => 'Stream 2', 'url' => 'https://external.example/s2', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ),
				array( 'label' => 'Stream 3', 'url' => 'https://external.example/s3', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ),
			),
			$details['links']
		);
	}

	public function test_untitled_streams_are_numbered(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_stream_url' => array(
					array( 'url' => 'https://external.example/s1', 'title' => '' ),
					array( 'url' => 'https://external.example/s2' ),
				),
			)
		);
		$labels  = wp_list_pluck( Details::for_post( $id )['links'], 'label' );

		$this->assertSame( array( 'Stream 1', 'Stream 2' ), $labels );
	}

	public function test_a_single_stream_in_a_list_is_labelled_stream(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_stream_url' => array( array( 'url' => 'https://external.example/s1', 'title' => '' ) ),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array( array( 'label' => 'Stream', 'url' => 'https://external.example/s1', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ) ),
			$details['links']
		);
	}

	public function test_streams_without_a_safe_url_are_dropped(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_stream_url' => array(
					array( 'url' => '', 'title' => 'Empty' ),
					array( 'url' => 'javascript:alert(1)', 'title' => 'Bad' ),
					array( 'url' => 'https://external.example/ok', 'title' => 'Ok' ),
				),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array( array( 'label' => 'Ok', 'url' => 'https://external.example/ok', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ) ),
			$details['links']
		);
	}

	public function test_empty_stream_values_give_no_links(): void {
		foreach ( array( '', array(), null ) as $value ) {
			$id = $this->competition( array( 'hyperpress_competition_stream_url' => $value ) );

			$this->assertSame( array(), Details::for_post( $id )['links'] );
		}
	}

	public function test_internal_urls_open_in_the_same_tab_without_rel(): void {
		$internal = home_url( '/agenda/' );
		$id       = $this->competition(
			array(
				'hyperpress_competition_agenda_url' => array( 'url' => $internal, 'title' => '' ),
				'hyperpress_competition_stream_url' => array( 'url' => 'https://external.example/s', 'title' => '' ),
			)
		);

		$this->assertSame(
			array(
				array( 'label' => 'Public Agenda', 'url' => $internal, 'target' => '_self', 'rel' => '', 'group' => 'info' ),
				array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'live' ),
			),
			Details::for_post( $id )['links']
		);
	}

	public function test_internal_pdf_opens_in_a_new_tab(): void {
		$pdf = home_url( '/wp-content/uploads/2026/agenda.pdf' );
		$id  = $this->competition(
			array( 'hyperpress_competition_agenda_url' => array( 'url' => $pdf, 'title' => '' ) )
		);

		$this->assertSame(
			array( array( 'label' => 'Public Agenda', 'url' => $pdf, 'target' => '_blank', 'rel' => 'noopener noreferrer', 'group' => 'info' ) ),
			Details::for_post( $id )['links']
		);
	}

	public function test_missing_start_date_gives_null_when(): void {
		$details = Details::for_post( $this->competition( array() ) );

		$this->assertNull( $details['when'] );
		$this->assertSame( array(), $details['days'] );
		$this->assertSame( array(), $details['links'] );
	}

	public function test_when_uses_the_site_timezone(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id      = $this->competition( array(), array( array( 'label' => '', 'start' => '2030-01-15 10:00' ) ) );
		$details = Details::for_post( $id );

		$this->assertSame( '2030-01-15T10:00:00-05:00', $details['when']->format( 'c' ) );
	}

	public function test_links_are_grouped_streams_live_and_everything_else_info(): void {
		$id    = $this->competition(
			array(
				'hyperpress_competition_ne_first_event_url' => array( 'url' => 'https://external.example/ne', 'title' => '' ),
				'hyperpress_competition_tba_event_key'      => '2026mabos',
				'hyperpress_competition_agenda_url'         => array( 'url' => 'https://external.example/a', 'title' => '' ),
				'hyperpress_competition_stream_url'         => array(
					array( 'url' => 'https://external.example/s1', 'title' => 'Saturday' ),
					array( 'url' => 'https://external.example/s2', 'title' => 'Sunday' ),
				),
			)
		);
		$links = Details::for_post( $id )['links'];

		$this->assertSame(
			array( 'info', 'info', 'info', 'live', 'live' ),
			wp_list_pluck( $links, 'group' )
		);
		$this->assertSame(
			array( 'NE FIRST Event', 'The Blue Alliance', 'Public Agenda', 'Saturday', 'Sunday' ),
			wp_list_pluck( $links, 'label' )
		);
	}

	/** @return list<array<string,string>> Load In (unticked), Day 1 and Day 2, deliberately stored out of order. */
	private static function three_day_rows(): array {
		return array(
			array( 'label' => 'Day 2', 'start' => '2030-07-02 09:00' ),
			array( 'label' => 'Load In', 'start' => '2030-06-30 16:00', 'end' => '21:00', 'skip' => '1' ),
			array( 'label' => 'Day 1', 'start' => '2030-07-01 09:00' ),
		);
	}

	/** @param string $text Wall-clock time in the site zone. */
	private function set_now( string $text ): void {
		remove_all_filters( 'hyperpress_competition_now' );
		add_filter( 'hyperpress_competition_now', fn() => new \DateTimeImmutable( $text, wp_timezone() ) );
	}

	public function test_when_is_the_start_of_the_day_in_progress_or_the_next_counted_day(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id    = $this->competition( array(), self::three_day_rows() );
		$cases = array(
			'2030-06-29 08:00' => '2030-07-01T09:00:00-04:00',
			'2030-07-01 12:00' => '2030-07-01T09:00:00-04:00',
			'2030-07-01 19:59:59' => '2030-07-01T09:00:00-04:00',
			'2030-07-01 20:00' => '2030-07-02T09:00:00-04:00',
			'2030-07-09 12:00' => '2030-07-02T09:00:00-04:00',
		);
		foreach ( $cases as $now => $when ) {
			$this->set_now( $now );
			$this->assertSame( $when, Details::for_post( $id )['when']->format( 'c' ), $now );
		}
	}

	public function test_when_is_null_when_no_day_is_counted(): void {
		$id = $this->competition( array(), array( array( 'label' => 'Day 1', 'start' => '2030-07-01 09:00', 'skip' => '1' ) ) );

		$this->assertNull( Details::for_post( $id )['when'] );
		$this->assertCount( 1, Details::for_post( $id )['days'] );
	}

	public function test_days_lists_every_day_in_order(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$days = Details::for_post( $this->competition( array(), self::three_day_rows() ) )['days'];

		$this->assertSame( array( 'Load In', 'Day 1', 'Day 2' ), wp_list_pluck( $days, 'label' ) );
		foreach ( $days as $day ) {
			$this->assertSame( array( 'label', 'start', 'state' ), array_keys( $day ) );
			$this->assertInstanceOf( \DateTimeImmutable::class, $day['start'] );
		}
		$this->assertSame( '2030-06-30T16:00:00-04:00', $days[0]['start']->format( 'c' ) );
	}

	/** @return list<string> The `state` of every day at the given wall-clock time. */
	private function states_at( int $id, string $now ): array {
		$this->set_now( $now );
		return wp_list_pluck( Details::for_post( $id )['days'], 'state' );
	}

	public function test_days_carry_a_state_that_follows_each_days_end(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id    = $this->competition( array(), self::three_day_rows() );
		$cases = array(
			'2030-06-29 08:00'    => array( 'upcoming', 'current', 'upcoming' ),
			'2030-06-30 20:30'    => array( 'upcoming', 'current', 'upcoming' ),
			'2030-06-30 21:00'    => array( 'past', 'current', 'upcoming' ),
			'2030-07-01 09:00'    => array( 'past', 'current', 'upcoming' ),
			'2030-07-01 12:00'    => array( 'past', 'current', 'upcoming' ),
			'2030-07-01 19:59:59' => array( 'past', 'current', 'upcoming' ),
			'2030-07-01 20:00'    => array( 'past', 'past', 'current' ),
			'2030-07-02 12:00'    => array( 'past', 'past', 'current' ),
			'2030-07-02 19:59:59' => array( 'past', 'past', 'current' ),
			'2030-07-02 20:00'    => array( 'past', 'past', 'past' ),
			'2030-07-09 12:00'    => array( 'past', 'past', 'past' ),
		);
		foreach ( $cases as $now => $states ) {
			$this->assertSame( $states, $this->states_at( $id, $now ), $now );
		}
	}

	public function test_a_setting_change_moves_the_switch(): void {
		update_option( 'timezone_string', 'America/New_York' );
		update_option( Settings::OPTION, array( Settings::DAY_END => '18:00' ) );
		$id = $this->competition( array(), self::three_day_rows() );

		$this->assertSame( array( 'past', 'current', 'upcoming' ), $this->states_at( $id, '2030-07-01 17:59' ) );
		$this->assertSame( array( 'past', 'past', 'current' ), $this->states_at( $id, '2030-07-01 18:00' ) );
		delete_option( Settings::OPTION );
	}

	public function test_the_switch_holds_on_a_dst_date(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id = $this->competition( array(), array( array( 'label' => 'Day 1', 'start' => '2030-03-09 12:00' ), array( 'label' => 'Day 2', 'start' => '2030-03-10 12:00' ) ) );

		$this->assertSame( array( 'current', 'upcoming' ), $this->states_at( $id, '2030-03-09 19:59' ) );
		$this->assertSame( array( 'past', 'current' ), $this->states_at( $id, '2030-03-09 20:00' ) );
		$this->assertSame( array( 'past', 'current' ), $this->states_at( $id, '2030-03-10 19:59' ) );
		$this->assertSame( array( 'past', 'past' ), $this->states_at( $id, '2030-03-10 20:00' ) );
	}

	public function test_the_clock_is_compared_in_the_site_zone(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id = $this->competition( array(), array( array( 'label' => 'Day 1', 'start' => '2030-07-01 20:00' ) ) );
		// 2030-07-02 01:00 UTC is still 21:00 on July 1 in New York.
		remove_all_filters( 'hyperpress_competition_now' );
		add_filter( 'hyperpress_competition_now', fn() => new \DateTimeImmutable( '2030-07-02 01:00', new \DateTimeZone( 'UTC' ) ) );

		$this->assertSame( array( 'current' ), wp_list_pluck( Details::for_post( $id )['days'], 'state' ) );
	}

	public function test_no_day_is_current_when_none_is_counted(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id = $this->competition( array(), array( array( 'label' => 'A', 'start' => '2030-07-01 09:00', 'skip' => '1' ), array( 'label' => 'B', 'start' => '2030-07-02 09:00', 'skip' => '1' ) ) );

		$this->assertSame( array( 'upcoming', 'upcoming' ), $this->states_at( $id, '2030-07-01 12:00' ) );
		$this->assertSame( array( 'past', 'upcoming' ), $this->states_at( $id, '2030-07-01 20:00' ) );
		$this->assertSame( array( 'past', 'past' ), $this->states_at( $id, '2030-07-02 20:00' ) );
	}

	public function test_a_row_end_overrides_the_setting_for_the_states(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id = $this->competition( array(), array( array( 'label' => 'Day 1', 'start' => '2030-07-01 09:00', 'end' => '22:00' ), array( 'label' => 'Day 2', 'start' => '2030-07-02 09:00' ) ) );

		$this->assertSame( array( 'current', 'upcoming' ), $this->states_at( $id, '2030-07-01 21:59' ) );
		$this->assertSame( array( 'past', 'current' ), $this->states_at( $id, '2030-07-01 22:00' ) );
	}

	public function test_days_has_one_entry_for_a_single_day(): void {
		$id = $this->competition( array(), array( array( 'label' => '', 'start' => '2030-07-01 09:00' ) ) );

		$this->assertCount( 1, Details::for_post( $id )['days'] );
	}
}
