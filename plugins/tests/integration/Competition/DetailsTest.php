<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Details;
use WP_UnitTestCase;

final class DetailsTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb_posts'] );
		delete_option( 'timezone_string' );
		parent::tear_down();
	}

	/** @param array<string,mixed> $meta */
	private function competition( array $meta ): int {
		$id = self::factory()->post->create( array( 'post_type' => 'hyper_competition', 'post_status' => 'publish' ) );
		if ( isset( $meta['hyperpress_competition_event_start_date'] ) ) {
			update_post_meta( $id, 'hyperpress_competition_event_start_date', $meta['hyperpress_competition_event_start_date'] );
			unset( $meta['hyperpress_competition_event_start_date'] );
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
				array( 'label' => 'Custom', 'url' => 'https://external.example/ne', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'FIRST event', 'url' => 'https://external.example/f', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
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
			array( array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ),
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
			array( array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ),
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
			array( array( 'label' => 'Watch live', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ),
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
			array( array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ),
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
				array( 'label' => 'NE FIRST event', 'url' => 'https://external.example/ne', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'FTC MA event', 'url' => 'https://external.example/ftc', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'FIRST event', 'url' => 'https://external.example/f', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'FRC Events', 'url' => 'https://frc-events.firstinspires.org/2026/MABOS', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'The Blue Alliance', 'url' => 'https://www.thebluealliance.com/event/2026mabos', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'Agenda', 'url' => 'https://external.example/a', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
			),
			$details['links']
		);
	}

	public function test_tba_key_is_trimmed_and_lowercased(): void {
		$id      = $this->competition( array( 'hyperpress_competition_tba_event_key' => '  2026MABos ' ) );
		$details = Details::for_post( $id );

		$this->assertSame(
			array( array( 'label' => 'The Blue Alliance', 'url' => 'https://www.thebluealliance.com/event/2026mabos', 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ),
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
				array( 'label' => 'FRC Events', 'url' => 'https://frc-events.firstinspires.org/2026/MABOS', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'Schedule', 'url' => 'https://external.example/agenda', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
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
				array( 'label' => 'Main stage', 'url' => 'https://external.example/s1', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'Stream 2', 'url' => 'https://external.example/s2', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
				array( 'label' => 'Stream 3', 'url' => 'https://external.example/s3', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
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
			array( array( 'label' => 'Stream', 'url' => 'https://external.example/s1', 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ),
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
			array( array( 'label' => 'Ok', 'url' => 'https://external.example/ok', 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ),
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
				array( 'label' => 'Agenda', 'url' => $internal, 'target' => '_self', 'rel' => '' ),
				array( 'label' => 'Stream', 'url' => 'https://external.example/s', 'target' => '_blank', 'rel' => 'noopener noreferrer' ),
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
			array( array( 'label' => 'Agenda', 'url' => $pdf, 'target' => '_blank', 'rel' => 'noopener noreferrer' ) ),
			Details::for_post( $id )['links']
		);
	}

	public function test_missing_start_date_gives_null_when(): void {
		$details = Details::for_post( $this->competition( array() ) );

		$this->assertNull( $details['when'] );
		$this->assertSame( array(), $details['links'] );
	}

	public function test_when_uses_the_site_timezone(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id      = $this->competition( array( 'hyperpress_competition_event_start_date' => '2030-01-15 10:00' ) );
		$details = Details::for_post( $id );

		$this->assertSame( '2030-01-15T10:00:00-05:00', $details['when']->format( 'c' ) );
	}
}
