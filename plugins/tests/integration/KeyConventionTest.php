<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class KeyConventionTest extends WP_UnitTestCase {

	public function test_registered_post_types_are_exactly_the_expected_set(): void {
		$actual = array_keys( get_post_types( array( '_builtin' => false ) ) );
		$this->assertEqualsCanonicalizing( array_keys( Convention::POST_TYPES ), $actual );
	}

	public function test_registered_taxonomies_are_exactly_the_expected_set(): void {
		$actual = array_keys( get_taxonomies( array( '_builtin' => false ) ) );
		$this->assertEqualsCanonicalizing( array_keys( Convention::TAXONOMIES ), $actual );
	}

	public function test_keys_follow_prefix_and_length_rules(): void {
		$limits = array(
			Convention::KEY_MAX_LENGTH          => array_keys( Convention::POST_TYPES ),
			Convention::TAXONOMY_KEY_MAX_LENGTH => array_keys( Convention::TAXONOMIES ),
		);
		foreach ( $limits as $max => $keys ) {
			foreach ( $keys as $key ) {
				$this->assertMatchesRegularExpression( '/^hyper_[a-z0-9_]+$/', $key, "$key format" );
				$this->assertLessThanOrEqual( $max, strlen( $key ), "$key length" );
			}
		}
	}
}
