<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Season\SeasonBanner;
use HyperPress\Season\SortedSeasons;
use HyperPress\Utils\Banner;
use HyperPress\Utils\Breadcrumbs;
use WP_UnitTestCase;

final class SeasonGuardsTest extends WP_UnitTestCase {

	/** @var callable|null */
	private $previous = null;

	public function set_up(): void {
		parent::set_up();
		global $shortcode_tags;
		$this->previous = $shortcode_tags['hyperpress_date_distance'] ?? null;
	}

	public function tear_down(): void {
		remove_shortcode( 'hyperpress_date_distance' );
		if ( null !== $this->previous ) {
			add_shortcode( 'hyperpress_date_distance', $this->previous );
		}
		unregister_post_type( 'hyper_gizmo' );
		parent::tear_down();
	}

	private function register_gizmo(): void {
		register_post_type( 'hyper_gizmo', array( 'public' => true, 'has_archive' => 'gizmos', 'labels' => array( 'name' => 'Gizmos' ) ) );
		register_taxonomy_for_object_type( 'hyper_season', 'hyper_gizmo' );
		flush_rewrite_rules();
	}

	private function post_with_seasons( array $names ): int {
		$post_id = self::factory()->post->create();
		$ids     = array();
		foreach ( $names as $name ) {
			$ids[] = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => $name ) );
		}
		wp_set_object_terms( $post_id, $ids, 'hyper_season' );

		return $post_id;
	}

	public function test_season_count_renders_nothing_without_the_date_distance_shortcode(): void {
		remove_shortcode( 'hyperpress_date_distance' );

		$this->assertSame( '', do_shortcode( '[hyperpress_season_count]' ) );
	}

	public function test_season_count_passes_boolean_words_and_translatable_label(): void {
		add_shortcode(
			'hyperpress_date_distance',
			static function ( $atts ): string {
				return '[suffix=' . ( $atts['suffix'] ?? '' ) . ']';
			}
		);

		$this->assertSame( '[suffix=true] Season', do_shortcode( '[hyperpress_season_count suffix="true"]' ) );
		$this->assertSame( '[suffix=false] Season', do_shortcode( '[hyperpress_season_count]' ) );
	}

	public function test_sorted_seasons_is_empty_when_the_terms_lookup_errors(): void {
		$post_id = $this->post_with_seasons( array( '2026' ) );
		$this->go_to( get_permalink( $post_id ) );
		add_filter(
			'wp_get_object_terms',
			static fn() => new \WP_Error( 'boom', 'lookup failed' )
		);

		$this->assertSame( array(), SortedSeasons::get() );
	}

	public function test_sorted_seasons_orders_names_naturally_newest_first(): void {
		$post_id = $this->post_with_seasons( array( 'Season 9', 'Season 10', 'Season 2' ) );
		$this->go_to( get_permalink( $post_id ) );

		$names = array_map( static fn( $term ) => $term->name, SortedSeasons::get() );
		$this->assertSame( array( 'Season 10', 'Season 9', 'Season 2' ), $names );
	}

	public function test_utils_breadcrumbs_pick_the_naturally_newest_season(): void {
		$this->register_gizmo();
		Breadcrumbs::register( array( 'hyper_gizmo' ) );
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_gizmo', 'post_title' => 'Widget' ) );
		$ids     = array(
			self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => 'Season 9' ) ),
			self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => 'Season 10' ) ),
		);
		wp_set_object_terms( $post_id, $ids, 'hyper_season' );

		$this->go_to( get_permalink( $post_id ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', 'Season 10', 'Widget' ), array_column( $trail, 'title' ) );
	}

	public function test_banner_filters_do_not_fail_when_the_queried_object_is_not_a_term(): void {
		$this->register_gizmo();
		Banner::register(
			'hyper_gizmo',
			array(
				'type'          => 'gizmo',
				'subtitle'      => static fn() => '',
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => 'Gizmos',
			)
		);
		SeasonBanner::register();
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026', 'slug' => '2026' ) );
		flush_rewrite_rules();
		$this->go_to( get_term_link( $term_id, 'hyper_season' ) );
		$this->assertTrue( is_tax( 'hyper_season' ) );

		// A queried object that is not a WP_Term (for example after a third party filter).
		$GLOBALS['wp_query']->queried_object    = (object) array( 'taxonomy' => 'hyper_season' );
		$GLOBALS['wp_query']->queried_object_id = 0;

		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
		$this->assertIsArray( $banner );
		$this->assertSame( 'Season', $banner['title'], 'falls back to the generic title' );
	}

	public function test_banner_filter_accepts_a_null_starting_value(): void {
		$this->register_gizmo();
		Banner::register(
			'hyper_gizmo',
			array(
				'type'          => 'gizmo',
				'subtitle'      => static fn() => '',
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => 'Gizmos',
			)
		);
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );

		$this->assertIsArray( apply_filters( 'hyperpress_banner_content', null ) );
	}

	public function test_season_breadcrumbs_are_translatable_and_survive_a_non_array_start(): void {
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026', 'slug' => '2026' ) );
		flush_rewrite_rules();
		$this->go_to( get_term_link( $term_id, 'hyper_season' ) );

		$trail = apply_filters( 'hyperpress_breadcrumbs_content', null );
		$this->assertSame( array( 'Seasons', '2026' ), array_column( $trail, 'title' ) );
	}
}
