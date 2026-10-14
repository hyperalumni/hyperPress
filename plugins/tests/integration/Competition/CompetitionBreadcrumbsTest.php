<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Plugin;
use HyperPress\Competition\TaxonomyBreadcrumbs;
use HyperPress\Utils\Breadcrumbs;
use WP_UnitTestCase;

/**
 * The competition archive and single trails: post type, then the type, district and week terms
 * after the season (broad to narrow; in that order, only those present).
 */
final class CompetitionBreadcrumbsTest extends WP_UnitTestCase {

	/**
	 * Whether set_up() registered hyper_season itself (and so must remove it).
	 *
	 * @var bool
	 */
	private bool $registered_season = false;

	public function set_up(): void {
		parent::set_up();
		$this->registered_season = ! taxonomy_exists( 'hyper_season' );
		if ( $this->registered_season ) {
			register_taxonomy( 'hyper_season', array( Plugin::POST_TYPE ), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
		}
		// Same wiring as Plugin::boot() (shared taxonomy list), without duplicates from earlier registrations.
		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		Breadcrumbs::register( array( Plugin::POST_TYPE ), Plugin::BREADCRUMB_TAXONOMIES );
		TaxonomyBreadcrumbs::register();
	}

	public function tear_down(): void {
		$GLOBALS['wp_query']->queried_object    = null;
		$GLOBALS['wp_query']->queried_object_id = 0;
		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		if ( $this->registered_season ) {
			unregister_taxonomy( 'hyper_season' );
		}
		parent::tear_down();
	}

	private function rt_term( string $name, string $slug, string $taxonomy ): \WP_Term {
		return self::factory()->term->create_and_get( array( 'taxonomy' => $taxonomy, 'name' => $name, 'slug' => $slug ) );
	}

	private function label(): string {
		return get_post_type_object( Plugin::POST_TYPE )->labels->name;
	}

	private function titles(): array {
		return array_column( apply_filters( 'hyperpress_breadcrumbs_content', array() ), 'title' );
	}

	public function test_archive_with_district_and_week_shows_the_season_then_both_terms(): void {
		$season   = $this->rt_term( 'RT Season 31', 'rt-season-31', 'hyper_season' );
		$district = $this->rt_term( 'RT NE FIRST', 'rt-ne-first', Plugin::DISTRICT );
		$week     = $this->rt_term( 'RT Week 0', 'rt-week-0', Plugin::WEEK );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::DISTRICT . '=' . $district->slug . '&' . Plugin::WEEK . '=' . $week->slug );
		// Core may hand back any of the queried terms; make it deterministic.
		$GLOBALS['wp_query']->queried_object    = $season;
		$GLOBALS['wp_query']->queried_object_id = $season->term_id;

		$this->assertSame( array( $this->label(), 'RT Season 31', 'RT NE FIRST', 'RT Week 0' ), $this->titles() );
	}

	public function test_archive_orders_terms_by_taxonomy_list_not_by_the_request(): void {
		$season   = $this->rt_term( 'RT Season 32', 'rt-season-32', 'hyper_season' );
		$type     = $this->rt_term( 'RT Type', 'rt-type', Plugin::TYPE );
		$district = $this->rt_term( 'RT District', 'rt-district', Plugin::DISTRICT );
		$week     = $this->rt_term( 'RT Week 1', 'rt-week-1', Plugin::WEEK );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::WEEK . '=' . $week->slug . '&' . Plugin::DISTRICT . '=' . $district->slug . '&' . Plugin::TYPE . '=' . $type->slug );
		$GLOBALS['wp_query']->queried_object    = $season;
		$GLOBALS['wp_query']->queried_object_id = $season->term_id;

		$this->assertSame( array( $this->label(), 'RT Season 32', 'RT Type', 'RT District', 'RT Week 1' ), $this->titles() );
	}

	public function test_archive_with_only_a_district_shows_it_after_the_season(): void {
		$season   = $this->rt_term( 'RT Season 33', 'rt-season-33', 'hyper_season' );
		$district = $this->rt_term( 'RT PNW FIRST', 'rt-pnw-first', Plugin::DISTRICT );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::DISTRICT . '=' . $district->slug );
		$GLOBALS['wp_query']->queried_object    = $season;
		$GLOBALS['wp_query']->queried_object_id = $season->term_id;

		$this->assertSame( array( $this->label(), 'RT Season 33', 'RT PNW FIRST' ), $this->titles() );
	}

	public function test_single_competition_shows_the_season_then_type_district_and_week(): void {
		$season   = $this->rt_term( 'RT Season 34', 'rt-season-34', 'hyper_season' );
		$type     = $this->rt_term( 'RT Single Type', 'rt-single-type', Plugin::TYPE );
		$district = $this->rt_term( 'RT Single District', 'rt-single-district', Plugin::DISTRICT );
		$week     = $this->rt_term( 'RT Single Week', 'rt-single-week', Plugin::WEEK );
		$post_id  = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_title' => 'RT Long Trail' ) );
		wp_set_object_terms( $post_id, array( $season->term_id ), 'hyper_season' );
		wp_set_object_terms( $post_id, array( $type->term_id ), Plugin::TYPE );
		wp_set_object_terms( $post_id, array( $district->term_id ), Plugin::DISTRICT );
		wp_set_object_terms( $post_id, array( $week->term_id ), Plugin::WEEK );

		$this->go_to( get_permalink( $post_id ) );

		$this->assertSame(
			array( $this->label(), 'RT Season 34', 'RT Single Type', 'RT Single District', 'RT Single Week', 'RT Long Trail' ),
			$this->titles()
		);
	}

	public function test_single_competition_skips_taxonomies_it_has_no_term_in(): void {
		$season   = $this->rt_term( 'RT Season 35', 'rt-season-35', 'hyper_season' );
		$district = $this->rt_term( 'RT Lone District', 'rt-lone-district', Plugin::DISTRICT );
		$post_id  = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_title' => 'RT Partial Trail' ) );
		wp_set_object_terms( $post_id, array( $season->term_id ), 'hyper_season' );
		wp_set_object_terms( $post_id, array( $district->term_id ), Plugin::DISTRICT );

		$this->go_to( get_permalink( $post_id ) );

		$this->assertSame( array( $this->label(), 'RT Season 35', 'RT Lone District', 'RT Partial Trail' ), $this->titles() );
	}

	public function test_single_competition_without_extra_terms_is_unchanged(): void {
		$season  = $this->rt_term( 'RT Season 36', 'rt-season-36', 'hyper_season' );
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_title' => 'RT Plain Trail' ) );
		wp_set_object_terms( $post_id, array( $season->term_id ), 'hyper_season' );

		$this->go_to( get_permalink( $post_id ) );

		$this->assertSame( array( $this->label(), 'RT Season 36', 'RT Plain Trail' ), $this->titles() );
	}

	public function test_type_only_taxonomy_page_has_a_single_trail(): void {
		$type = $this->rt_term( 'RT Only Type', 'rt-only-type', Plugin::TYPE );

		$this->go_to( get_term_link( $type ) );

		$this->assertSame( array( $this->label(), 'Type', 'RT Only Type' ), $this->titles() );
	}
}
