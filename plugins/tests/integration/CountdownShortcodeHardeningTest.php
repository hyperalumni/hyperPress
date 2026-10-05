<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Countdown\Assets;
use WP_UnitTestCase;

final class CountdownShortcodeHardeningTest extends WP_UnitTestCase {

	private const HANDLE = 'hyperpress-countdown-timecircles';

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb'] );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	/** @param array<string,mixed> $meta */
	private function countdown( array $meta = array(), array $post = array() ): int {
		$GLOBALS['hyperpress_test_rwmb'] = array_merge(
			array( 'hyperpress_countdown_event_date_time' => '2030-01-01 10:00' ),
			$meta
		);
		return self::factory()->post->create(
			array_merge( array( 'post_type' => 'hyper_countdown', 'post_status' => 'publish', 'post_title' => 'Kickoff' ), $post )
		);
	}

	private function render( int|string $id ): string {
		return do_shortcode( '[hyperpress_countdown id="' . $id . '"]' );
	}

	private function inline_js(): string {
		return implode( "\n", (array) wp_scripts()->get_data( self::HANDLE, 'after' ) );
	}

	public function test_valid_published_countdown_still_renders_markup(): void {
		$id     = $this->countdown( array( 'hyperpress_countdown_event_name' => 'Big Game', 'hyperpress_countdown_event_location' => 'Field 1' ) );
		$output = $this->render( $id );

		$this->assertStringContainsString( '<section class="grid-x countdown align-center">', $output );
		$this->assertStringContainsString( 'data-date="2030-01-01T10:00:00+00:00"', $output );
		$this->assertStringContainsString( '<h2 class="cell align-center">Big Game</h2>', $output );
		$this->assertStringContainsString( '<h4 class="cell align-center">Field 1</h4>', $output );
	}

	public function test_rendering_the_same_countdown_twice_gives_distinct_ids_targeted_by_their_own_script(): void {
		$id    = $this->countdown();
		$first = $this->render( $id );
		$again = $this->render( $id );

		$this->assertSame( 1, preg_match( '/<div id="([^"]+)" class="cell" data-date/', $first, $a ) );
		$this->assertSame( 1, preg_match( '/<div id="([^"]+)" class="cell" data-date/', $again, $b ) );
		$this->assertNotSame( $a[1], $b[1], 'element ids must be unique per render' );

		$js = $this->inline_js();
		$this->assertStringContainsString( '$( "#' . $a[1] . '" ).TimeCircles', $js );
		$this->assertStringContainsString( '$( "#' . $b[1] . '" ).TimeCircles', $js );
	}

	public static function site_zones(): array {
		return array(
			'utc'      => array( 'UTC', '+00:00' ),
			'new york' => array( 'America/New_York', '-04:00' ),
			'tokyo'    => array( 'Asia/Tokyo', '+09:00' ),
		);
	}

	/**
	 * The event is fixed in the SITE's time zone (Settings > General): the stored wall-clock value is read in that zone,
	 * and data-date carries its offset, so visitors in any zone count down to the same instant.
	 *
	 * @dataProvider site_zones
	 */
	public function test_data_date_is_the_site_time_zone_instant_for_every_visitor( string $zone, string $offset ): void {
		update_option( 'timezone_string', 'UTC' === $zone ? '' : $zone );
		$id     = $this->countdown( array( 'hyperpress_countdown_event_date_time' => '2026-07-07 00:00' ) );
		$output = $this->render( $id );

		$this->assertSame( 1, preg_match( '/data-date="([^"]+)"/', $output, $m ) );
		$this->assertSame( '2026-07-07T00:00:00' . $offset, $m[1] );
		$stored = new \DateTimeImmutable( '2026-07-07 00:00', wp_timezone() );
		$this->assertSame( $stored->getTimestamp(), ( new \DateTimeImmutable( $m[1] ) )->getTimestamp(), 'same instant as the stored value in the site zone' );
	}
	public function test_stored_markup_is_escaped_in_output_and_inline_script(): void {
		$name = '</script><script>alert(1)</script>';
		$loc  = '"><img src=x onerror=1>';
		$id   = $this->countdown(
			array(
				'hyperpress_countdown_event_name'      => $name,
				'hyperpress_countdown_event_location'  => $loc,
			)
		);

		$output = $this->render( $id );
		$js     = $this->inline_js();

		$this->assertNotSame( '', $output );
		foreach ( array( $name, $loc, '<script>alert(1)', '<img src=x' ) as $raw ) {
			$this->assertStringNotContainsString( $raw, $output, "raw payload in output: $raw" );
			$this->assertStringNotContainsString( $raw, $js, "raw payload in inline script: $raw" );
		}
		$this->assertStringContainsString( '&lt;/script&gt;', $output );
	}

	public function test_slug_is_escaped_in_id_attribute_and_js_selector(): void {
		global $wpdb;
		$id = $this->countdown();
		$wpdb->update( $wpdb->posts, array( 'post_name' => 'x"]);alert(1);//' ), array( 'ID' => $id ) );
		clean_post_cache( $id );

		$output = $this->render( $id );
		$js     = $this->inline_js();

		$this->assertStringNotContainsString( 'id="x"]', $output );
		$this->assertStringContainsString( 'id="x&quot;', $output );
		$this->assertDoesNotMatchRegularExpression( '/(?<!\\\\)"\]\);alert/', $js );
		$this->assertStringContainsString( 'alert(1)', $js, 'payload should remain, but only inside a JSON string' );
	}

