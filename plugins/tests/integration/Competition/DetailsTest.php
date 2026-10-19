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
				'hyperpress_competition_stream_url'         => array( 'url' => 'https://example.org/s', 'title' => '' ),
				'hyperpress_competition_ne_first_event_url' => array( 'url' => 'https://example.org/ne', 'title' => 'Custom' ),
				'hyperpress_competition_ftc_ma_event_url'   => array( 'url' => '', 'title' => 'Dropped' ),
				'hyperpress_competition_first_event_url'    => array( 'url' => 'https://example.org/f' ),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array(
				array( 'label' => 'Custom', 'url' => 'https://example.org/ne', 'target' => '_blank' ),
				array( 'label' => 'FIRST event', 'url' => 'https://example.org/f', 'target' => '_blank' ),
				array( 'label' => 'Stream', 'url' => 'https://example.org/s', 'target' => '_blank' ),
			),
			$details['links']
		);
	}

	public function test_links_with_rejected_or_empty_urls_are_dropped(): void {
		$id      = $this->competition(
			array(
				'hyperpress_competition_ne_first_event_url' => array( 'url' => 'javascript:alert(1)', 'title' => 'Bad' ),
				'hyperpress_competition_ftc_ma_event_url'   => array( 'url' => '', 'title' => 'Empty' ),
				'hyperpress_competition_stream_url'         => array( 'url' => 'https://example.org/s', 'title' => '' ),
			)
		);
		$details = Details::for_post( $id );

		$this->assertSame(
			array( array( 'label' => 'Stream', 'url' => 'https://example.org/s', 'target' => '_blank' ) ),
			$details['links']
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
