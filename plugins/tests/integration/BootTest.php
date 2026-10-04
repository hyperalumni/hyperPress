<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class BootTest extends WP_UnitTestCase {
	public function test_wordpress_boots_with_plugins_loaded(): void {
		$this->assertTrue( did_action( 'init' ) > 0 );
		$this->assertTrue( function_exists( 'rwmb_meta' ) );
	}
}
