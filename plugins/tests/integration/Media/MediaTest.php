<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\Breadcrumbs as NextGenBreadcrumbs;
use WP_UnitTestCase;

final class MediaTest extends WP_UnitTestCase {

	public function test_uploads_are_left_to_core_and_safe_svg(): void {
		// AVIF is allowed by WordPress core; SVG (with sanitizing) by the Safe SVG plugin.
		$mimes = apply_filters( 'upload_mimes', get_allowed_mime_types() );
		$this->assertSame( 'image/avif', $mimes['avif'] ?? null, 'core allows AVIF' );
		$this->assertArrayNotHasKey( 'svg', $mimes, 'this plugin must not enable SVG' );
		$this->assertFalse( class_exists( 'HyperPress\\Media\\Uploads\\Svg' ) );
		$this->assertFalse( class_exists( 'HyperPress\\Media\\Uploads\\Avif' ) );
	}

	public function test_nextgen_breadcrumbs_only_hook_when_nextgen_is_present(): void {
		$hooked = (bool) has_filter( 'hyperpress_breadcrumbs_content', array( NextGenBreadcrumbs::class, 'add' ) );
		$this->assertSame( class_exists( 'Imagely\NGG\DataMappers\Album' ), $hooked );
	}
}
