<?php
/**
 * Tests for the taxonomy post types helper.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Utils\TaxonomyPostTypes;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Utils\TaxonomyPostTypes
 */
final class TaxonomyPostTypesTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unregister_taxonomy( 'hp_test_tax' );
		unregister_post_type( 'hp_test_a' );
		unregister_post_type( 'hp_test_b' );
		parent::tear_down();
	}

	public function test_unregistered_taxonomy_gives_an_empty_list(): void {
		$this->assertSame( array(), TaxonomyPostTypes::for_taxonomy( 'hp_test_missing' ) );
	}

	public function test_lists_every_attached_post_type(): void {
		register_post_type( 'hp_test_a' );
		register_post_type( 'hp_test_b' );
		register_taxonomy( 'hp_test_tax', array( 'hp_test_a', 'hp_test_b' ) );

		$this->assertSame( array( 'hp_test_a', 'hp_test_b' ), TaxonomyPostTypes::for_taxonomy( 'hp_test_tax' ) );
	}

	public function test_unattached_taxonomy_gives_an_empty_list(): void {
		register_taxonomy( 'hp_test_tax', array() );

		$this->assertSame( array(), TaxonomyPostTypes::for_taxonomy( 'hp_test_tax' ) );
	}

	public function test_list_is_reindexed_after_detaching(): void {
		register_post_type( 'hp_test_a' );
		register_post_type( 'hp_test_b' );
		register_taxonomy( 'hp_test_tax', array( 'hp_test_a', 'hp_test_b' ) );
		unregister_taxonomy_for_object_type( 'hp_test_tax', 'hp_test_a' );

		$this->assertSame( array( 'hp_test_b' ), TaxonomyPostTypes::for_taxonomy( 'hp_test_tax' ) );
	}
}
