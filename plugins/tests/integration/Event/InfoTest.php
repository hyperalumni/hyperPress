<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Dates;
use HyperPress\Event\Fields;
use HyperPress\Event\Info;
use HyperPress\Event\Plugin;
use WP_UnitTestCase;

final class InfoTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		update_option( 'date_format', 'j F Y' );
		update_option( 'time_format', 'H:i' );
	}

	public function tear_down(): void {
		unset( $GLOBALS['post'] );
		delete_option( 'timezone_string' );
		parent::tear_down();
	}

	/**
	 * @param array<string,string> $meta Meta to save, by key.
	 */
	private function event( array $meta = array(), string $type = Plugin::POST_TYPE ): int {
		$id = self::factory()->post->create( array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => 'Bar Crawl' ) );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		$this->go_to( get_permalink( $id ) );

		return $id;
	}

	public function test_same_day_event_shows_the_date_and_a_time_range(): void {
		$this->event( array( Fields::START => '2030-03-10 09:00', Fields::END => '2030-03-10 17:30', Fields::VENUE_NAME => 'City Hall' ) );

		$html = Info::shortcode( array() );

		$this->assertStringContainsString( '<div class="grid-x grid-margin-x">', $html );
		$this->assertStringContainsString( '<div class="cell small-12 medium-4"><strong>Date</strong><br>10 March 2030</div>', $html );
		$this->assertStringContainsString( '<strong>Time</strong><br>09:00 – 17:30</div>', $html );
		$this->assertStringContainsString( '<strong>Where</strong><br>City Hall</div>', $html );
		$this->assertStringContainsString( 'Event Info', $html );
		$this->assertStringContainsString( 'id="info"', $html );
		$this->assertLessThan( strpos( $html, 'grid-x' ), strpos( $html, 'Event Info' ) );
	}

	public function test_multi_day_event_shows_a_date_range_and_the_start_time(): void {
		$this->event( array( Fields::START => '2030-03-10 09:00', Fields::END => '2030-03-12 17:30' ) );

		$html = Info::shortcode( array() );

		$this->assertStringContainsString( '<strong>Date</strong><br>10 March 2030 – 12 March 2030</div>', $html );
		$this->assertStringContainsString( '<strong>Time</strong><br>09:00</div>', $html );
	}

	public function test_start_only_shows_neither_a_range_nor_the_default_end(): void {
		$this->event( array( Fields::START => '2030-03-10 09:00' ) );

		$html = Info::shortcode( array() );

		$this->assertStringContainsString( '<strong>Time</strong><br>09:00</div>', $html );
		$this->assertStringNotContainsString( '–', $html );
		$this->assertStringNotContainsString( '23:59', $html );
		$this->assertStringNotContainsString( 'Where', $html );

		// An end that is not after the start is not an explicit end.
		$this->event( array( Fields::START => '2030-03-10 09:00', Fields::END => '2030-03-10 08:00' ) );
		$this->assertStringNotContainsString( '–', Info::shortcode( array() ) );
	}

	public function test_venue_name_and_address_with_line_breaks(): void {
		$this->event(
			array(
				Fields::START         => '2030-03-10 09:00',
				Fields::VENUE_NAME    => 'City Hall',
				Fields::VENUE_ADDRESS => "1 Main St\nSpringfield",
			)
		);

		$this->assertStringContainsString( '<strong>Where</strong><br>City Hall<br>1 Main St<br>' . "\n" . 'Springfield</div>', Info::shortcode( array() ) );
	}

	public function test_venue_without_a_start_still_prints(): void {
		$this->event( array( Fields::VENUE_NAME => 'City Hall' ) );

		$html = Info::shortcode( array() );

		$this->assertStringContainsString( 'City Hall', $html );
		$this->assertStringContainsString( 'Event Info', $html );
		$this->assertStringNotContainsString( 'Date', $html );
		$this->assertStringNotContainsString( 'Time', $html );
	}

	public function test_address_alone_counts_as_a_venue(): void {
		$this->event( array( Fields::VENUE_ADDRESS => '1 Main St' ) );

		$this->assertStringContainsString( '<strong>Where</strong><br>1 Main St</div>', Info::shortcode( array() ) );
	}

	public function test_prints_nothing_without_a_start_and_a_venue(): void {
		$this->event();

		$this->assertSame( '', Info::shortcode( array() ) );
		$this->event( array( Fields::START => 'garbage', Fields::VENUE_NAME => '  ' ) );
		$this->assertSame( '', Info::shortcode( array() ) );
	}

	public function test_prints_nothing_outside_an_event(): void {
		$this->assertSame( '', Info::shortcode( array() ) );

		$this->event( array( Fields::START => '2030-03-10 09:00' ), 'post' );
		$this->assertSame( '', Info::shortcode( array() ) );
		$this->assertSame( '', do_shortcode( '[hyperpress_event_info]' ) );

		$GLOBALS['post'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->assertSame( '', Info::shortcode( '' ) );
	}

	public function test_title_subtitle_anchor_and_colour_go_to_the_banner(): void {
		$this->event( array( Fields::START => '2030-03-10 09:00' ) );

		$html = Info::shortcode(
			array(
				'title'    => 'Details',
				'subtitle' => 'Plan ahead',
				'anchor'   => 'when',
				'color'    => '#112233',
			)
		);

		$this->assertStringContainsString( 'Details', $html );
		$this->assertStringContainsString( 'Plan ahead', $html );
		$this->assertStringContainsString( 'id="when"', $html );
		$this->assertStringContainsString( 'background-color: #112233', $html );
		$this->assertStringNotContainsString( 'Event Info', $html );
	}

	public function test_the_event_colour_is_the_banner_default(): void {
		$this->event( array( Fields::START => '2030-03-10 09:00', Fields::BANNER_COLOR => '#abcdef' ) );

		$this->assertStringContainsString( 'background-color: #abcdef', do_shortcode( '[hyperpress_event_info]' ) );
	}

	public function test_output_is_escaped(): void {
		$this->event(
			array(
				Fields::VENUE_NAME    => '<script>alert(1)</script>',
				Fields::VENUE_ADDRESS => '<b>1 Main</b>',
			)
		);

		$html = Info::shortcode( array() );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<b>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	public function test_site_timezone_and_formats_are_respected(): void {
		update_option( 'timezone_string', 'Asia/Tokyo' );
		update_option( 'date_format', 'Y/m/d' );
		update_option( 'time_format', 'g:i a' );
		$id = $this->event( array( Fields::START => '2030-03-10 23:30', Fields::END => '2030-03-11 01:00' ) );

		$html = Info::shortcode( array() );

		// Wall-clock values as typed, whatever the zone; the formats come from the options.
		$this->assertSame( '2030-03-10 23:30', Dates::start( $id )->format( 'Y-m-d H:i' ) );
		$this->assertStringContainsString( '2030/03/10 – 2030/03/11', $html );
		$this->assertStringContainsString( '<strong>Time</strong><br>11:30 pm</div>', $html );
	}

	public function test_the_shortcode_is_registered(): void {
		$this->assertTrue( shortcode_exists( Info::SHORTCODE ) );
	}
}
