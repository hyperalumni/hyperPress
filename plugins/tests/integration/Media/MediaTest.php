<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\Breadcrumbs as NextGenBreadcrumbs;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class MediaTest extends WP_UnitTestCase {

	public function test_svg_and_avif_mime_types_are_allowed(): void {
		$mimes = apply_filters( 'upload_mimes', array() );
		$this->assertSame( 'image/svg+xml', $mimes['svg'] ?? null );
		$this->assertSame( 'image/avif', $mimes['avif'] ?? null );
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
}
