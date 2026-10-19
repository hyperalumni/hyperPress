<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Countdown;
use HyperPress\Competition\Settings;
use HyperPress\Countdown\Assets;
use WP_UnitTestCase;

final class CountdownTest extends WP_UnitTestCase {

	private const HANDLE = 'hyperpress-countdown-timecircles';
	private const START  = 'hyperpress_competition_event_start_date';
	private const VENUE  = 'hyperpress_competition_venue_name';

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
		Countdown::register();
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb_posts'] );
		delete_option( Settings::OPTION );
		delete_option( 'timezone_string' );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	/** @param array<string,mixed> $meta */
	private function competition( array $meta = array(), array $post = array() ): int {
		$id = self::factory()->post->create(
			array_merge( array( 'post_type' => 'hyper_competition', 'post_status' => 'publish' ), $post )
		);
		if ( isset( $meta['hyperpress_competition_event_start_date'] ) ) {
			update_post_meta( $id, 'hyperpress_competition_event_start_date', $meta['hyperpress_competition_event_start_date'] );
			unset( $meta['hyperpress_competition_event_start_date'] );
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
		update_post_meta( $post, self::START, $meta[ self::START ] );
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
}
