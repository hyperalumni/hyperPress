<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Countdown;
use HyperPress\Competition\Schedule;
use HyperPress\Competition\Settings;
use HyperPress\Countdown\Assets;
use WP_UnitTestCase;

final class CountdownTest extends WP_UnitTestCase {

	private const HANDLE = 'hyperpress-countdown-timecircles';
	/** Shorthand in a fixture's meta for a one-day schedule that counts down. */
	private const START  = 'start_shorthand';
	private const VENUE  = 'hyperpress_competition_venue_name';

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
		Countdown::register();
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb_posts'] );
		remove_all_filters( 'hyperpress_competition_now' );
		delete_option( Settings::OPTION );
		delete_option( 'timezone_string' );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	/**
	 * @param array<string,mixed>            $meta Meta fixtures; self::START becomes a one-row schedule.
	 * @param array<string,mixed>            $post Post overrides.
	 * @param list<array<string,mixed>>|null $days Raw schedule rows, stored as given.
	 */
	private function competition( array $meta = array(), array $post = array(), ?array $days = null ): int {
		$id = self::factory()->post->create(
			array_merge( array( 'post_type' => 'hyper_competition', 'post_status' => 'publish' ), $post )
		);
		if ( isset( $meta[ self::START ] ) ) {
			$days = array( array( 'label' => '', 'start' => $meta[ self::START ] ) );
			unset( $meta[ self::START ] );
		}
		if ( null !== $days ) {
			update_post_meta( $id, Schedule::FIELD, $days );
		}
		$GLOBALS['hyperpress_test_rwmb_posts'][ $id ] = $meta;
		return $id;
	}

	private function inline_js(): string {
		return implode( "\n", (array) wp_scripts()->get_data( self::HANDLE, 'after' ) );
	}

	public function test_renders_the_countdown_from_the_start_date(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id     = $this->competition( array( self::START => '2030-07-01 10:00', self::VENUE => 'Memorial Field' ) );
		$output = Countdown::render( $id );

		$this->assertStringContainsString( 'data-date="2030-07-01T10:00:00-04:00"', $output );
		$this->assertStringContainsString( 'Memorial Field', $output );
		$this->assertStringNotContainsString( '<time>', $output );
	}

	public function test_location_can_be_hidden_and_never_prints_an_array(): void {
		$id = $this->competition( array( self::START => '2030-07-01 10:00', self::VENUE => 'Memorial Field' ) );

		$this->assertStringContainsString( 'Memorial Field', Countdown::render( $id ) );
		$this->assertStringNotContainsString( 'Memorial Field', Countdown::render( $id, false ) );

		$array_id = $this->competition( array( self::START => '2030-07-01 10:00', self::VENUE => array( 'x' ) ) );
		$this->assertStringNotContainsString( 'Array', Countdown::render( $array_id ) );
	}

	public function test_date_is_shown_only_when_asked_for(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id = $this->competition( array( self::START => '2030-07-01 10:00', self::VENUE => 'Memorial Field' ) );

		$with = Countdown::render( $id, true, true );
		$this->assertStringContainsString( '<time>Monday July 01, 2030 @ 10:00AM</time>', $with );
		$this->assertStringContainsString( 'Memorial Field', $with );
		$this->assertStringNotContainsString( '<time>', Countdown::render( $id, true ) );
		$this->assertStringNotContainsString( '<time>', do_shortcode( '[hyperpress_competition_countdown id="' . $id . '"]' ) );
	}

	public function test_past_start_still_renders_and_the_setting_reaches_the_script(): void {
		update_option( Settings::OPTION, array( Settings::PAST_ZERO => '1' ) );
		$id     = $this->competition( array( self::START => '2001-01-01 10:00' ) );
		$output = Countdown::render( $id );

		$this->assertStringContainsString( 'data-date="2001-01-01T10:00:00', $output );
		$this->assertStringContainsString( '"count_past_zero": true', $this->inline_js() );
	}

	/** @return array<string,array{0:array<string,mixed>}> */
	public static function unusable_dates(): array {
		return array(
			'no meta' => array( array() ),
			'junk'    => array( array( self::START => 'junk' ) ),
		);
	}

	/**
	 * @dataProvider unusable_dates
	 * @param array<string,mixed> $meta
	 */
	public function test_nothing_without_a_usable_date( array $meta ): void {
		$id = $this->competition( $meta );

		$this->assertSame( '', Countdown::render( $id ) );
	}

	public function test_nothing_for_other_posts_or_drafts(): void {
		$meta = array( self::START => '2030-07-01 10:00' );

		$post = self::factory()->post->create( array( 'post_type' => 'post', 'post_status' => 'publish' ) );
		update_post_meta( $post, Schedule::FIELD, array( array( 'start' => $meta[ self::START ] ) ) );
		$this->assertSame( '', Countdown::render( $post ) );

		$draft = $this->competition( $meta, array( 'post_status' => 'draft' ) );
		$this->assertSame( '', Countdown::render( $draft ) );
	}

