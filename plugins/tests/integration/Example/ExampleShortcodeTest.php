<?php
namespace HyperPress\Tests\Integration\Example;

use HyperPress\Example\Plugin;
use HyperPress\Example\Shortcode;
use WP_UnitTestCase;

final class ExampleShortcodeTest extends WP_UnitTestCase {

	private const HANDLE = 'hp-example-uikit';

	public function set_up(): void {
		parent::set_up();
		// The plugin is not part of the shared load order; load it the way WordPress would, then boot it.
		require_once hyperpress_tests_find_main_file( 'hyperpress-example' );
		Plugin::boot();
		$GLOBALS['wp_styles'] = null;
	}

	public function tear_down(): void {
		$GLOBALS['wp_styles'] = null;
		parent::tear_down();
	}

	public function test_shortcode_is_registered(): void {
		$this->assertTrue( shortcode_exists( 'hp_uikit_card' ) );
	}

	public function test_render_wraps_title_and_content_in_a_uikit_card(): void {
		$output = Shortcode::render( array( 'title' => 'Hello' ), 'Body text' );

		$this->assertSame(
			'<div class="uk-card uk-card-default uk-card-body hp-uikit-card"><h3 class="uk-card-title">Hello</h3>Body text</div>',
			$output
		);
	}

	public function test_shortcode_renders_through_do_shortcode(): void {
		$output = do_shortcode( '[hp_uikit_card title="Hi"]Inside[/hp_uikit_card]' );

		$this->assertStringContainsString( '<h3 class="uk-card-title">Hi</h3>', $output );
		$this->assertStringContainsString( 'Inside</div>', $output );
	}

	public function test_title_is_escaped(): void {
		$output = Shortcode::render( array( 'title' => '<script>alert(1)</script>' ), '' );

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $output );
	}

	public function test_content_is_sanitised(): void {
		$output = Shortcode::render( array(), '<script>alert(1)</script><em>ok</em>' );

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '<em>ok</em>', $output );
	}

	public function test_no_title_means_no_heading(): void {
		$output = Shortcode::render( array(), 'Only body' );

		$this->assertStringNotContainsString( '<h3', $output );
		$this->assertSame( '<div class="uk-card uk-card-default uk-card-body hp-uikit-card">Only body</div>', $output );
	}

	public function test_stylesheet_is_registered_but_enqueued_only_when_rendered(): void {
		do_action( 'wp_enqueue_scripts' );

		$this->assertTrue( wp_style_is( self::HANDLE, 'registered' ) );
		$this->assertFalse( wp_style_is( self::HANDLE, 'enqueued' ) );

		Shortcode::render( array( 'title' => 'x' ), '' );

		$this->assertTrue( wp_style_is( self::HANDLE, 'enqueued' ) );
	}
}
