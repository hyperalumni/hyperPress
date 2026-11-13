<?php
use HyperPress\Event\Dates;
use HyperPress\Event\Fields;

class Event_DatesTest extends WP_UnitTestCase {

	private int $post_id;

	public function set_up(): void {
		parent::set_up();
		update_option( 'timezone_string', 'America/New_York' );
		$this->post_id = self::factory()->post->create( array( 'post_type' => 'hyper_event' ) );
	}

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_event_now' );
		delete_option( 'timezone_string' );
		parent::tear_down();
	}

	private function set_dates( string $start, ?string $end = null ): void {
		update_post_meta( $this->post_id, Fields::START, $start );
		if ( null !== $end ) {
			update_post_meta( $this->post_id, Fields::END, $end );
		}
	}

	public function test_start_is_parsed_in_the_site_timezone(): void {
		$this->set_dates( '2026-11-28 14:00' );
		$start = Dates::start( $this->post_id );

		$this->assertInstanceOf( DateTimeImmutable::class, $start );
		$this->assertSame( 'America/New_York', $start->getTimezone()->getName() );
		$this->assertSame( '2026-11-28 14:00:00', $start->format( 'Y-m-d H:i:s' ) );
	}

	public function test_end_defaults_to_the_end_of_the_start_day(): void {
		$this->set_dates( '2026-11-28 14:00' );

		$this->assertSame( '2026-11-28 23:59:59', Dates::end( $this->post_id )->format( 'Y-m-d H:i:s' ) );
	}

	public function test_valid_end_is_used(): void {
		$this->set_dates( '2026-11-28 14:00', '2026-11-29 10:30' );

		$this->assertSame( '2026-11-29 10:30:00', Dates::end( $this->post_id )->format( 'Y-m-d H:i:s' ) );
	}

	public function test_end_not_after_start_is_ignored(): void {
		$this->set_dates( '2026-11-28 14:00', '2026-11-28 09:00' );
		$this->assertSame( '2026-11-28 23:59:59', Dates::end( $this->post_id )->format( 'Y-m-d H:i:s' ) );

		$this->set_dates( '2026-11-28 14:00', '2026-11-28 14:00' );
		$this->assertSame( '2026-11-28 23:59:59', Dates::end( $this->post_id )->format( 'Y-m-d H:i:s' ) );
	}

	public function test_no_start_gives_null_end_and_never_over(): void {
		$this->assertNull( Dates::start( $this->post_id ) );
		$this->assertNull( Dates::end( $this->post_id ) );
		$this->assertFalse( Dates::is_over( $this->post_id ) );
	}

	public function test_malformed_start_is_null(): void {
		foreach ( array( 'garbage', '2026-02-30 10:00' ) as $value ) {
			$this->set_dates( $value );
			$this->assertNull( Dates::start( $this->post_id ), $value );
			$this->assertNull( Dates::end( $this->post_id ), $value );
		}
	}

	public function test_is_over_is_strict(): void {
		$this->set_dates( '2026-11-28 14:00', '2026-11-28 18:00' );
		$tz = wp_timezone();

		add_filter(
			'hyperpress_event_now',
			static fn() => new DateTimeImmutable( '2026-11-28 18:00:00', $tz )
		);
		$this->assertFalse( Dates::is_over( $this->post_id ) );

		remove_all_filters( 'hyperpress_event_now' );
		add_filter(
			'hyperpress_event_now',
			static fn() => new DateTimeImmutable( '2026-11-28 18:00:01', $tz )
		);
		$this->assertTrue( Dates::is_over( $this->post_id ) );
	}

	public function test_now_filter(): void {
		$fixed = new DateTimeImmutable( '2030-01-02 03:04:05', wp_timezone() );
		add_filter( 'hyperpress_event_now', static fn() => $fixed );

		$this->assertSame( $fixed, Dates::now() );
	}
}
