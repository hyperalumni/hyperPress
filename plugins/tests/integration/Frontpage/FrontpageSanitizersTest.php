<?php
namespace HyperPress\Tests\Integration\Frontpage;

use HyperPress\Frontpage\Sanitizers;
use WP_UnitTestCase;

final class FrontpageSanitizersTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unregister_post_type( 'hp_private' );
		unregister_post_type( 'hp_public' );
		parent::tear_down();
	}

	public function test_post_types_drop_non_public_and_unknown_types(): void {
		register_post_type( 'hp_private', array( 'public' => false ) );
		register_post_type( 'hp_public', array( 'public' => true ) );

		$clean = Sanitizers::post_types( array( 'post', 'hp_public', 'hp_private', 'nope', 'x"y' ) );

		$this->assertSame( array( 'post', 'hp_public' ), $clean );
	}

	public function test_post_types_are_unique(): void {
		$this->assertSame( array( 'post' ), Sanitizers::post_types( array( 'post', 'post' ) ) );
	}

	public function test_post_types_from_a_non_array_value_become_empty(): void {
		$this->assertSame( array(), Sanitizers::post_types( 'post' ) );
	}

	public function test_categories_from_a_non_array_value_become_empty(): void {
		$id = self::factory()->category->create();
		$this->assertSame( array(), Sanitizers::categories( (string) $id ) );
		$this->assertSame( array(), Sanitizers::categories( null ) );
	}

	public function test_categories_that_do_not_exist_are_dropped(): void {
		$this->assertSame( array(), Sanitizers::categories( array( '999999' ) ) );
	}

	public function test_a_category_list_keeps_only_existing_ids_as_ints(): void {
		$id = self::factory()->category->create();
		$this->assertSame( array( $id ), Sanitizers::categories( array( (string) $id, '999999', 'abc' ) ) );
	}

	public function test_categories_ignore_nested_arrays(): void {
		$id = self::factory()->category->create();
		$this->assertSame( array(), Sanitizers::categories( array( array( $id ) ) ) );
	}

	public function test_categories_collapse_duplicate_ids(): void {
		$id = self::factory()->category->create();
		$this->assertSame( array( $id ), Sanitizers::categories( array( $id, (string) $id, $id ) ) );
	}

	public function test_categories_drop_zero_negative_and_non_numeric_values(): void {
		$this->assertSame( array(), Sanitizers::categories( array( '0', 0, '-3', -3, 'abc', '', 'x1' ) ) );
	}
}
