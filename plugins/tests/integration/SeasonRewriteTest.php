<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SeasonRewriteTest extends WP_UnitTestCase {

	public function test_each_hyper_archive_gets_a_season_rule_with_the_real_post_type_key(): void {
		flush_rewrite_rules();
		$rules = (array) get_option( 'rewrite_rules' );

		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $key => $type ) {
			if ( 0 !== strpos( $key, 'hyper_' ) || ! is_string( $type->has_archive ) ) {
				continue;
			}
			$expected = 'index.php?hyper_season=$matches[1]&post_type=' . $key;
			$this->assertContains( $expected, $rules, "No season rule for $key" );
		}
	}

	public function test_season_archive_url_resolves_to_the_post_type_archive(): void {
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026', 'slug' => '2026' ) );
		flush_rewrite_rules();
		$this->go_to( home_url( '/seasons/2026/awards/' ) );
		$this->assertTrue( is_post_type_archive( 'hyper_award' ) );
		$this->assertTrue( is_tax( 'hyper_season' ) );
		$this->assertSame( $term_id, get_queried_object_id() );
	}
}
