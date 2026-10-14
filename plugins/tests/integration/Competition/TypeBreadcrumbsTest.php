<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Plugin;
use HyperPress\Competition\TypeBreadcrumbs;
use HyperPress\Utils\Breadcrumbs;
use WP_UnitTestCase;

final class TypeBreadcrumbsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		if ( ! taxonomy_exists( 'hyper_season' ) ) {
			register_taxonomy( 'hyper_season', array( Plugin::POST_TYPE ), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
		}
		flush_rewrite_rules();
		// Same wiring as Plugin::boot(), without duplicates from earlier registrations.
		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		Breadcrumbs::register( array( Plugin::POST_TYPE ), array( Plugin::TYPE ) );
		TypeBreadcrumbs::register();
	}

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		parent::tear_down();
	}

	/**
	 * Find or create a term. The competition plugin seeds its terms outside the test transaction.
	 */
	private function term( string $name, string $slug, string $taxonomy ): \WP_Term {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term ) {
			$term = self::factory()->term->create_and_get( array( 'taxonomy' => $taxonomy, 'name' => $name, 'slug' => $slug ) );
		}
		return $term;
	}

	public function test_type_archive_trail(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );

		$this->go_to( get_term_link( $district ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertSame( array( 'Competitions', 'District' ), array_column( $trail, 'title' ) );
		$this->assertSame( get_post_type_archive_link( Plugin::POST_TYPE ), $trail[0]['url'] );
		$this->assertArrayNotHasKey( 'url', $trail[1] );
		$this->assertSame( array( 'item-archive-competitions' ), $trail[0]['classes']['li'] );
		$this->assertSame( array( 'bread-archive-competitions' ), $trail[0]['classes']['bread'] );
	}

	public function test_other_requests_unchanged(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'About' ) );

		$this->go_to( get_permalink( $page_id ) );
		$input = array( array( 'title' => 'Home' ) );

		$this->assertSame( $input, apply_filters( 'hyperpress_breadcrumbs_content', $input ) );
	}

	public function test_unknown_type_slug_adds_no_crumb(): void {
		$this->go_to( '/?' . Plugin::TYPE . '=does-not-exist' );
		$input = array( array( 'title' => 'Home' ) );

		$this->assertSame( $input, TypeBreadcrumbs::filter( $input ) );
	}

	public function test_season_page_with_type_queried_is_left_to_the_season_trail(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( '/?hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $district->slug );
		$GLOBALS['wp_query']->queried_object    = $district;
		$GLOBALS['wp_query']->queried_object_id = $district->term_id;

		$input = array( array( 'title' => 'Home' ) );
		$this->assertSame( $input, TypeBreadcrumbs::filter( $input ) );
	}

	public function test_competition_archive_includes_type(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $district->slug );
		// Make the ambiguity deterministic: core may hand back either term as the queried object.
		$GLOBALS['wp_query']->queried_object    = $season;
		$GLOBALS['wp_query']->queried_object_id = $season->term_id;

		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertSame( array( 'Competitions', 'District', 'Season 31' ), array_column( $trail, 'title' ) );
	}
}
