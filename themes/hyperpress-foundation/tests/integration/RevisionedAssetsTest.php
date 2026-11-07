<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * With REVISIONING on, the build writes hashed files and a rev-manifest.json per type; everything
 * the theme loads must use those names (a hard-coded name would 404). These tests read the real
 * manifests in dist/ and are skipped when the theme has not been built with revisioning.
 */
final class RevisionedAssetsTest extends WP_UnitTestCase {

	private function dist(): string {
		return dirname( __DIR__, 2 ) . '/dist/assets';
	}

	/** @return array<string,string> */
	private function manifest( string $type ): array {
		$path = $this->dist() . "/{$type}/rev-manifest.json";
		if ( ! is_readable( $path ) ) {
			$this->markTestSkipped( "No {$type} rev-manifest.json: build the theme with REVISIONING on first." );
		}
		$manifest = json_decode( (string) file_get_contents( $path ), true );
		$this->assertIsArray( $manifest );

		return $manifest;
	}

	public function test_every_manifest_entry_points_at_a_file_that_exists(): void {
		foreach ( array( 'css', 'js' ) as $type ) {
			foreach ( $this->manifest( $type ) as $source => $hashed ) {
				$this->assertNotSame( $source, $hashed, "{$source} is revisioned" );
				$this->assertFileExists( $this->dist() . "/{$type}/{$hashed}" );
			}
		}
	}

	public function test_the_enqueued_stylesheet_and_scripts_use_the_revisioned_names(): void {
		$css = $this->manifest( 'css' );
		$js  = $this->manifest( 'js' );

		do_action( 'wp_enqueue_scripts' );

		$this->assertStringEndsWith( '/dist/assets/css/' . $css['app.css'], wp_styles()->registered['main-stylesheet']->src );
		$this->assertStringEndsWith( '/dist/assets/js/' . $js['foundation.js'], wp_scripts()->registered['foundation']->src );
		$this->assertStringEndsWith( '/dist/assets/js/' . $js['app.js'], wp_scripts()->registered['main-script']->src );
	}

	public function test_the_block_editor_style_uses_the_revisioned_name(): void {
		$css = $this->manifest( 'css' );

		// WordPress rejects add_theme_support( 'title-tag' ) after wp_loaded, which has always run in a test.
		$this->setExpectedIncorrectUsage( "add_theme_support( 'title-tag' )" );

		$GLOBALS['editor_styles'] = array();
		do_action( 'after_setup_theme' );

		$styles = array_map( 'strval', (array) $GLOBALS['editor_styles'] );
		$this->assertContains(
			get_stylesheet_directory_uri() . '/dist/assets/css/' . $css['editor.css'],
			$styles,
			'the editor style is the hashed file, not the plain editor.css'
		);
	}
}
