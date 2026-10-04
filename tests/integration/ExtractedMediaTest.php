<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class ExtractedMediaTest extends WP_UnitTestCase {

	public function test_theme_alone_does_not_add_svg_or_avif_uploads(): void {
		if ( ! getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ) {
			$this->markTestSkipped( 'Run with HYPERPRESS_TEST_WITHOUT_EXTRACTED=1' );
		}
		$mimes = apply_filters( 'upload_mimes', array() );
		$this->assertArrayNotHasKey( 'svg', $mimes );
		$this->assertArrayNotHasKey( 'avif', $mimes );
		$this->assertFalse( has_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_nggallery' ) );
	}

	public function test_with_the_media_plugin_uploads_are_allowed(): void {
		if ( getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ) {
			$this->markTestSkipped( 'Needs the media plugin' );
		}
		$mimes = apply_filters( 'upload_mimes', array() );
		$this->assertArrayHasKey( 'svg', $mimes );
		$this->assertArrayHasKey( 'avif', $mimes );
	}
}
