<?php
namespace HyperPress\Tests\Integration\Utils;

use WP_UnitTestCase;

/**
 * The dropdown Customizer controls were removed when their settings moved to
 * Meta Box settings pages.
 */
final class NoControlsTest extends WP_UnitTestCase {

	public function test_the_controls_files_and_directory_are_gone(): void {
		$src = dirname( __DIR__, 3 ) . '/hyperpress-utils/src';

		// Guard: the path derivation is right if a neighbouring file is found.
		$this->assertFileExists( $src . '/AdminMenu.php' );
		$this->assertFileDoesNotExist( $src . '/Controls/PostTypeDropdownControl.php' );
		$this->assertFileDoesNotExist( $src . '/Controls/CategoryDropdownControl.php' );
		$this->assertDirectoryDoesNotExist( $src . '/Controls' );
	}

	public function test_the_dropdown_customizer_controls_are_gone(): void {
		$this->assertFalse( class_exists( 'HyperPress\Utils\Controls\PostTypeDropdownControl' ) );
		$this->assertFalse( class_exists( 'HyperPress\Utils\Controls\CategoryDropdownControl' ) );
	}
}
