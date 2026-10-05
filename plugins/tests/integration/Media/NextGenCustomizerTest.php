<?php
namespace HyperPress\Tests\Integration\Media;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class NextGenCustomizerTest extends WP_UnitTestCase {

	private function setting(): \WP_Customize_Setting {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );
		return $manager->get_setting( 'hyperpress_nggallery_photos_page' );
	}

	public function test_the_photos_page_setting_is_sanitized_to_a_positive_integer(): void {
		$setting = $this->setting();

		$this->assertSame( 12, $setting->sanitize( '12abc' ) );
		$this->assertSame( 3, $setting->sanitize( -3 ) );
		$this->assertSame( 0, $setting->sanitize( 'junk' ) );
	}

	public function test_the_photos_page_setting_defaults_to_zero(): void {
		$this->assertSame( 0, $this->setting()->default );
	}
}
