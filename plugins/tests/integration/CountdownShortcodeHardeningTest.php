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
		$this->assertStringContainsString( 'data-date="2030-01-01 10:00"', $output );
		$this->assertStringContainsString( '<h2 class="cell align-center">Big Game</h2>', $output );
		$this->assertStringContainsString( '<h4 class="cell align-center">Field 1</h4>', $output );
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
