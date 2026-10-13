<?php
namespace HyperPress\Tests\Integration\Program;

use HyperPress\Program\Sanitizers;
use WP_UnitTestCase;

final class ProgramSanitizersTest extends WP_UnitTestCase {

	public function test_unknown_and_markup_post_types_are_dropped(): void {
		$this->assertSame( array( 'post' ), Sanitizers::post_types( array( 'post', 'nope', '<script>' ) ) );
	}

	public function test_non_public_post_types_are_dropped(): void {
		$this->assertSame( array( 'page' ), Sanitizers::post_types( array( 'nav_menu_item', 'page', 'attachment_x' ) ) );
	}

	public function test_non_array_input_becomes_an_array(): void {
		$this->assertSame( array(), Sanitizers::post_types( 'nope' ) );
		$this->assertSame( array(), Sanitizers::post_types( null ) );
		$this->assertSame( array( 'post' ), Sanitizers::post_types( array( 'post', array( 'x' ), 5 ) ) );
	}

	public function test_post_types_are_unique(): void {
		$this->assertSame( array( 'post', 'page' ), Sanitizers::post_types( array( 'post', 'page', 'post' ) ) );
	}
}
