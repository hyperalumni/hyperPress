<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class GoogleFontsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_styles'] = null;
		do_action( 'wp_enqueue_scripts' );
	}

	public static function fonts(): array {
		return array(
			'oswald'   => array( 'oswald', 'family=Oswald:wght@200..700' ),
			'opensans' => array( 'opensans', 'family=Open+Sans:ital,wght@0,300..800;1,300..800' ),
		);
	}

	/** @dataProvider fonts */
	public function test_font_is_registered_from_google_with_swap( string $handle, string $family ): void {
		$style = wp_styles()->registered[ $handle ] ?? null;
		$this->assertNotNull( $style, "$handle is registered" );
		$this->assertStringStartsWith( 'https://fonts.googleapis.com/css2?', $style->src );
		$this->assertStringContainsString( $family, $style->src );
		$this->assertStringEndsWith( '&display=swap', $style->src, 'display=swap is spelled correctly' );
	}

	public function test_the_main_stylesheet_depends_on_both_fonts(): void {
		$deps = wp_styles()->registered['main-stylesheet']->deps;
		$this->assertContains( 'oswald', $deps );
		$this->assertContains( 'opensans', $deps );
	}
}
