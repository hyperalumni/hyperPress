<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The default logo is an SVG without intrinsic size, so the header CSS must size it.
 */
final class BrandingStylesTest extends TestCase {

	public function test_header_logo_is_sized_in_css(): void {
		$scss = (string) file_get_contents( dirname( __DIR__, 2 ) . '/src/assets/scss/modules/_navigation.scss' );
		$this->assertStringContainsString( '.site-desktop-title .custom-logo', $scss );
	}
}
