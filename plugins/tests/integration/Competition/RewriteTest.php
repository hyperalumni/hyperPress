<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Plugin;
use HyperPress\Competition\Rewrite;
use HyperPress\Utils\RequestTerm;
use WP_UnitTestCase;

final class RewriteTest extends WP_UnitTestCase {

	private const HASH_OPTION = 'hyperpress_competition_rewrite_hash';

	public function set_up(): void {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
		// Same wiring as Plugin::boot(), once.
		remove_action( 'init', array( Rewrite::class, 'add_rules' ), 21 );
		Rewrite::register();
		Rewrite::add_rules();
		flush_rewrite_rules();
	}

	public function tear_down(): void {
		remove_action( 'init', array( Rewrite::class, 'add_rules' ), 21 );
		remove_action( 'init', array( Rewrite::class, 'maybe_flush' ), 99 );
		$GLOBALS['wp_query']->queried_object    = null;
		$GLOBALS['wp_query']->queried_object_id = 0;
		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		parent::tear_down();
	}

	/**
	 * Find or create a term. The plugins seed their terms outside the test transaction.
	 */
	private function term( string $name, string $slug, string $taxonomy ): \WP_Term {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term ) {
			$term = self::factory()->term->create_and_get( array( 'taxonomy' => $taxonomy, 'name' => $name, 'slug' => $slug ) );
		}
		return $term;
	}

	public function test_season_and_type_route_to_the_filtered_archive(): void {
		$this->term( 'District', 'district', Plugin::TYPE );
		$this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( home_url( '/seasons/season-31/competitions/district/' ) );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertFalse( is_404() );
		$this->assertSame( 'season-31', get_query_var( 'hyper_season' ) );
		$this->assertSame( 'district', get_query_var( Plugin::TYPE ) );
	}

	public function test_hierarchical_type_path_routes_to_the_child_term(): void {
		$parent = self::factory()->term->create_and_get( array( 'taxonomy' => Plugin::TYPE, 'name' => 'rt-parent', 'slug' => 'rt-parent' ) );
		$child  = self::factory()->term->create_and_get( array( 'taxonomy' => Plugin::TYPE, 'name' => 'rt-child', 'slug' => 'rt-child', 'parent' => $parent->term_id ) );
		$this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( home_url( '/seasons/season-31/competitions/rt-parent/rt-child/' ) );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertFalse( is_404() );
		// Core reduces a hierarchical term path to its last segment.
		$this->assertSame( 'rt-child', get_query_var( Plugin::TYPE ) );
		$term = RequestTerm::get( Plugin::TYPE );
		$this->assertNotNull( $term );
		$this->assertSame( $child->term_id, $term->term_id );
	}

	public function test_season_competitions_without_type_still_routes(): void {
		$this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( home_url( '/seasons/season-31/competitions/' ) );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertFalse( is_404() );
		$this->assertSame( 'season-31', get_query_var( 'hyper_season' ) );
		$this->assertSame( '', (string) get_query_var( Plugin::TYPE ) );
	}

	public function test_season_archive_still_routes(): void {
		$season = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( home_url( '/seasons/season-31/' ) );

		$this->assertTrue( is_tax( 'hyper_season' ) );
		$this->assertFalse( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertSame( $season->term_id, get_queried_object_id() );
	}

	public function test_unknown_type_slug_is_a_404(): void {
		$this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( home_url( '/seasons/season-31/competitions/rt-no-such-type/' ) );

		// Utils\NotFound: an unknown term in a taxonomy query var is a 404 (as is ?hyper_competition_type=rt-no-such-type).
		$this->assertTrue( is_404() );
		$this->assertSame( 'rt-no-such-type', get_query_var( Plugin::TYPE ) );
	}

	public function test_unknown_season_is_a_404(): void {
		$this->go_to( home_url( '/seasons/rt-no-such-season/competitions/' ) );

		$this->assertTrue( is_404() );
	}

	public function test_breadcrumbs_end_to_end(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( home_url( '/seasons/' . $season->slug . '/competitions/' . $district->slug . '/' ) );

		$label = get_post_type_object( Plugin::POST_TYPE )->labels->name;
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertSame(
			array( $label, $season->name, $district->name ),
			array_slice( array_column( $trail, 'title' ), -3 )
		);
	}

	public function test_season_and_district_route_to_the_filtered_archive(): void {
		$this->term( 'Season 31', 'season-31', 'hyper_season' );
		$this->term( 'RT NE', 'rt-ne', 'hyper_district' );

		$this->go_to( home_url( '/seasons/season-31/competitions/districts/rt-ne/' ) );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertFalse( is_404() );
		$this->assertSame( 'season-31', get_query_var( 'hyper_season' ) );
		$this->assertSame( 'rt-ne', get_query_var( 'hyper_district' ) );
		$this->assertSame( '', (string) get_query_var( Plugin::TYPE ) );
	}

	public function test_season_and_week_route_to_the_filtered_archive(): void {
		$this->term( 'Season 31', 'season-31', 'hyper_season' );
		$this->term( 'RT Week 1', 'rt-week-1', 'hyper_week' );

		$this->go_to( home_url( '/seasons/season-31/competitions/weeks/rt-week-1/' ) );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertFalse( is_404() );
		$this->assertSame( 'season-31', get_query_var( 'hyper_season' ) );
		$this->assertSame( 'rt-week-1', get_query_var( 'hyper_week' ) );
		$this->assertSame( '', (string) get_query_var( Plugin::TYPE ) );
	}

	public function test_hierarchical_district_path_routes_to_the_child_term(): void {
		$parent = self::factory()->term->create_and_get( array( 'taxonomy' => 'hyper_district', 'name' => 'rt-dparent', 'slug' => 'rt-dparent' ) );
		$child  = self::factory()->term->create_and_get( array( 'taxonomy' => 'hyper_district', 'name' => 'rt-dchild', 'slug' => 'rt-dchild', 'parent' => $parent->term_id ) );
		$this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( home_url( '/seasons/season-31/competitions/districts/rt-dparent/rt-dchild/' ) );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertFalse( is_404() );
		$this->assertSame( 'rt-dchild', get_query_var( 'hyper_district' ) );
		$term = RequestTerm::get( 'hyper_district' );
		$this->assertNotNull( $term );
		$this->assertSame( $child->term_id, $term->term_id );
	}

	public function test_type_rule_still_routes_after_the_prefixed_rules(): void {
		$parent = self::factory()->term->create_and_get( array( 'taxonomy' => Plugin::TYPE, 'name' => 'qualifying', 'slug' => 'rt-qualifying' ) );
		self::factory()->term->create_and_get( array( 'taxonomy' => Plugin::TYPE, 'name' => 'district', 'slug' => 'rt-district-type', 'parent' => $parent->term_id ) );
		$this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( home_url( '/seasons/season-31/competitions/rt-qualifying/' ) );
		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertFalse( is_404() );
		$this->assertSame( 'rt-qualifying', get_query_var( Plugin::TYPE ) );
		$this->assertSame( '', (string) get_query_var( 'hyper_district' ) );

		$this->go_to( home_url( '/seasons/season-31/competitions/rt-qualifying/rt-district-type/' ) );
		$this->assertFalse( is_404() );
		$this->assertSame( 'rt-district-type', get_query_var( Plugin::TYPE ) );
		$this->assertSame( '', (string) get_query_var( 'hyper_district' ) );
	}

	public function test_unknown_district_is_a_404(): void {
		$this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( home_url( '/seasons/season-31/competitions/districts/rt-no-such-district/' ) );

		$this->assertTrue( is_404() );
	}

	public function test_district_breadcrumbs_and_banner_end_to_end(): void {
		$district = $this->term( 'RT NE', 'rt-ne', 'hyper_district' );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );
		update_option( 'hyperpress_competition', array( 'hyperpress_competition_banner_subtitle' => 'Compete' ) );
		remove_all_filters( 'hyperpress_banner_content' );
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) {
				if ( empty( $banner['type'] ) && is_archive() ) {
					$banner['type']  = 'archive';
					$banner['title'] = 'Archive';
				}
				return $banner;
			},
			90
		);
		Plugin::register_banner();

		$this->go_to( home_url( '/seasons/' . $season->slug . '/competitions/districts/' . $district->slug . '/' ) );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$label = get_post_type_object( Plugin::POST_TYPE )->labels->name;
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame(
			array( $label, $season->name, $district->name ),
			array_slice( array_column( $trail, 'title' ), -3 )
		);

		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertSame( 'Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Compete in ' . $district->name . ', ' . $season->name, $banner['subtitle'] );
	}

	public function test_maybe_flush_reflushes_when_a_prefix_slug_changes(): void {
		Rewrite::maybe_flush();

		$flushes = 0;
		$counter = static function ( $value ) use ( &$flushes ) {
			++$flushes;
			return $value;
		};
		add_filter( 'pre_update_option_rewrite_rules', $counter );

		foreach ( array( 'hyper_district' => 'rt-areas', 'hyper_week' => 'rt-rounds' ) as $taxonomy => $slug ) {
			$previous                 = get_option( self::HASH_OPTION );
			$taxonomy_object          = get_taxonomy( $taxonomy );
			$original                 = $taxonomy_object->rewrite;
			$taxonomy_object->rewrite = array_merge( $original, array( 'slug' => $slug ) );

			Rewrite::maybe_flush();
			$this->assertNotSame( $previous, get_option( self::HASH_OPTION ), "Changing the $taxonomy slug must change the hash." );

			$taxonomy_object->rewrite = $original;
		}

		$this->assertSame( 2, $flushes );
		remove_filter( 'pre_update_option_rewrite_rules', $counter );
	}

	public function test_maybe_flush_flushes_once_per_change(): void {
		$flushes = 0;
		$counter = static function ( $value ) use ( &$flushes ) {
			++$flushes;
			return $value;
		};
		add_filter( 'pre_update_option_rewrite_rules', $counter );

		delete_option( self::HASH_OPTION );
		Rewrite::maybe_flush();
		$this->assertNotFalse( get_option( self::HASH_OPTION ) );
		$this->assertSame( 1, $flushes );

		Rewrite::maybe_flush();
		$this->assertSame( 1, $flushes, 'A matching hash must not flush again.' );

		update_option( self::HASH_OPTION, 'stale' );
		Rewrite::maybe_flush();
		$this->assertSame( 2, $flushes );
		$this->assertNotSame( 'stale', get_option( self::HASH_OPTION ) );

		remove_filter( 'pre_update_option_rewrite_rules', $counter );
	}
}
