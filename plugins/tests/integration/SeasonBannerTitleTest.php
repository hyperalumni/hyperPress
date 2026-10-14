<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Utils\Banner as HyperPressBanner;
use WP_UnitTestCase;

final class SeasonBannerTitleTest extends WP_UnitTestCase {

	private bool $registered_type = false;

	private bool $registered_gizmo = false;

	public function tear_down(): void {
		if ( $this->registered_type ) {
			unregister_taxonomy( 'hyper_competition_type' );
			$this->registered_type = false;
		}
		if ( $this->registered_gizmo ) {
			unregister_post_type( 'hyper_gizmo' );
			$this->registered_gizmo = false;
		}
		$GLOBALS['wp_query']->queried_object    = null;
		$GLOBALS['wp_query']->queried_object_id = 0;
		remove_all_filters( 'gettext' );
		parent::tear_down();
	}

	public function test_banner_title_is_the_term_name_and_is_not_translated(): void {
		$term_id = self::factory()->term->create(
			array(
				'taxonomy' => 'hyper_season',
				'name'     => 'Open Sans',
			)
		);
		$this->go_to( get_term_link( $term_id, 'hyper_season' ) );
		add_filter( 'gettext', static fn (): string => 'TRANSLATED' );

		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );

		$this->assertSame( 'season', $banner['type'] );
		$this->assertSame( 'Open Sans', $banner['title'] );
	}

	private function query_season_and_type(): void {
		if ( ! taxonomy_exists( 'hyper_competition_type' ) ) {
			register_taxonomy( 'hyper_competition_type', array( 'post' ), array( 'public' => true ) );
			$this->registered_type = true;
		}
		self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => 'Season 31', 'slug' => 'season-31' ) );
		self::factory()->term->create( array( 'taxonomy' => 'hyper_competition_type', 'name' => 'District', 'slug' => 'district' ) );
		$this->go_to( '/?hyper_season=season-31&hyper_competition_type=district' );
		$this->stub_queried_type();
	}

	/**
	 * Core may hand back the type term as the queried object; code that read get_queried_object() for the season would then use the wrong term.
	 */
	private function stub_queried_type(): void {
		$district = get_term_by( 'slug', 'district', 'hyper_competition_type' );
		$GLOBALS['wp_query']->queried_object    = $district;
		$GLOBALS['wp_query']->queried_object_id = $district->term_id;
	}

	public function test_banner_title_is_the_competition_title_when_type_is_also_queried(): void {
		$this->query_season_and_type();

		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );

		// Both the season and the competition banner fire on this URL; the competition one names the type and wins (competition-type banner).
		$this->assertSame( 'District Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
	}

	public function test_utils_banner_term_name_is_the_season_when_type_is_also_queried(): void {
		$this->query_season_and_type();
		register_post_type( 'hyper_gizmo', array( 'public' => true, 'has_archive' => 'gizmos' ) );
		$this->registered_gizmo = true;
		$seen = null;
		add_filter(
			'hyperpress_banner_args',
			static function ( $banner, $post_type, $ctx ) use ( &$seen ) {
				$seen = $ctx['term_name'];
				return $banner;
			},
			10,
			3
		);
		HyperPressBanner::register(
			'hyper_gizmo',
			array(
				'type'          => 'gizmo',
				'subtitle'      => static fn() => '',
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => 'Gizmos',
			)
		);
		$this->go_to( '/?post_type=hyper_gizmo&hyper_season=season-31&hyper_competition_type=district' );
		$this->stub_queried_type();

		apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );

		$this->assertSame( 'Season 31', $seen );
	}

	public function test_season_breadcrumbs_for_a_season_page_with_type_queried(): void {
		$this->query_season_and_type();

		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertSame( array( 'Seasons', 'Season 31' ), array_column( $trail, 'title' ) );
	}
}
