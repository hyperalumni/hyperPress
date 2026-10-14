<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Plugin;
use HyperPress\Competition\TaxonomyBreadcrumbs;
use HyperPress\Utils\Breadcrumbs;
use WP_UnitTestCase;

final class TaxonomyBreadcrumbsTest extends WP_UnitTestCase {

	/**
	 * Whether set_up() registered hyper_season itself (and so must remove it).
	 *
	 * @var bool
	 */
	private bool $registered_season = false;

	/**
	 * Original singular labels, when a test changed them.
	 *
	 * @var array<string,string>|null
	 */
	private ?array $saved_labels = null;

	public function set_up(): void {
		parent::set_up();
		$this->registered_season = ! taxonomy_exists( 'hyper_season' );
		if ( $this->registered_season ) {
			register_taxonomy( 'hyper_season', array( Plugin::POST_TYPE ), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
		}
		// Same wiring as Plugin::boot(), without duplicates from earlier registrations.
		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		Breadcrumbs::register( array( Plugin::POST_TYPE ), array( Plugin::TYPE ) );
		TaxonomyBreadcrumbs::register();
	}

	public function tear_down(): void {
		if ( null !== $this->saved_labels ) {
			foreach ( $this->saved_labels as $taxonomy => $singular ) {
				get_taxonomy( $taxonomy )->labels->singular_name = $singular;
			}
			$this->saved_labels = null;
		}
		$GLOBALS['wp_query']->queried_object    = null;
		$GLOBALS['wp_query']->queried_object_id = 0;
		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		if ( $this->registered_season ) {
			unregister_taxonomy( 'hyper_season' );
		}
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

	/**
	 * The competition post type's plural label, as the breadcrumb shows it.
	 */
	private function label(): string {
		return get_post_type_object( Plugin::POST_TYPE )->labels->name;
	}

	public function test_type_taxonomy_is_called_type(): void {
		$labels = get_taxonomy( Plugin::TYPE )->labels;

		$this->assertSame( 'Type', $labels->singular_name );
		$this->assertSame( 'Competition Types', $labels->name );
	}

	public function test_type_taxonomy_keeps_its_competition_type_admin_labels(): void {
		$labels = get_taxonomy( Plugin::TYPE )->labels;

		$this->assertSame( 'Add New Competition Type', $labels->add_new_item );
		$this->assertSame( 'Edit Competition Type', $labels->edit_item );
		$this->assertSame( 'View Competition Type', $labels->view_item );
		$this->assertSame( 'Update Competition Type', $labels->update_item );
		$this->assertSame( 'New Competition Type Name', $labels->new_item_name );
	}

	public function test_type_archive_trail(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );

		$this->go_to( get_term_link( $district ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$class = sanitize_html_class( sanitize_title( $this->label() ) );

		$this->assertSame( array( $this->label(), $this->singular( Plugin::TYPE ), 'District' ), array_column( $trail, 'title' ) );
		$this->assertSame( 'Type', $trail[1]['title'] );
		$this->assertSame( get_post_type_archive_link( Plugin::POST_TYPE ), $trail[0]['url'] );
		$this->assertArrayNotHasKey( 'url', $trail[1] );
		$this->assertArrayNotHasKey( 'url', $trail[2] );
		$this->assertSame( array( 'item-archive-' . $class ), $trail[0]['classes']['li'] );
		$this->assertSame( array( 'bread-archive-' . $class ), $trail[0]['classes']['bread'] );
		$this->assertSame( array( 'li' => array(), 'bread' => array() ), $trail[1]['classes'] );
		$this->assertSame( array( 'li' => array(), 'bread' => array() ), $trail[2]['classes'] );
	}

	public function test_single_competition_trail_has_the_type_term_and_no_label_crumb(): void {
		$type    = $this->rt_term( 'RT Single Type', 'rt-single-type', Plugin::TYPE );
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_title' => 'RT Single Competition' ) );
		wp_set_object_terms( $post_id, array( $type->term_id ), Plugin::TYPE );

		$this->go_to( get_permalink( $post_id ) );
		$titles = array_column( apply_filters( 'hyperpress_breadcrumbs_content', array() ), 'title' );

		$this->assertContains( 'RT Single Type', $titles );
		$this->assertNotContains( $this->singular( Plugin::TYPE ), $titles );
		$this->assertNotContains( 'Type', $titles );
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

		$this->assertSame( $input, TaxonomyBreadcrumbs::filter( $input ) );
	}

	public function test_season_page_with_type_queried_is_left_to_the_season_trail(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( '/?hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $district->slug );
		$GLOBALS['wp_query']->queried_object    = $district;
		$GLOBALS['wp_query']->queried_object_id = $district->term_id;

		$input = array( array( 'title' => 'Home' ) );
		$this->assertSame( $input, TaxonomyBreadcrumbs::filter( $input ) );
	}

	public function test_competition_archive_includes_type(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $district->slug );
		// Make the ambiguity deterministic: core may hand back either term as the queried object.
		$GLOBALS['wp_query']->queried_object    = $season;
		$GLOBALS['wp_query']->queried_object_id = $season->term_id;

		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertSame( array( $this->label(), 'District', 'Season 31' ), array_column( $trail, 'title' ) );
		$this->assertNotContains( 'Type', array_column( $trail, 'title' ) );
	}

	/**
	 * Make a unique, test-only term (optionally under a parent).
	 */
	private function rt_term( string $name, string $slug, string $taxonomy, int $parent = 0 ): \WP_Term {
		return self::factory()->term->create_and_get(
			array(
				'taxonomy' => $taxonomy,
				'name'     => $name,
				'slug'     => $slug,
				'parent'   => $parent,
			)
		);
	}

	private function query_object( \WP_Term $term ): void {
		$GLOBALS['wp_query']->queried_object    = $term;
		$GLOBALS['wp_query']->queried_object_id = $term->term_id;
	}

	private function singular( string $taxonomy ): string {
		return get_taxonomy( $taxonomy )->labels->singular_name;
	}

	public function test_week_archive_trail(): void {
		$week = $this->rt_term( 'RT Week 0', 'rt-week-0', Plugin::WEEK );

		$this->go_to( get_term_link( $week ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertSame( array( $this->label(), $this->singular( Plugin::WEEK ), 'RT Week 0' ), array_column( $trail, 'title' ) );
		$this->assertSame( get_post_type_archive_link( Plugin::POST_TYPE ), $trail[0]['url'] );
		$this->assertArrayNotHasKey( 'url', $trail[1] );
		$this->assertArrayNotHasKey( 'url', $trail[2] );
		$this->assertSame( array( 'li' => array(), 'bread' => array() ), $trail[1]['classes'] );
		$this->assertSame( array( 'li' => array(), 'bread' => array() ), $trail[2]['classes'] );
	}

	public function test_district_archive_trail(): void {
		$district = $this->rt_term( 'RT NE FIRST', 'rt-ne-first', Plugin::DISTRICT );

		$this->go_to( get_term_link( $district ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertSame( array( $this->label(), $this->singular( Plugin::DISTRICT ), 'RT NE FIRST' ), array_column( $trail, 'title' ) );
		$this->assertSame( get_post_type_archive_link( Plugin::POST_TYPE ), $trail[0]['url'] );
		$this->assertArrayNotHasKey( 'url', $trail[1] );
		$this->assertArrayNotHasKey( 'url', $trail[2] );
		$this->assertSame( array( 'li' => array(), 'bread' => array() ), $trail[1]['classes'] );
	}

	public function test_middle_crumb_follows_the_taxonomy_label(): void {
		$this->saved_labels = array(
			Plugin::WEEK     => $this->singular( Plugin::WEEK ),
			Plugin::DISTRICT => $this->singular( Plugin::DISTRICT ),
			Plugin::TYPE     => $this->singular( Plugin::TYPE ),
		);
		get_taxonomy( Plugin::WEEK )->labels->singular_name     = 'Rt Wk Label';
		get_taxonomy( Plugin::DISTRICT )->labels->singular_name = 'Rt Dist Label';
		get_taxonomy( Plugin::TYPE )->labels->singular_name     = 'Rt Type Label';
		$week     = $this->rt_term( 'RT Week 1', 'rt-week-1', Plugin::WEEK );
		$district = $this->rt_term( 'RT Dist 1', 'rt-dist-1', Plugin::DISTRICT );
		$type     = $this->rt_term( 'RT Type 1', 'rt-type-1', Plugin::TYPE );

		$this->go_to( '/?' . Plugin::TYPE . '=' . $type->slug );
		$this->assertSame( 'Rt Type Label', apply_filters( 'hyperpress_breadcrumbs_content', array() )[1]['title'] );

		$this->go_to( '/?' . Plugin::WEEK . '=' . $week->slug );
		$this->assertSame( 'Rt Wk Label', apply_filters( 'hyperpress_breadcrumbs_content', array() )[1]['title'] );

		$this->go_to( '/?' . Plugin::DISTRICT . '=' . $district->slug );
		$this->assertSame( 'Rt Dist Label', apply_filters( 'hyperpress_breadcrumbs_content', array() )[1]['title'] );
	}

	public function test_hierarchical_child_term_shows_only_its_own_name(): void {
		$parent_week = $this->rt_term( 'RT Parent Week', 'rt-parent-week', Plugin::WEEK );
		$child_week  = $this->rt_term( 'RT Child Week', 'rt-child-week', Plugin::WEEK, $parent_week->term_id );
		$parent_dist = $this->rt_term( 'RT Parent District', 'rt-parent-district', Plugin::DISTRICT );
		$child_dist  = $this->rt_term( 'RT Child District', 'rt-child-district', Plugin::DISTRICT, $parent_dist->term_id );

		$this->go_to( '/?' . Plugin::WEEK . '=' . $child_week->slug );
		$this->assertSame(
			array( $this->label(), $this->singular( Plugin::WEEK ), 'RT Child Week' ),
			array_column( apply_filters( 'hyperpress_breadcrumbs_content', array() ), 'title' )
		);

		$this->go_to( '/?' . Plugin::DISTRICT . '=' . $child_dist->slug );
		$this->assertSame(
			array( $this->label(), $this->singular( Plugin::DISTRICT ), 'RT Child District' ),
			array_column( apply_filters( 'hyperpress_breadcrumbs_content', array() ), 'title' )
		);
	}

	public function test_type_and_district_request_shows_only_the_type_trail(): void {
		$type     = $this->rt_term( 'RT Type', 'rt-type', Plugin::TYPE );
		$district = $this->rt_term( 'RT District', 'rt-district', Plugin::DISTRICT );

		$this->go_to( '/?' . Plugin::TYPE . '=' . $type->slug . '&' . Plugin::DISTRICT . '=' . $district->slug );
		$this->query_object( $district );

		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertSame( array( $this->label(), $this->singular( Plugin::TYPE ), 'RT Type' ), array_column( $trail, 'title' ) );
	}

	public function test_district_and_week_request_shows_only_the_district_trail(): void {
		$district = $this->rt_term( 'RT District 2', 'rt-district-2', Plugin::DISTRICT );
		$week     = $this->rt_term( 'RT Week 2', 'rt-week-2', Plugin::WEEK );

		$this->go_to( '/?' . Plugin::DISTRICT . '=' . $district->slug . '&' . Plugin::WEEK . '=' . $week->slug );
		$this->query_object( $week );

		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertSame( array( $this->label(), $this->singular( Plugin::DISTRICT ), 'RT District 2' ), array_column( $trail, 'title' ) );
	}

	public function test_season_page_with_district_queried_is_left_to_the_season_trail(): void {
		$district = $this->rt_term( 'RT District 3', 'rt-district-3', Plugin::DISTRICT );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( '/?hyper_season=' . $season->slug . '&' . Plugin::DISTRICT . '=' . $district->slug );
		$this->query_object( $district );

		$input = array( array( 'title' => 'Home' ) );
		$this->assertSame( $input, TaxonomyBreadcrumbs::filter( $input ) );
	}

	public function test_competition_archive_with_district_is_not_affected(): void {
		$district = $this->rt_term( 'RT District 4', 'rt-district-4', Plugin::DISTRICT );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&' . Plugin::DISTRICT . '=' . $district->slug );
		$this->query_object( $district );

		$input = array( array( 'title' => 'Home' ) );
		$this->assertSame( $input, TaxonomyBreadcrumbs::filter( $input ) );

		$titles = array_column( apply_filters( 'hyperpress_breadcrumbs_content', array() ), 'title' );
		$this->assertSame( $titles, array_values( array_unique( $titles ) ) );
		$this->assertNotContains( $this->singular( Plugin::DISTRICT ), $titles );
	}
}
