<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\Breadcrumbs as NextGenBreadcrumbs;
use WP_Customize_Manager;
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

	public function test_nextgen_customizer_setting_keeps_its_id_and_the_control_matches(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNotNull( $manager->get_setting( 'hyperpress_nggallery_photos_page' ) );
		$control = $manager->get_control( 'hyperpress_nggallery_photos_page' );
		$this->assertNotNull( $control, 'Control id must equal the setting id' );
	}

	public function test_the_nextgen_section_sits_where_the_old_gallery_section_was(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$section = $manager->get_section( 'hyperpress_media_nextgen' );
		$this->assertNotNull( $section );
		$this->assertSame( 'NextGen Gallery', $section->title );
		$this->assertSame( 140, $section->priority, 'the position of the Meta Box "Gallery" section this one replaces' );
	}
}
