<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class FontAwesomeDependencyTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles']  = null;
	}

	public function test_the_main_stylesheet_depends_on_fontawesome_when_the_handle_is_registered(): void {
		do_action( 'wp_enqueue_scripts' );

		$this->assertTrue( wp_style_is( 'fontawesome', 'registered' ) );
		$this->assertContains( 'fontawesome', wp_styles()->registered['main-stylesheet']->deps );
	}

	public function test_the_dependencies_include_fontawesome_only_when_it_is_registered(): void {
		$this->assertContains( 'fontawesome', \hyperpress_main_style_deps( true ) );
		$this->assertNotContains( 'fontawesome', \hyperpress_main_style_deps( false ) );
	}

	public function test_the_fonts_are_always_dependencies(): void {
		foreach ( array( true, false ) as $registered ) {
			$this->assertSame( array( 'oswald', 'opensans' ), array_slice( \hyperpress_main_style_deps( $registered ), 0, 2 ) );
		}
	}

	public function test_the_default_follows_the_registered_handle(): void {
		wp_register_style( 'fontawesome', 'https://example.org/fa.css', array(), '1' );
		$this->assertContains( 'fontawesome', \hyperpress_main_style_deps() );

		wp_deregister_style( 'fontawesome' );
		$this->assertNotContains( 'fontawesome', \hyperpress_main_style_deps() );
	}
}
