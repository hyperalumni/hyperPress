<?php
namespace HyperPress\Tests\Integration\Media;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class MediaReadersTest extends WP_UnitTestCase {

	public function test_the_customizer_no_longer_has_the_nextgen_section_or_setting(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNull( $manager->get_section( 'hyperpress_media_nextgen' ) );
		$this->assertNull( $manager->get_setting( 'hyperpress_nggallery_photos_page' ) );
		$this->assertNull( $manager->get_control( 'hyperpress_nggallery_photos_page' ) );
	}

	public function test_the_customizer_class_file_is_gone(): void {
		$plugins = dirname( __DIR__, 3 );

		$this->assertFileExists( $plugins . '/hyperpress-media/src/Plugin.php', 'sanity check of the derived path' );
		$this->assertFileDoesNotExist( $plugins . '/hyperpress-media/src/NextGen/Customizer.php' );
	}
}
