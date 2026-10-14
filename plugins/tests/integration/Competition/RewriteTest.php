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
		remove_all_filters( 'pre_option_permalink_structure', 99 );
		$this->set_permalink_structure( '/%postname%/' );
		parent::tear_down();
	}

	/**
	 * The integration bootstrap forces pretty permalinks through pre_option_permalink_structure,
	 * so a later filter is needed for set_permalink_structure() to take effect.
	 */
	private function use_plain_permalinks(): void {
		remove_all_filters( 'pre_option_permalink_structure', 99 );
		add_filter( 'pre_option_permalink_structure', static fn (): string => '', 99 );
		$this->set_permalink_structure( '' );
	}

	/**
	 * Find or create a term. The plugins seed their terms outside the test transaction.
	 */
	private function term( string $name, string $slug, string $taxonomy ): \WP_Term {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term ) {
			$term = self::factory()->term->create_and_get(
				array(
					'taxonomy' => $taxonomy,
					'name'     => $name,
					'slug'     => $slug,
				)
			);
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
		$parent = self::factory()->term->create_and_get(
			array(
				'taxonomy' => Plugin::TYPE,
				'name'     => 'rt-parent',
				'slug'     => 'rt-parent',
			)
		);
		$child  = self::factory()->term->create_and_get(
			array(
				'taxonomy' => Plugin::TYPE,
				'name'     => 'rt-child',
				'slug'     => 'rt-child',
				'parent'   => $parent->term_id,
			)
		);
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
		$parent = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'hyper_district',
				'name'     => 'rt-dparent',
				'slug'     => 'rt-dparent',
			)
		);
		$child  = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'hyper_district',
				'name'     => 'rt-dchild',
				'slug'     => 'rt-dchild',
				'parent'   => $parent->term_id,
			)
		);
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
		$parent = self::factory()->term->create_and_get(
			array(
				'taxonomy' => Plugin::TYPE,
				'name'     => 'qualifying',
				'slug'     => 'rt-qualifying',
			)
		);
		self::factory()->term->create_and_get(
			array(
				'taxonomy' => Plugin::TYPE,
				'name'     => 'district',
				'slug'     => 'rt-district-type',
				'parent'   => $parent->term_id,
			)
		);
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

	/**
	 * A fresh, test-only term (optionally under a parent).
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

	/**
	 * Own season, hierarchical type, district and week terms for the archive_url tests.
	 *
	 * @return array{season: \WP_Term, type: \WP_Term, child_type: \WP_Term, district: \WP_Term, child_district: \WP_Term, week: \WP_Term}
	 */
	private function url_terms(): array {
		$type     = $this->rt_term( 'RT Qualifying', 'rt-qualifying', Plugin::TYPE );
		$district = $this->rt_term( 'RT Parent District', 'rt-dparent', Plugin::DISTRICT );

		return array(
			'season'         => $this->term( 'Season 31', 'season-31', 'hyper_season' ),
			'type'           => $type,
			'child_type'     => $this->rt_term( 'RT District Type', 'rt-district-type', Plugin::TYPE, $type->term_id ),
			'district'       => $district,
			'child_district' => $this->rt_term( 'RT NE', 'rt-ne', Plugin::DISTRICT, $district->term_id ),
			'week'           => $this->rt_term( 'RT Week 1', 'rt-week-1', Plugin::WEEK ),
		);
	}

	public function test_archive_url_pretty_permalinks(): void {
		$t    = $this->url_terms();
		$base = home_url( '/seasons/season-31/competitions/' );

		$this->assertSame( $base . 'rt-qualifying/', Rewrite::archive_url( $t['season'], $t['type'] ) );
		$this->assertSame( $base . 'rt-qualifying/rt-district-type/', Rewrite::archive_url( $t['season'], $t['child_type'] ) );
		$this->assertSame( $base . 'districts/rt-dparent/', Rewrite::archive_url( $t['season'], $t['district'] ) );
		$this->assertSame( $base . 'districts/rt-dparent/rt-ne/', Rewrite::archive_url( $t['season'], $t['child_district'] ) );
		$this->assertSame( $base . 'weeks/rt-week-1/', Rewrite::archive_url( $t['season'], $t['week'] ) );
	}

	public function test_archive_url_plain_permalinks(): void {
		$t = $this->url_terms();
		$this->use_plain_permalinks();
		$base = home_url( '/' ) . '?hyper_season=season-31&post_type=' . Plugin::POST_TYPE;

		$this->assertSame( $base . '&hyper_competition_type=rt-qualifying', Rewrite::archive_url( $t['season'], $t['type'] ) );
		$this->assertSame( $base . '&hyper_competition_type=rt-district-type', Rewrite::archive_url( $t['season'], $t['child_type'] ) );
		$this->assertSame( $base . '&hyper_district=rt-dparent', Rewrite::archive_url( $t['season'], $t['district'] ) );
		$this->assertSame( $base . '&hyper_district=rt-ne', Rewrite::archive_url( $t['season'], $t['child_district'] ) );
		$this->assertSame( $base . '&hyper_week=rt-week-1', Rewrite::archive_url( $t['season'], $t['week'] ) );
	}

	/**
	 * @return array<string, array{0: bool}>
	 */
	public function permalink_modes(): array {
		return array(
			'pretty' => array( true ),
			'plain'  => array( false ),
		);
	}

	/**
	 * @dataProvider permalink_modes
	 */
	public function test_archive_url_round_trips_to_the_competition_archive( bool $pretty ): void {
		$t = $this->url_terms();
		if ( ! $pretty ) {
			$this->use_plain_permalinks();
		}

		$cases = array(
			array( $t['type'], Plugin::TYPE, 'rt-qualifying' ),
			array( $t['child_type'], Plugin::TYPE, 'rt-district-type' ),
			array( $t['district'], 'hyper_district', 'rt-dparent' ),
			array( $t['child_district'], 'hyper_district', 'rt-ne' ),
			array( $t['week'], 'hyper_week', 'rt-week-1' ),
		);
		foreach ( $cases as list( $term, $taxonomy, $slug ) ) {
			$url = Rewrite::archive_url( $t['season'], $term );
			$this->assertNotSame( '', $url );

			$this->go_to( $url );

			$this->assertFalse( is_404(), $url );
			$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ), $url );
			$this->assertSame( 'season-31', get_query_var( 'hyper_season' ), $url );
			$this->assertSame( $slug, get_query_var( $taxonomy ), $url );
			$request_term = RequestTerm::get( $taxonomy );
			$this->assertNotNull( $request_term, $url );
			$this->assertSame( $term->term_id, $request_term->term_id, $url );
		}
	}

	public function test_archive_url_is_empty_for_other_taxonomies_and_missing_pieces(): void {
		$t        = $this->url_terms();
		$category = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'category',
				'name'     => 'RT Cat',
			)
		);

		$this->assertSame( '', Rewrite::archive_url( $t['season'], $category ) );
		$this->assertSame( '', Rewrite::archive_url( $t['season'], $t['season'] ) );
		// A term that is not a season.
		$this->assertSame( '', Rewrite::archive_url( $t['week'], $t['type'] ) );

		$post_type = get_post_type_object( Plugin::POST_TYPE );
		$district  = get_taxonomy( 'hyper_district' );
		$season_tx = get_taxonomy( 'hyper_season' );
		$saved     = array( $post_type->has_archive, $district->rewrite, $season_tx->rewrite );

		try {
			$post_type->has_archive = false;
			$this->assertSame( '', Rewrite::archive_url( $t['season'], $t['type'] ), 'No archive slug.' );
			$post_type->has_archive = $saved[0];

			$district->rewrite = false;
			$this->assertSame( '', Rewrite::archive_url( $t['season'], $t['district'] ), 'No district prefix slug.' );
			$this->assertNotSame( '', Rewrite::archive_url( $t['season'], $t['type'] ), 'Type needs no prefix.' );
			$district->rewrite = $saved[1];

			$season_tx->rewrite = false;
			$this->assertSame( '', Rewrite::archive_url( $t['season'], $t['type'] ), 'No season slug.' );
		} finally {
			$post_type->has_archive = $saved[0];
			$district->rewrite      = $saved[1];
			$season_tx->rewrite     = $saved[2];
		}

		$this->assertNotSame( '', Rewrite::archive_url( $t['season'], $t['type'] ) );
	}

	/**
	 * Creates the season and the three terms and assigns them to a competition.
	 *
	 * @return array{0: int, 1: array} Post ID and url_terms().
	 */
	private function competition_with_terms(): array {
		$t       = $this->url_terms();
		$post_id = self::factory()->post->create(
			array(
				'post_type'  => Plugin::POST_TYPE,
				'post_title' => 'RT Link Trail',
			)
		);
		wp_set_object_terms( $post_id, array( $t['season']->term_id ), 'hyper_season' );
		wp_set_object_terms( $post_id, array( $t['child_type']->term_id ), Plugin::TYPE );
		wp_set_object_terms( $post_id, array( $t['child_district']->term_id ), Plugin::DISTRICT );
		wp_set_object_terms( $post_id, array( $t['week']->term_id ), Plugin::WEEK );

		return array( $post_id, $t );
	}

	public function test_boot_hooks_the_season_scoped_term_urls(): void {
		$this->assertNotFalse( has_filter( 'hyperpress_breadcrumbs_term_url', array( Plugin::class, 'term_crumb_url' ) ) );
	}

	public function test_single_competition_crumbs_link_to_the_season_scoped_archives(): void {
		list( $post_id, $t ) = $this->competition_with_terms();

		$this->go_to( get_permalink( $post_id ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$base = home_url( '/seasons/season-31/competitions/' );
		$this->assertSame(
			array( 'Competitions', 'Season 31', 'RT District Type', 'RT NE', 'RT Week 1', 'RT Link Trail' ),
			array_slice( array_column( $trail, 'title' ), -6 )
		);
		$by_title = array_column( $trail, 'url', 'title' );
		$this->assertSame( $base . 'rt-qualifying/rt-district-type/', $by_title['RT District Type'] );
		$this->assertSame( $base . 'districts/rt-dparent/rt-ne/', $by_title['RT NE'] );
		$this->assertSame( $base . 'weeks/rt-week-1/', $by_title['RT Week 1'] );
		// The season crumb keeps linking to the season's competitions archive.
		$this->assertSame( untrailingslashit( $base ), $by_title['Season 31'] );
	}

	public function test_archive_with_a_season_crumbs_link_to_the_season_scoped_archives(): void {
		$t = $this->url_terms();

		$this->go_to( home_url( '/seasons/season-31/competitions/districts/rt-dparent/rt-ne/' ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$by_title = array_column( $trail, 'url', 'title' );
		$this->assertSame( home_url( '/seasons/season-31/competitions/districts/rt-dparent/rt-ne/' ), $by_title['RT NE'] );
		$this->assertSame( array( 'Season 31', 'RT NE' ), array_slice( array_column( $trail, 'title' ), -2 ) );
		$this->assertSame( $t['child_district']->term_id, RequestTerm::get( 'hyper_district' )->term_id );
	}

	public function test_crumbs_without_a_season_keep_the_all_seasons_term_links(): void {
		$t       = $this->url_terms();
		$post_id = self::factory()->post->create(
			array(
				'post_type'  => Plugin::POST_TYPE,
				'post_title' => 'RT No Season',
			)
		);
		wp_set_object_terms( $post_id, array( $t['child_district']->term_id ), Plugin::DISTRICT );

		$this->go_to( get_permalink( $post_id ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$by    = array_column( $trail, 'url', 'title' );
		$this->assertSame( get_term_link( $t['child_district'] ), $by['RT NE'] );

		$this->go_to( home_url( '/?post_type=' . Plugin::POST_TYPE . '&hyper_district=rt-ne' ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$by    = array_column( $trail, 'url', 'title' );
		$this->assertSame( get_term_link( $t['child_district'] ), $by['RT NE'] );
	}

	public function test_crumbs_use_the_plain_season_scoped_urls_with_plain_permalinks(): void {
		list( $post_id ) = $this->competition_with_terms();
		$this->use_plain_permalinks();

		$this->go_to( get_permalink( $post_id ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$by   = array_column( $trail, 'url', 'title' );
		$base = home_url( '/' ) . '?hyper_season=season-31&post_type=' . Plugin::POST_TYPE;
		$this->assertSame( $base . '&hyper_week=rt-week-1', $by['RT Week 1'] );
		$this->assertSame( $base . '&hyper_district=rt-ne', $by['RT NE'] );
		$this->assertSame( $base . '&hyper_competition_type=rt-district-type', $by['RT District Type'] );
	}

	public function test_taxonomy_only_pages_keep_their_trails(): void {
		$t = $this->url_terms();

		$this->go_to( get_term_link( $t['child_district'] ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertSame( array( 'Competitions', get_taxonomy( 'hyper_district' )->labels->singular_name, 'RT NE' ), array_column( $trail, 'title' ) );
		$this->assertArrayNotHasKey( 'url', $trail[1] );
		$this->assertArrayNotHasKey( 'url', $trail[2] );
	}

	public function test_term_crumb_url_leaves_other_post_types_and_missing_seasons_alone(): void {
		$t = $this->url_terms();

		$this->assertSame( 'x', Plugin::term_crumb_url( 'x', $t['type'], 'hyper_gizmo', $t['season'] ) );
		$this->assertSame( 'x', Plugin::term_crumb_url( 'x', $t['type'], Plugin::POST_TYPE, null ) );
		$this->assertSame( 'x', Plugin::term_crumb_url( 'x', self::factory()->term->create_and_get( array( 'taxonomy' => 'category' ) ), Plugin::POST_TYPE, $t['season'] ) );
		$this->assertSame( Rewrite::archive_url( $t['season'], $t['type'] ), Plugin::term_crumb_url( 'x', $t['type'], Plugin::POST_TYPE, $t['season'] ) );
	}

	public function test_maybe_flush_reflushes_when_a_prefix_slug_changes(): void {
		Rewrite::maybe_flush();

		$flushes = 0;
		$counter = static function ( $value ) use ( &$flushes ) {
			++$flushes;
			return $value;
		};
		add_filter( 'pre_update_option_rewrite_rules', $counter );

		foreach ( array(
			'hyper_district' => 'rt-areas',
			'hyper_week'     => 'rt-rounds',
		) as $taxonomy => $slug ) {
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