	public function test_hostile_color_theme_mods_cannot_break_out_of_the_inline_script(): void {
		foreach ( array( 'days', 'hours', 'minutes', 'seconds' ) as $unit ) {
			set_theme_mod( 'hyperpress_countdown_' . $unit . '_color', 'red"};alert(1);//' );
		}
		$this->render( $this->countdown() );
		$js = $this->inline_js();

		$this->assertStringNotContainsString( 'alert(1)', $js );
		$this->assertSame( 4, preg_match_all( '/"color": ("[^"]*")/', $js, $m ) );
		foreach ( $m[1] as $json ) {
			$this->assertIsString( json_decode( $json ), "not valid JSON: $json" );
		}
		$this->assertSame( array( '"#FC6"', '"#9CF"', '"#BFB"', '"#F99"' ), $m[1], 'invalid colors fall back to the TimeCircles defaults' );
	}

	public function test_valid_color_theme_mods_are_used(): void {
		set_theme_mod( 'hyperpress_countdown_days_color', '#123456' );
		set_theme_mod( 'hyperpress_countdown_seconds_color', '#abc' );
		$this->render( $this->countdown() );
		$js = $this->inline_js();

		$this->assertStringContainsString( '"color": "#123456"', $js );
		$this->assertStringContainsString( '"color": "#abc"', $js );
		$this->assertStringContainsString( '"color": "#9CF"', $js, 'unset colors use the default' );
	}

	public function test_asset_urls_have_no_double_slash_after_the_scheme(): void {
		foreach ( array( wp_scripts(), wp_styles() ) as $registry ) {
			$src = $registry->registered[ self::HANDLE ]->src ?? '';
			$this->assertStringContainsString( 'hyperpress-countdown/assets/', $src );
			$this->assertStringNotContainsString( '//', substr( $src, strpos( $src, '://' ) + 3 ), $src );
		}
	}

	/** @return array<string,array{0:int|string}> */
	public static function non_renderable(): array {
		return array(
			'draft countdown'    => array( 'draft' ),
			'other post type'    => array( 'post' ),
			'non-existent id'    => array( 999999 ),
			'id zero'            => array( 0 ),
			'private countdown'  => array( 'private' ),
			'trashed countdown'  => array( 'trash' ),
		);
	}

	/** @dataProvider non_renderable */
	public function test_only_published_countdowns_render( int|string $case ): void {
		if ( is_int( $case ) ) {
			$id = $case;
		} elseif ( 'post' === $case ) {
			$this->countdown();
			$id = self::factory()->post->create( array( 'post_type' => 'post', 'post_status' => 'publish' ) );
		} else {
			$id = $this->countdown( array(), array( 'post_status' => $case ) );
		}

		$this->assertSame( '', $this->render( $id ) );
		$this->assertSame( '', $this->inline_js() );
	}

	public function test_private_countdown_renders_for_users_who_can_read_it(): void {
		$id = $this->countdown( array(), array( 'post_status' => 'private' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertStringContainsString( 'section class="grid-x countdown', $this->render( $id ) );
	}

	public function test_draft_countdown_is_not_rendered_by_slug(): void {
		$id = $this->countdown( array(), array( 'post_status' => 'draft', 'post_name' => 'draft-cd' ) );
		$this->assertNotSame( 0, $id );

		$this->assertSame( '', do_shortcode( '[hyperpress_countdown slug="draft-cd"]' ) );
	}

	/** @return array<string,array{0:mixed,1:string}> */
	public static function past_zero_values(): array {
		return array(
			'empty string' => array( '', 'false' ),
			'string zero'  => array( '0', 'false' ),
			'int zero'     => array( 0, 'false' ),
			'false'        => array( false, 'false' ),
			'string one'   => array( '1', 'true' ),
			'int one'      => array( 1, 'true' ),
			'true'         => array( true, 'true' ),
			'word false'   => array( 'false', 'false' ),
			'word off'     => array( 'off', 'false' ),
			'word no'      => array( 'no', 'false' ),
			'word true'    => array( 'true', 'true' ),
			'word on'      => array( 'on', 'true' ),
			'array value'  => array( array( '1' ), 'false' ),
		);
	}

	/** @dataProvider past_zero_values */
	public function test_count_past_zero_is_a_strict_boolean_literal( $stored, string $expected ): void {
		$id = $this->countdown( array( 'hyperpress_countdown_past_zero' => $stored ) );
		$this->render( $id );

		$this->assertStringContainsString( '"count_past_zero": ' . $expected . ',', $this->inline_js() );
	}

	public function test_inline_script_is_attached_to_a_jquery_dependent_handle(): void {
		$id     = $this->countdown();
		$output = $this->render( $id );
		$js     = $this->inline_js();

		$this->assertContains( 'jquery', wp_scripts()->registered[ self::HANDLE ]->deps );
		$this->assertStringNotContainsString( '<script', $output );
		$this->assertStringContainsString( 'jQuery( function ( $ )', $js );
		$this->assertStringNotContainsString( '$(document).ready', $js );
		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ) );
	}
}
