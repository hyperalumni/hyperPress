<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Plugin;
use HyperPress\Competition\Rewrite;
use HyperPress\Utils\RequestTerm;
use WP_UnitTestCase;

/**
 * Pagination of the season-scoped filtered competition archives: /page/N/ after the term path.
 */
final class RewritePagedTest extends WP_UnitTestCase {

	private const HASH_OPTION = 'hyperpress_competition_rewrite_hash';

	public function set_up(): void {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
		// Same wiring as Plugin::boot(), once.
		remove_action( 'init', array( Rewrite::class, 'add_rules' ), 21 );
		Rewrite::register();
		Rewrite::add_rules();
		flush_rewrite_rules();
		add_filter( 'pre_option_posts_per_page', array( self::class, 'page_size' ) );
	}

	public function tear_down(): void {
		remove_filter( 'pre_option_posts_per_page', array( self::class, 'page_size' ) );
		remove_action( 'init', array( Rewrite::class, 'add_rules' ), 21 );
		remove_action( 'init', array( Rewrite::class, 'maybe_flush' ), 99 );
		$GLOBALS['wp_query']->queried_object    = null;
		$GLOBALS['wp_query']->queried_object_id = 0;
		$this->set_permalink_structure( '/%postname%/' );
		parent::tear_down();
	}

	public static function page_size(): int {
		return 2;
	}