	public function test_shortcode_renders_the_same_markup_for_an_id(): void {
		$id        = $this->competition( array( self::START => '2030-07-01 10:00', self::VENUE => 'Memorial Field' ) );
		$strip     = static fn( string $html ): string => (string) preg_replace( '/\d+/', 'N', (string) preg_replace( '/id="[^"]*"/', 'id=""', $html ) );
		$direct    = Countdown::render( $id );
		$shortcode = do_shortcode( '[hyperpress_competition_countdown id="' . $id . '"]' );

		$this->assertNotSame( '', $direct );
		$this->assertSame( $strip( $direct ), $strip( $shortcode ) );
		$this->assertSame( '', do_shortcode( '[hyperpress_competition_countdown]' ) );
	}

	/** @param string $text Wall-clock time in the site zone. */
	private function set_now( string $text ): void {
		remove_all_filters( 'hyperpress_competition_now' );
		add_filter( 'hyperpress_competition_now', fn() => new \DateTimeImmutable( $text, wp_timezone() ) );
	}

	public function test_label_is_the_heading(): void {
		$id = $this->competition( array(), array(), array( array( 'label' => 'Day 1', 'start' => '2030-07-01 10:00' ) ) );

		$this->assertStringContainsString( '<h2 class="cell align-center">Day 1</h2>', Countdown::render( $id ) );
	}

	public function test_no_label_means_no_heading(): void {
		$id = $this->competition( array( self::START => '2030-07-01 10:00' ) );

		$this->assertStringNotContainsString( '<h2', Countdown::render( $id ) );
	}

	public function test_label_is_escaped(): void {
		$id     = $this->competition( array(), array(), array( array( 'label' => 'A <b>&</b>', 'start' => '2030-07-01 10:00' ) ) );
		$output = Countdown::render( $id );

		$this->assertStringContainsString( 'A &lt;b&gt;&amp;&lt;/b&gt;', $output );
		$this->assertStringNotContainsString( '<b>', $output );
	}

	public function test_targets_the_next_counted_day(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$this->set_now( '2030-06-30 08:00' );
		$id     = $this->competition(
			array(),
			array(),
			array( array( 'label' => 'Load In', 'start' => '2030-06-30 16:00', 'skip' => '1' ), array( 'label' => 'Day 1', 'start' => '2030-07-01 09:00' ) )
		);
		$output = Countdown::render( $id );

		$this->assertStringContainsString( 'data-date="2030-07-01T09:00:00-04:00"', $output );
		$this->assertStringContainsString( '>Day 1</h2>', $output );
		$this->assertStringNotContainsString( 'Load In', $output );
	}

	public function test_between_days_targets_the_next_day_and_after_the_last_keeps_the_last_day(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id = $this->competition(
			array(),
			array(),
			array( array( 'label' => 'Day 1', 'start' => '2030-07-01 09:00' ), array( 'label' => 'Day 2', 'start' => '2030-07-02 09:00' ) )
		);

		$this->set_now( '2030-07-01 12:00' );
		$between = Countdown::render( $id );
		$this->assertStringContainsString( 'data-date="2030-07-02T09:00:00-04:00"', $between );
		$this->assertStringContainsString( '>Day 2</h2>', $between );

		$this->set_now( '2030-07-09 12:00' );
		$after = Countdown::render( $id );
		$this->assertStringContainsString( 'data-date="2030-07-02T09:00:00-04:00"', $after );
		$this->assertStringContainsString( '>Day 2</h2>', $after );
	}

	public function test_no_counted_day_gives_nothing(): void {
		$id = $this->competition(
			array(),
			array(),
			array(
				array( 'label' => 'Day 1', 'start' => '2030-07-01 09:00', 'skip' => '1' ),
				array( 'label' => 'Day 2', 'start' => '2030-07-02 09:00', 'skip' => '1' ),
			)
		);

		$this->assertSame( '', Countdown::render( $id ) );
	}

	public function test_show_date_and_venue_are_unchanged(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id     = $this->competition( array( self::VENUE => 'Memorial Field' ), array(), array( array( 'label' => 'Day 1', 'start' => '2030-07-01 10:00' ) ) );
		$output = Countdown::render( $id, true, true );

		$this->assertStringContainsString( '<time>Monday July 01, 2030 @ 10:00AM</time>', $output );
		$this->assertStringContainsString( 'Memorial Field', $output );
	}

	public function test_old_start_date_key_is_ignored(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'hyper_competition', 'post_status' => 'publish' ) );
		update_post_meta( $id, 'hyperpress_competition_event_start_date', '2030-07-01 10:00' );

		$this->assertSame( '', Countdown::render( $id ) );
	}
}
