<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SeasonRewritePagedTest extends WP_UnitTestCase {

	private const HASH_OPTION = 'hyperpress_season_rewrite_hash';

	public function tear_down(): void {
		delete_option( self::HASH_OPTION );
		parent::tear_down();
	}

	/**
	 * Creates a 2026 season with two published posts of a type and forces one
	 * post per page, so that /page/2/ has content.
	 */
	private function seed( string $post_type ): string {
		if ( ! post_type_exists( $post_type ) ) {
			$this->markTestSkipped( "$post_type is not registered." );
		}
		$archive = (string) get_post_type_object( $post_type )->has_archive;
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026', 'slug' => '2026' ) );
		for ( $i = 0; $i < 2; $i++ ) {
			$id = self::factory()->post->create( array( 'post_type' => $post_type, 'post_status' => 'publish' ) );
			wp_set_object_terms( $id, array( $term_id ), 'hyper_season' );
		}
		add_action(
			'pre_get_posts',
			static function ( $query ) {
				if ( $query->is_main_query() ) {
					$query->set( 'posts_per_page', 1 );
				}
			},
			999
		);
		flush_rewrite_rules();

		return $archive;
	}

	public function test_sponsor_archive_page_two_resolves(): void {
		$archive = $this->seed( 'hyper_sponsor' );
		$this->go_to( home_url( "/seasons/2026/$archive/page/2/" ) );

		$this->assertTrue( is_tax( 'hyper_season' ) );
		$this->assertTrue( is_post_type_archive( 'hyper_sponsor' ) );
		$this->assertSame( 2, (int) get_query_var( 'paged' ) );
		$this->assertFalse( is_404() );
	}

	public function test_sponsor_archive_unpaged_is_unchanged(): void {
		$archive = $this->seed( 'hyper_sponsor' );
		$this->go_to( home_url( "/seasons/2026/$archive/" ) );

		$this->assertTrue( is_tax( 'hyper_season' ) );
		$this->assertTrue( is_post_type_archive( 'hyper_sponsor' ) );
		$this->assertTrue( is_archive() );
		$this->assertFalse( is_paged() );
		$this->assertLessThanOrEqual( 1, (int) get_query_var( 'paged' ) );
		$this->assertFalse( is_404() );
	}

	public function test_another_archive_resolves_unpaged_and_paged(): void {
		$archive = $this->seed( 'hyper_competition' );

		$this->go_to( home_url( "/seasons/2026/$archive/" ) );
		$this->assertTrue( is_post_type_archive( 'hyper_competition' ) );
		$this->assertFalse( is_paged() );
		$this->assertFalse( is_404() );

		$this->go_to( home_url( "/seasons/2026/$archive/page/2/" ) );
		$this->assertTrue( is_post_type_archive( 'hyper_competition' ) );
		$this->assertTrue( is_tax( 'hyper_season' ) );
		$this->assertSame( 2, (int) get_query_var( 'paged' ) );
		$this->assertFalse( is_404() );
	}

	public function test_the_flush_hash_covers_the_rule_shape(): void {
		\HyperPress\Season\Rewrite::maybe_flush();
		$archives_only = md5( (string) wp_json_encode( self::archives_for_test() ) );

		$this->assertNotSame( $archives_only, get_option( self::HASH_OPTION ), 'An old archives-only hash must trigger a re-flush.' );
	}

	/** @return array<int, array{0: string, 1: string}> */
	private static function archives_for_test(): array {
		$archives = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $key => $type ) {
			if ( 0 !== strpos( $key, 'hyper_' ) || ! is_string( $type->has_archive ) || '' === $type->has_archive ) {
				continue;
			}
			$archives[] = array( $type->has_archive, $key );
		}
		sort( $archives );

		return $archives;
	}
}
