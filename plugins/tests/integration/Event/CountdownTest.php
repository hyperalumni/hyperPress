<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Countdown\Assets;
use HyperPress\Event\Countdown;
use HyperPress\Event\Fields;
use HyperPress\Event\Settings;
use WP_UnitTestCase;

final class CountdownTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
		delete_option( Settings::OPTION );
	}

	public function tear_down(): void {
		delete_option( Settings::OPTION );
		unset( $GLOBALS['post'] );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	/**
	 * @param array<string,mixed> $post Post overrides.
	 */
	private function event( array $post = array(), string $start = '2030-03-10 09:00' ): int {
		$id = self::factory()->post->create( array_merge( array( 'post_type' => 'hyper_event', 'post_status' => 'publish', 'post_title' => 'Spring Open' ), $post ) );
		if ( '' !== $start ) {
			update_post_meta( $id, Fields::START, $start );
		}
		update_post_meta( $id, Fields::VENUE_NAME, 'Main Hall' );

		return $id;
	}

	private function inline_js(): string {
		return implode( "\n", (array) wp_scripts()->get_data( 'hyperpress-countdown-timecircles', 'after' ) );
	}

	public function test_the_shortcode_is_registered(): void {
		$this->assertTrue( shortcode_exists( Countdown::SHORTCODE ) );
		$this->assertSame( 'hyperpress_event_countdown', Countdown::SHORTCODE );
	}

	public function test_explicit_id_renders_through_the_shared_renderer(): void {
		$id = $this->event();

		$html = Countdown::shortcode( array( 'id' => (string) $id ) );

		$this->assertStringContainsString( 'Spring Open', $html );
		$this->assertStringContainsString( 'Main Hall', $html );
		$this->assertStringContainsString( 'data-date="2030-03-10T09:00:00', $html );
		$this->assertStringContainsString( 'id="event-' . $id . '-', $html );
	}

	public function test_no_id_uses_the_current_event(): void {
		$id = $this->event();
		$this->go_to( get_permalink( $id ) );

		$this->assertStringContainsString( 'Spring Open', Countdown::shortcode( array() ) );
	}

	public function test_past_zero_follows_the_setting(): void {
		$id = $this->event();

		Countdown::render( $id );
		$this->assertStringContainsString( '"count_past_zero": false,', $this->inline_js() );

		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
		update_option( Settings::OPTION, array( Settings::COUNTDOWN_PAST_ZERO => '1' ) );

		Countdown::render( $id );
		$this->assertStringContainsString( '"count_past_zero": true,', $this->inline_js() );
	}

	public function test_empty_without_a_start(): void {
		$this->assertSame( '', Countdown::render( $this->event( array(), '' ) ) );

		$bad = $this->event( array(), '' );
		update_post_meta( $bad, Fields::START, 'garbage' );
		$this->assertSame( '', Countdown::render( $bad ) );
	}

	public function test_empty_for_a_draft_or_other_post_type(): void {
		$draft = $this->event( array( 'post_status' => 'draft' ) );
		$post  = self::factory()->post->create( array( 'post_title' => 'Plain post' ) );
		update_post_meta( $post, Fields::START, '2030-03-10 09:00' );

		$this->assertSame( '', Countdown::render( $draft ) );
		$this->assertSame( '', Countdown::render( $post ) );
		$this->assertSame( '', Countdown::render( 999999 ) );
	}

	public function test_empty_outside_an_event_without_notices(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $page ) );

		$this->assertSame( '', Countdown::shortcode( '' ) );
		$this->assertSame( '', Countdown::shortcode( array( 'id' => 'abc' ) ) );
	}
}