	/**
	 * Find or create a term. The plugins seed their terms outside the test transaction.
	 */
	private function term( string $slug, string $taxonomy, int $parent = 0 ): \WP_Term {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term ) {
			$term = self::factory()->term->create_and_get(
				array(
					'taxonomy' => $taxonomy,
					'name'     => $slug,
					'slug'     => $slug,
					'parent'   => $parent,
				)
			);
		}
		return $term;
	}

	/**
	 * Three published competitions (two per page: pages 1 and 2) in the season and the extra term.
	 *
	 * @param \WP_Term $term Type, district or week term the competitions are assigned to.
	 */
	private function competitions( \WP_Term $term ): void {
		$season = $this->term( 'season-31', 'hyper_season' );
		for ( $i = 0; $i < 3; $i++ ) {
			$id = self::factory()->post->create(
				array(
					'post_type'   => Plugin::POST_TYPE,
					'post_status' => 'publish',
				)
			);
			wp_set_object_terms( $id, array( $season->term_id ), 'hyper_season' );
			wp_set_object_terms( $id, array( $term->term_id ), $term->taxonomy );
		}
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: string}> Label => URL prefix, taxonomy, term slug.
	 */
	public static function filters(): array {
		return array(
			'district' => array( 'districts', 'hyper_district', 'pg-ne' ),
			'week'     => array( 'weeks', 'hyper_week', 'pg-week-1' ),
			'type'     => array( '', Plugin::TYPE, 'pg-type' ),
		);
	}

	/**
	 * @dataProvider filters
	 */
	public function test_page_two_routes_with_the_term_and_paged( string $prefix, string $taxonomy, string $slug ): void {
		$term = $this->term( $slug, $taxonomy );
		$this->competitions( $term );
		$path = '/seasons/season-31/competitions/' . ( '' === $prefix ? '' : $prefix . '/' ) . $slug . '/';

		$this->go_to( home_url( $path ) );
		$this->assertFalse( is_404() );
		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertSame( 0, (int) get_query_var( 'paged' ) );
		$this->assertCount( 2, $GLOBALS['wp_query']->posts );

		$this->go_to( home_url( $path . 'page/2/' ) );
		$this->assertFalse( is_404(), 'The second page of a filtered archive is not a 404.' );
		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertTrue( is_paged() );
		$this->assertSame( 2, (int) get_query_var( 'paged' ) );
		$this->assertSame( 'season-31', get_query_var( 'hyper_season' ) );
		$this->assertSame( $slug, get_query_var( $taxonomy ) );
		$this->assertCount( 1, $GLOBALS['wp_query']->posts );
		$queried = RequestTerm::get( $taxonomy );
		$this->assertNotNull( $queried );
		$this->assertSame( $term->term_id, $queried->term_id );

		$this->go_to( home_url( $path . 'page/3/' ) );
		$this->assertTrue( is_404(), 'A page past the end is a legitimate 404.' );
	}

	public function test_hierarchical_type_path_pages(): void {
		$parent = $this->term( 'pg-parent', Plugin::TYPE );
		$child  = $this->term( 'pg-child', Plugin::TYPE, $parent->term_id );
		$this->competitions( $child );

		$this->go_to( home_url( '/seasons/season-31/competitions/pg-parent/pg-child/page/2/' ) );

		$this->assertFalse( is_404() );
		$this->assertSame( 2, (int) get_query_var( 'paged' ) );
		$this->assertSame( 'pg-child', get_query_var( Plugin::TYPE ) );
		$queried = RequestTerm::get( Plugin::TYPE );
		$this->assertNotNull( $queried );
		$this->assertSame( $child->term_id, $queried->term_id );
	}

	public function test_hierarchical_district_path_pages(): void {
		$parent = $this->term( 'pg-dparent', 'hyper_district' );
		$child  = $this->term( 'pg-dchild', 'hyper_district', $parent->term_id );
		$this->competitions( $child );

		$this->go_to( home_url( '/seasons/season-31/competitions/districts/pg-dparent/pg-dchild/page/2/' ) );

		$this->assertFalse( is_404() );
		$this->assertSame( 2, (int) get_query_var( 'paged' ) );
		$this->assertSame( 'pg-dchild', get_query_var( 'hyper_district' ) );
	}

	public function test_a_term_slug_containing_page_in_a_non_final_position_still_routes(): void {
		$parent = $this->term( 'page-one', Plugin::TYPE );
		$child  = $this->term( 'pg-after-page', Plugin::TYPE, $parent->term_id );
		$this->competitions( $child );

		$this->go_to( home_url( '/seasons/season-31/competitions/page-one/pg-after-page/' ) );
		$this->assertFalse( is_404() );
		$this->assertSame( 'pg-after-page', get_query_var( Plugin::TYPE ) );
		$this->assertSame( 0, (int) get_query_var( 'paged' ) );

		$this->go_to( home_url( '/seasons/season-31/competitions/page-one/pg-after-page/page/2/' ) );
		$this->assertFalse( is_404() );
		$this->assertSame( 'pg-after-page', get_query_var( Plugin::TYPE ) );
		$this->assertSame( 2, (int) get_query_var( 'paged' ) );

		$this->competitions( $parent );
		$this->go_to( home_url( '/seasons/season-31/competitions/page-one/' ) );
		$this->assertFalse( is_404() );
		$this->assertSame( 'page-one', get_query_var( Plugin::TYPE ) );
	}

	public function test_pagination_links_keep_the_term_path_and_page(): void {
		$term = $this->term( 'pg-links', 'hyper_district' );
		$this->competitions( $term );
		$this->go_to( home_url( '/seasons/season-31/competitions/districts/pg-links/page/2/' ) );

		$base = home_url( '/seasons/season-31/competitions/districts/pg-links/' );
		$this->assertSame( $base . 'page/3/', get_pagenum_link( 3 ) );
		$this->assertSame( $base, get_pagenum_link( 1 ) );
		$this->assertSame( $base . 'page/2/', get_pagenum_link( 2 ) );
		$this->assertSame( $base, get_previous_posts_page_link() );
	}

	/**
	 * The rule shape is part of the flush hash: a site flushed by the previous version re-flushes once.
	 */
	public function test_the_rules_version_is_part_of_the_flush_hash(): void {
		$slugs = new \ReflectionMethod( Rewrite::class, 'slugs' );
		$slugs->setAccessible( true );
		$legacy = md5( (string) wp_json_encode( $slugs->invoke( null ) ) );

		$flushes = 0;
		$counter = static function ( $value ) use ( &$flushes ) {
			++$flushes;
			return $value;
		};
		add_filter( 'pre_update_option_rewrite_rules', $counter );

		// A site last flushed by the version that hashed only the slugs.
		update_option( self::HASH_OPTION, $legacy );
		Rewrite::maybe_flush();

		$this->assertSame( 1, $flushes, 'The hash of the pre-pagination version must flush once.' );
		$this->assertNotSame( $legacy, get_option( self::HASH_OPTION ) );

		Rewrite::maybe_flush();
		$this->assertSame( 1, $flushes );

		remove_filter( 'pre_update_option_rewrite_rules', $counter );
	}
}
