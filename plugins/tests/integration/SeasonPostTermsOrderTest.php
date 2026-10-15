<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Season\Plugin;
use HyperPress\Season\SeasonOrder;
use WP_UnitTestCase;

final class SeasonPostTermsOrderTest extends WP_UnitTestCase {

	public function test_a_posts_seasons_are_listed_newest_first(): void {
		register_taxonomy_for_object_type( Plugin::TAXONOMY, 'post' );
		// Seasons may already be seeded; passing names reuses them and creates any that are missing.
		$names = array( 'Season 1', 'Season 2', 'Season 10', 'Season 20' );
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		wp_set_object_terms( $post_id, $names, Plugin::TAXONOMY );
		// The post list table primes the term cache with orderby none, which queries every taxonomy of the post type at once, so terms_clauses does not reorder it.
		clean_object_term_cache( $post_id, 'post' );
		update_object_term_cache( $post_id, 'post' );

		$listed = wp_list_pluck( get_the_terms( $post_id, Plugin::TAXONOMY ), 'name' );

		$this->assertSame( array( 'Season 20', 'Season 10', 'Season 2', 'Season 1' ), $listed );
	}

	public function test_other_taxonomies_are_left_alone(): void {
		$terms = array(
			new \WP_Term( (object) array( 'term_id' => 1, 'name' => 'Alpha' ) ),
			new \WP_Term( (object) array( 'term_id' => 2, 'name' => 'Beta' ) ),
		);

		$this->assertSame( $terms, SeasonOrder::sort_terms( $terms, 1, 'category' ) );
	}

	public function test_non_array_results_pass_through(): void {
		$error = new \WP_Error( 'x' );

		$this->assertSame( $error, SeasonOrder::sort_terms( $error, 1, Plugin::TAXONOMY ) );
		$this->assertFalse( SeasonOrder::sort_terms( false, 1, Plugin::TAXONOMY ) );
		$this->assertSame( array(), SeasonOrder::sort_terms( array(), 1, Plugin::TAXONOMY ) );
	}

	private function sorted_names( array $names ): array {
		$terms = array();
		foreach ( $names as $i => $name ) {
			$terms[] = new \WP_Term( (object) array( 'term_id' => $i + 1, 'name' => $name ) );
		}

		return wp_list_pluck( SeasonOrder::sort_terms( $terms, 1, Plugin::TAXONOMY ), 'name' );
	}

	public function test_names_without_a_number_sort_after_the_numbered_ones(): void {
		$this->assertSame(
			array( 'Season 20', 'Season 10', 'Season 2', 'Season 1', 'Archive', 'Special' ),
			$this->sorted_names( array( 'Season 2', 'Season 10', 'Season 1', 'Special', 'Archive', 'Season 20' ) )
		);
	}

	public function test_equal_numbers_tie_break_by_name_then_come_before_unnumbered(): void {
		$this->assertSame(
			array( 'Alpha 5', 'Fall 5', 'Zeta' ),
			$this->sorted_names( array( 'Zeta', 'Fall 5', 'Alpha 5' ) )
		);
	}
}
