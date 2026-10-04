<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class BootTest extends WP_UnitTestCase {

	public function test_the_hyperpress_theme_is_active(): void {
		$this->assertSame( 'hyperpress', get_template() );
		$this->assertSame( 'hyperpress', get_stylesheet() );
		$this->assertNotFalse( has_filter( 'hyperpress_banner_content' ), 'theme banner filters are registered' );
	}

	public function test_plugins_are_loaded(): void {
		$this->assertTrue( class_exists( 'HyperPress\Utils\PostTypeRegistrar' ) );
		$this->assertTrue( post_type_exists( 'hyper_sponsor' ) );
	}
}
