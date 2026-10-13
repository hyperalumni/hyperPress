<?php
namespace HyperPress\Tests\Integration\Utils;

use WP_UnitTestCase;

/**
 * The dropdown Customizer controls were removed when their settings moved to
 * Meta Box settings pages.
 */
final class NoControlsTest extends WP_UnitTestCase {

	public function test_the_dropdown_customizer_controls_are_gone(): void {
		$this->assertFalse( class_exists( 'HyperPress\Utils\Controls\PostTypeDropdownControl' ) );
		$this->assertFalse( class_exists( 'HyperPress\Utils\Controls\CategoryDropdownControl' ) );
	}
}
