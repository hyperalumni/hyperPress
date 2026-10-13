<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Banner;
use WP_UnitTestCase;

/**
 * Lives in the integration suite because wp_kses_post() is not available to the unit bootstrap.
 */
final class BannerSanitizeTest extends WP_UnitTestCase {

	public function test_keeps_allowed_markup(): void {
		$this->assertSame( 'a <em>x</em> b', Banner::sanitize_subtitle( 'a <em>x</em> b' ) );
	}

	public function test_removes_script_tags(): void {
		$this->assertSame( 'okalert(1)', Banner::sanitize_subtitle( 'ok<script>alert(1)</script>' ) );
	}

	public function test_non_scalars_become_an_empty_string(): void {
		$this->assertSame( '', Banner::sanitize_subtitle( array( 'x' ) ) );
		$this->assertSame( '', Banner::sanitize_subtitle( null ) );
		$this->assertSame( '', Banner::sanitize_subtitle( new \stdClass() ) );
	}

	public function test_scalars_are_cast_to_string(): void {
		$this->assertSame( '5', Banner::sanitize_subtitle( 5 ) );
	}
}
