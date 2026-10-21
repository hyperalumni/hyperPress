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
		$direct    = Countdown::render( $id, true, false, true );
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

	public function test_a_day_holds_the_countdown_until_it_ends_then_the_next_day_takes_over(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id    = $this->competition( array(), array(), array( array( 'label' => 'Day 1', 'start' => '2030-07-01 09:00' ), array( 'label' => 'Day 2', 'start' => '2030-07-02 09:00' ) ) );
		$cases = array(
			'2030-07-01 09:00'    => array( '2030-07-01T09:00:00-04:00', 'Day 1' ),
			'2030-07-01 12:00'    => array( '2030-07-01T09:00:00-04:00', 'Day 1' ),
			'2030-07-01 19:59:59' => array( '2030-07-01T09:00:00-04:00', 'Day 1' ),
			'2030-07-01 20:00'    => array( '2030-07-02T09:00:00-04:00', 'Day 2' ),
			'2030-07-02 20:00'    => array( '2030-07-02T09:00:00-04:00', 'Day 2' ),
			'2030-07-09 12:00'    => array( '2030-07-02T09:00:00-04:00', 'Day 2' ),
		);
		foreach ( $cases as $now => $expected ) {
			$this->set_now( $now );
			$output = Countdown::render( $id );
			$this->assertStringContainsString( 'data-date="' . $expected[0] . '"', $output, $now );
			$this->assertStringContainsString( '>' . $expected[1] . '</h2>', $output, $now );
		}
	}

	public function test_a_row_end_extends_the_day_and_a_skipped_day_never_holds_the_countdown(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id = $this->competition( array(), array(), array( array( 'label' => 'Load In', 'start' => '2030-06-30 16:00', 'end' => '21:00', 'skip' => '1' ), array( 'label' => 'Day 1', 'start' => '2030-07-01 09:00', 'end' => '22:00' ), array( 'label' => 'Day 2', 'start' => '2030-07-02 09:00' ) ) );

		$this->set_now( '2030-06-30 18:00' );
		$this->assertStringContainsString( '>Day 1</h2>', Countdown::render( $id ) );
		$this->set_now( '2030-07-01 21:59' );
		$this->assertStringContainsString( '>Day 1</h2>', Countdown::render( $id ) );
		$this->set_now( '2030-07-01 22:00' );
		$this->assertStringContainsString( '>Day 2</h2>', Countdown::render( $id ) );
	}

	public function test_the_shortcode_heading_follows_the_same_switch(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$id = $this->titled( 'UNH District Event 2026' );
		update_post_meta( $id, Schedule::FIELD, array( array( 'label' => 'Day 1', 'start' => '2030-07-01 09:00' ), array( 'label' => 'Day 2', 'start' => '2030-07-02 09:00' ) ) );

		$this->set_now( '2030-07-01 19:59' );
		$this->assertStringContainsString( '>UNH District Event 2026: Day 1</h2>', do_shortcode( '[hyperpress_competition_countdown id="' . $id . '"]' ) );
		$this->set_now( '2030-07-01 20:00' );
		$this->assertStringContainsString( '>UNH District Event 2026: Day 2</h2>', do_shortcode( '[hyperpress_competition_countdown id="' . $id . '"]' ) );
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

	/** @return int A competition titled `$title` with one day labelled `$label` (an empty label is stored as such). */
	private function titled( string $title, string $label = 'Day 1' ): int {
		return $this->competition( array(), array( 'post_title' => $title ), array( array( 'label' => $label, 'start' => '2030-07-01 10:00' ) ) );
	}

	public function test_shortcode_heading_is_the_title_and_the_day_label(): void {
		$id = $this->titled( 'UNH District Event 2026' );

		$this->assertStringContainsString( '<h2 class="cell align-center">UNH District Event 2026: Day 1</h2>', do_shortcode( '[hyperpress_competition_countdown id="' . $id . '"]' ) );
	}

	public function test_shortcode_without_a_label_heads_with_the_title_only(): void {
		$id = $this->titled( 'UNH District Event 2026', '' );

		$this->assertStringContainsString( '<h2 class="cell align-center">UNH District Event 2026</h2>', do_shortcode( '[hyperpress_competition_countdown id="' . $id . '"]' ) );
	}

	public function test_render_defaults_to_the_label_only(): void {
		$id = $this->titled( 'UNH District Event 2026' );

		$this->assertStringContainsString( '<h2 class="cell align-center">Day 1</h2>', Countdown::render( $id, true, true ) );
		$this->assertStringNotContainsString( 'UNH District', Countdown::render( $id ) );
	}

	public function test_render_with_title_prefixes_the_title(): void {
		$id = $this->titled( 'UNH District Event 2026' );

		$this->assertStringContainsString( '<h2 class="cell align-center">UNH District Event 2026: Day 1</h2>', Countdown::render( $id, true, false, true ) );
	}

	public function test_title_special_characters_render_once_and_tags_are_stripped(): void {
		$output = Countdown::render( $this->titled( "Tom & Jerry's <b>Cup</b>" ), true, false, true );

		$this->assertStringContainsString( 'Tom &amp; Jerry', $output );
		$this->assertStringContainsString( 'Cup: Day 1</h2>', $output );
		$this->assertStringNotContainsString( '&amp;amp;', $output );
		$this->assertStringNotContainsString( '&amp;#', $output );
		$this->assertStringNotContainsString( '<b>', $output );
	}

	public function test_an_empty_title_leaves_the_label_only(): void {
		$id = $this->titled( '' );

		$this->assertStringContainsString( '<h2 class="cell align-center">Day 1</h2>', Countdown::render( $id, true, false, true ) );
	}

	public function test_an_empty_title_and_label_print_no_heading(): void {
		$this->assertStringNotContainsString( '<h2', Countdown::render( $this->titled( '', '' ), true, false, true ) );
	}

	public function test_shortcode_gives_nothing_without_a_counted_day(): void {
		$id = $this->competition( array(), array( 'post_title' => 'UNH District Event 2026' ), array( array( 'label' => 'Day 1', 'start' => '2030-07-01 10:00', 'skip' => '1' ) ) );

		$this->assertSame( '', do_shortcode( '[hyperpress_competition_countdown id="' . $id . '"]' ) );
	}
}
