<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Breadcrumbs;
use WP_UnitTestCase;

final class BreadcrumbsRequestTest extends WP_UnitTestCase {

	/**
	 * Whether set_up() registered hyper_competition_type itself (and so must remove it).
	 *
	 * @var bool
	 */
	private bool $registered_type = false;

	public function set_up(): void {
		parent::set_up();
		if ( ! taxonomy_exists( 'hyper_season' ) ) {
			register_taxonomy( 'hyper_season', array(), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
		}
		register_post_type( 'hyper_gizmo', array( 'public' => true, 'has_archive' => 'gizmos', 'labels' => array( 'name' => 'Gizmos' ) ) );
		register_taxonomy_for_object_type( 'hyper_season', 'hyper_gizmo' );
		// The competition plugin registers this taxonomy at init; only add (and later remove) a stand-in when it is absent.
		$this->registered_type = ! taxonomy_exists( 'hyper_competition_type' );
		if ( $this->registered_type ) {
			register_taxonomy(
				'hyper_competition_type',
				array( 'hyper_gizmo' ),
				array(
					'public'       => true,
					'hierarchical' => true,
					'rewrite'      => array( 'slug' => 'competition-types' ),
				)
			);
		} else {
			register_taxonomy_for_object_type( 'hyper_competition_type', 'hyper_gizmo' );
		}
		flush_rewrite_rules();
		Breadcrumbs::register( array( 'hyper_gizmo' ) );
	}

	public function tear_down(): void {
		if ( $this->registered_type ) {
			unregister_taxonomy( 'hyper_competition_type' );
		} else {
			unregister_taxonomy_for_object_type( 'hyper_competition_type', 'hyper_gizmo' );
		}
		$GLOBALS['wp_query']->queried_object    = null;
		$GLOBALS['wp_query']->queried_object_id = 0;
		unregister_post_type( 'hyper_gizmo' );
		parent::tear_down();
	}

	/**
	 * Replace the default registration with one that also shows the competition type.
	 */
	private function register_with_type(): void {
		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		Breadcrumbs::register( array( 'hyper_gizmo' ), array( 'hyper_competition_type' ) );
	}

	/**
	 * Create Qualifying > District and Season 31.
	 *
	 * @return array{0: \WP_Term, 1: \WP_Term, 2: \WP_Term} Qualifying, District, Season 31.
	 */
	private function make_terms(): array {
		// The competition plugin may already seed Qualifying and District outside the test transaction, so reuse them.
		$qualifying = get_term_by( 'slug', 'qualifying', 'hyper_competition_type' );
		if ( ! $qualifying ) {
			$qualifying = self::factory()->term->create_and_get( array( 'taxonomy' => 'hyper_competition_type', 'name' => 'Qualifying', 'slug' => 'qualifying' ) );
		}
		$district = get_term_by( 'slug', 'district', 'hyper_competition_type' );
		if ( ! $district ) {
			$district = self::factory()->term->create_and_get( array( 'taxonomy' => 'hyper_competition_type', 'name' => 'District', 'slug' => 'district', 'parent' => $qualifying->term_id ) );
		}
		$season = get_term_by( 'slug', 'season-31', 'hyper_season' );
		if ( ! $season ) {
			$season = self::factory()->term->create_and_get( array( 'taxonomy' => 'hyper_season', 'name' => 'Season 31', 'slug' => 'season-31' ) );
		}
		return array( $qualifying, $district, $season );
	}

	public function test_archive_trail_includes_type_between_post_type_and_season(): void {
		list( , $district ) = $this->make_terms();
		$this->register_with_type();

		$this->go_to( '/?post_type=hyper_gizmo&hyper_season=season-31&hyper_competition_type=district' );
		// Make the ambiguity deterministic: core hands back the type term as the queried object.
		$GLOBALS['wp_query']->queried_object    = $district;
		$GLOBALS['wp_query']->queried_object_id = $district->term_id;

		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', 'District', 'Season 31' ), array_column( $trail, 'title' ) );
		$this->assertSame( get_term_link( $district ), $trail[1]['url'] );
	}

	public function test_singular_trail_uses_assigned_type(): void {
		list( , $district, $season ) = $this->make_terms();
		$this->register_with_type();
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_gizmo', 'post_title' => 'Widget One' ) );
		wp_set_object_terms( $post_id, array( $district->term_id ), 'hyper_competition_type' );
		wp_set_object_terms( $post_id, array( $season->term_id ), 'hyper_season' );

		$this->go_to( get_permalink( $post_id ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', 'District', 'Season 31', 'Widget One' ), array_column( $trail, 'title' ) );
	}

	public function test_singular_without_type_has_no_type_crumb(): void {
		list( , , $season ) = $this->make_terms();
		$this->register_with_type();
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_gizmo', 'post_title' => 'Widget One' ) );
		wp_set_object_terms( $post_id, array( $season->term_id ), 'hyper_season' );

		$this->go_to( get_permalink( $post_id ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', 'Season 31', 'Widget One' ), array_column( $trail, 'title' ) );
	}

	public function test_unknown_type_slug_has_no_type_crumb(): void {
		$this->make_terms();
		$this->register_with_type();

		$this->go_to( '/?post_type=hyper_gizmo&hyper_season=season-31&hyper_competition_type=does-not-exist' );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		// An unknown type slug matches no posts, and whether core then flags the request as a 404 (is_post_type_archive()
		// is false, so the callback leaves the input alone) or keeps it as the Gizmos archive depends on global state
		// left by other tests. Either way the trail is exact and never contains a type crumb.
		if ( is_404() ) {
			$this->assertSame( array(), $trail );
		} else {
			$this->assertSame( array( 'Gizmos', 'Season 31' ), array_column( $trail, 'title' ) );
		}
	}

	public function test_child_term_yields_single_crumb(): void {
		$this->make_terms();
		$this->register_with_type();

		$this->go_to( '/?post_type=hyper_gizmo&hyper_competition_type=district' );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', 'District' ), array_column( $trail, 'title' ) );
	}

	public function test_register_without_taxonomies_is_unchanged(): void {
		$this->make_terms();

		$this->go_to( '/?post_type=hyper_gizmo&hyper_season=season-31&hyper_competition_type=district' );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', 'Season 31' ), array_column( $trail, 'title' ) );
	}

	public function test_other_requests_are_untouched(): void {
		$this->go_to( home_url( '/' ) );
		$this->assertSame( array( array( 'title' => 'Home' ) ), apply_filters( 'hyperpress_breadcrumbs_content', array( array( 'title' => 'Home' ) ) ) );
	}

	public function test_post_type_archive_trail(): void {
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos' ), array_column( $trail, 'title' ) );
	}

	public function test_singular_uses_the_newest_season_and_the_title(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_gizmo', 'post_title' => 'Widget One' ) );
		$old     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2025' ) );
		$new     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026' ) );
		wp_set_object_terms( $post_id, array( $old, $new ), 'hyper_season' );

		$this->go_to( get_permalink( $post_id ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', '2026', 'Widget One' ), array_column( $trail, 'title' ) );
		$this->assertStringContainsString( '/gizmos', $trail[1]['url'] );
	}

	public function test_season_crumb_is_season_when_type_also_queried(): void {
		list( , $district ) = $this->make_terms();

		$this->go_to( '/?post_type=hyper_gizmo&hyper_season=season-31&hyper_competition_type=district' );
		// Core may hand back the type term as the queried object; the old get_queried_object() code then showed 'District' as the season.
		$GLOBALS['wp_query']->queried_object    = $district;
		$GLOBALS['wp_query']->queried_object_id = $district->term_id;
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( 'Season 31', end( $trail )['title'] );
	}

	public function test_unregistered_extra_taxonomy_is_skipped(): void {
		$this->make_terms();
		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		Breadcrumbs::register( array( 'hyper_gizmo' ), array( 'hyper_not_registered' ) );

		$this->go_to( '/?post_type=hyper_gizmo&hyper_season=season-31&hyper_not_registered=whatever' );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', 'Season 31' ), array_column( $trail, 'title' ) );
	}

	public function test_term_link_error_gives_an_empty_type_url(): void {
		$this->make_terms();
		$this->register_with_type();
		add_filter( 'term_link', static fn() => new \WP_Error( 'no_link', 'no link' ) );

		$this->go_to( '/?post_type=hyper_gizmo&hyper_competition_type=district' );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', 'District' ), array_column( $trail, 'title' ) );
		$this->assertSame( '', $trail[1]['url'] );
	}

	public function test_debug_output_is_gone(): void {
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		ob_start();
		apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( '', ob_get_clean(), 'Breadcrumb building must not print anything' );
	}
}
