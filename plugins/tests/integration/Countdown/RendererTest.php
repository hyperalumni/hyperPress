<?php
namespace HyperPress\Tests\Integration\Countdown;

use HyperPress\Countdown\Assets;
use HyperPress\Countdown\Renderer;
use WP_UnitTestCase;

final class RendererTest extends WP_UnitTestCase {

	private const HANDLE = 'hyperpress-countdown-timecircles';

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
	}

	public function tear_down(): void {
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	private function when( string $text ): \DateTimeImmutable {
		return new \DateTimeImmutable( $text, new \DateTimeZone( 'America/New_York' ) );
	}

	private function inline_js(): string {
		return implode( "\n", (array) wp_scripts()->get_data( self::HANDLE, 'after' ) );
	}

	public function test_marks_up_the_instant_with_offset(): void {
		$output = Renderer::render(
			$this->when( '2030-07-01 10:00' ),
			array(
				'name'     => 'Big Game',
				'location' => 'Field 1',
			)
		);

		$this->assertStringContainsString( 'data-date="2030-07-01T10:00:00-04:00"', $output );
		$this->assertStringContainsString( '<h2 class="cell align-center">Big Game</h2>', $output );
		$this->assertStringContainsString( '<h4 class="cell align-center">Field 1</h4>', $output );
		$this->assertStringContainsString( '<section class="grid-x countdown align-center">', $output );
	}

	public function test_winter_instant_uses_est_offset(): void {
		$output = Renderer::render( $this->when( '2030-01-01 10:00' ) );

		$this->assertStringContainsString( 'data-date="2030-01-01T10:00:00-05:00"', $output );
	}

	public function test_show_date_false_omits_the_time_line(): void {
		$hidden = Renderer::render( $this->when( '2030-07-01 10:00' ), array( 'show_date' => false ) );
		$shown  = Renderer::render( $this->when( '2030-07-01 10:00' ) );

		$this->assertStringNotContainsString( '<time>', $hidden );
		$this->assertStringContainsString( '<time>Monday July 01, 2030 @ 10:00AM</time>', $shown );
	}

	public function test_past_zero_flag_reaches_the_init_script(): void {
		Renderer::render( $this->when( '2030-07-01 10:00' ) );
		$this->assertStringContainsString( '"count_past_zero": false', $this->inline_js() );

		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
		Renderer::render( $this->when( '2030-07-01 10:00' ), array( 'past_zero' => true ) );
		$this->assertStringContainsString( '"count_past_zero": true', $this->inline_js() );
	}

	public function test_enqueues_assets_and_slug_prefixes_the_element_id(): void {
		$output = Renderer::render( $this->when( '2030-07-01 10:00' ), array( 'slug' => 'competition-7' ) );

		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ) );
		$this->assertTrue( wp_style_is( self::HANDLE, 'enqueued' ) );
		$this->assertMatchesRegularExpression( '/id="competition-7-\d+"/', $output );
	}
}
