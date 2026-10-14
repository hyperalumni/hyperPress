<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Plugin;
use HyperPress\Competition\Rewrite;
use HyperPress\Competition\Settings;
use HyperPress\Utils\Banner;
use WP_UnitTestCase;

final class BannerTest extends WP_UnitTestCase {

	/**
	 * The filter callbacks registered before this test, restored afterwards.
	 *
	 * @var mixed
	 */
	private $saved_filters;

	/**
	 * Taxonomies set_up() registered itself, removed again in tear_down().
	 *
	 * @var string[]
	 */
	private array $registered_taxonomies = array();

	/**
	 * Post types this test registered itself, removed again in tear_down().
	 *
	 * @var string[]
	 */
	private array $registered_post_types = array();

	public function set_up(): void {
		parent::set_up();
		global $wp_filter;
		$this->saved_filters = $wp_filter['hyperpress_banner_content'] ?? null;
		remove_all_filters( 'hyperpress_banner_content' );

		if ( ! taxonomy_exists( 'hyper_season' ) ) {
			register_taxonomy( 'hyper_season', array( Plugin::POST_TYPE ), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
			$this->registered_taxonomies[] = 'hyper_season';
		}
		foreach ( array( Plugin::DISTRICT, Plugin::WEEK, Plugin::TYPE ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy, array( Plugin::POST_TYPE ), array( 'public' => true ) );
				$this->registered_taxonomies[] = $taxonomy;
			}
		}
		if ( ! post_type_exists( Plugin::POST_TYPE ) ) {
			register_post_type( Plugin::POST_TYPE, array( 'public' => true, 'has_archive' => true ) );
			$this->registered_post_types[] = Plugin::POST_TYPE;
		}
		update_option( Settings::OPTION, array( Settings::BANNER_SUBTITLE => 'Compete' ) );
		set_theme_mod( 'hyperpress_hyper_green', '#0a0' );
		Plugin::register_banner();
	}

	public function tear_down(): void {
		global $wp_filter;
		$GLOBALS['wp_query']->queried_object    = null;
		$GLOBALS['wp_query']->queried_object_id = 0;
		foreach ( $this->registered_post_types as $post_type ) {
			unregister_post_type( $post_type );
		}
		foreach ( $this->registered_taxonomies as $taxonomy ) {
			unregister_taxonomy( $taxonomy );
		}
		$this->registered_post_types = array();
		$this->registered_taxonomies = array();
		remove_action( 'init', array( Rewrite::class, 'add_rules' ), 21 );
		remove_action( 'init', array( Rewrite::class, 'maybe_flush' ), 99 );
		remove_all_filters( 'hyperpress_banner_content' );
		if ( null !== $this->saved_filters ) {
			$wp_filter['hyperpress_banner_content'] = $this->saved_filters;
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

	private function set_queried( \WP_Term $term ): void {
		$GLOBALS['wp_query']->queried_object    = $term;
		$GLOBALS['wp_query']->queried_object_id = $term->term_id;
	}

	public function test_type_taxonomy_page_banner(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );

		$this->go_to( get_term_link( $district ) );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'District Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Compete', $banner['subtitle'] );
		$this->assertSame( '#0a0', $banner['backgroundColor'] );
	}

	public function test_district_and_week_taxonomy_page_banners(): void {
		$district = $this->term( 'NE FIRST', 'ne-first', Plugin::DISTRICT );
		$week     = $this->term( 'Week 1', 'week-1', Plugin::WEEK );

		$this->go_to( get_term_link( $district ) );
		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertSame( 'NE FIRST Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Compete', $banner['subtitle'] );

		$this->go_to( get_term_link( $week ) );
		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertSame( 'Week 1 Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Compete', $banner['subtitle'] );
	}

	public function test_combined_archive_banner(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $district->slug );
		$this->set_queried( $season );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'Competitions', $banner['title'] );
		$this->assertSame( 'Compete in District, Season 31', $banner['subtitle'] );

		// Same result when core hands back the type term as the queried object.
		$this->set_queried( $district );
		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertSame( 'Compete in District, Season 31', $banner['subtitle'] );
	}

	public function test_type_page_with_season_query_defers_to_the_season_banner(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );
		// Stands in for the Season plugin's banner, which runs first and sets its own title and type.
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) {
				$banner['title'] = 'Season 31';
				$banner['type']  = 'season';
				return $banner;
			},
			5
		);

		$this->go_to( '/?hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $district->slug );
		$this->set_queried( $season );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'Season 31', $banner['title'] );
		$this->assertSame( 'season', $banner['type'] );

		$this->set_queried( $district );
		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertSame( 'Season 31', $banner['title'] );
		$this->assertSame( 'season', $banner['type'] );
	}

	public function test_type_page_without_season_query_keeps_competition_banner(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		// Stands in for a banner that ran first and set its own title.
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) {
				$banner['title'] = 'Earlier title';
				return $banner;
			},
			5
		);

		$this->go_to( '/?' . Plugin::TYPE . '=' . $district->slug );
		$this->set_queried( $district );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'District Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
	}

	public function test_combined_archive_with_a_season_is_not_deferred(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $district->slug );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Competitions', $banner['title'] );
		$this->assertSame( 'Compete in District, Season 31', $banner['subtitle'] );
	}

	/**
	 * Register the competition banner after a stand-in for the theme's `hyperpress_banner_archive` filter.
	 *
	 * The theme's library files load before `init`, the plugins register their banner filters at `init`, both at
	 * priority 10, so on a live site the theme's filter runs first: when no banner type is set yet it sets
	 * type 'archive' and title 'Archive' on any archive (themes/hyperpress-foundation/library/banner/archive.php).
	 */
	private function register_banner_after_theme_filter(): void {
		remove_all_filters( 'hyperpress_banner_content' );
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) {
				if ( empty( $banner['type'] ) && is_archive() ) {
					$banner['type']  = 'archive';
					$banner['title'] = 'Archive';
				}
				return $banner;
			}
		);
		Plugin::register_banner();
	}

	private function use_pretty_permalinks(): void {
		$this->set_permalink_structure( '/%postname%/' );
		remove_action( 'init', array( Rewrite::class, 'add_rules' ), 21 );
		Rewrite::register();
		Rewrite::add_rules();
		flush_rewrite_rules();
	}

	public function test_combined_archive_keeps_the_competition_title_over_the_theme_archive_title(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );
		$this->register_banner_after_theme_filter();

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $district->slug );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Compete in District, Season 31', $banner['subtitle'] );
	}

	public function test_pretty_combined_archive_keeps_the_competition_title_over_the_theme_archive_title(): void {
		$this->term( 'District', 'district', Plugin::TYPE );
		$this->term( 'Season 31', 'season-31', 'hyper_season' );
		$this->use_pretty_permalinks();
		$this->register_banner_after_theme_filter();

		$this->go_to( home_url( '/seasons/season-31/competitions/district/' ) );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertSame( 'Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Compete in District, Season 31', $banner['subtitle'] );
	}

	public function test_season_competitions_archive_keeps_the_competition_title_over_the_theme_archive_title(): void {
		$this->term( 'Season 31', 'season-31', 'hyper_season' );
		$this->use_pretty_permalinks();
		$this->register_banner_after_theme_filter();

		$this->go_to( home_url( '/seasons/season-31/competitions/' ) );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertSame( 'Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Compete in Season 31', $banner['subtitle'] );
	}

	public function test_plain_competitions_archive_keeps_the_competition_title_over_the_theme_archive_title(): void {
		$this->use_pretty_permalinks();
		$this->register_banner_after_theme_filter();

		$this->go_to( home_url( '/competitions/' ) );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertSame( 'Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Compete', $banner['subtitle'] );
	}

	public function test_type_taxonomy_page_is_not_affected_by_the_theme_archive_title(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$this->register_banner_after_theme_filter();

		$this->go_to( get_term_link( $district ) );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'District Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
	}

	public function test_empty_subtitle_gets_no_term_suffix_on_the_combined_archive(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );
		// The competition default: no banner subtitle set, so there is nothing to append the terms to.
		update_option( Settings::OPTION, array() );
		$this->register_banner_after_theme_filter();

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $district->slug );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'Competitions', $banner['title'] );
		$this->assertSame( '', $banner['subtitle'] );
	}

	public function test_unknown_type_slug_sets_no_banner_type(): void {
		$this->go_to( '/?' . Plugin::TYPE . '=does-not-exist' );
		$input = array( 'title' => 'Theme title' );

		$this->assertSame( $input, apply_filters( 'hyperpress_banner_content', $input ) );
	}

	public function test_other_post_type_banner_unchanged(): void {
		Banner::register(
			'hyper_newsletter',
			array(
				'type'                     => 'newsletter',
				'subtitle'                 => static fn() => 'News',
				'color_mod'                => 'hyperpress_hyper_orange',
				'archive_title'            => 'Newsletters',
				'term_joiner'              => 'from',
				'always_set_archive_title' => true,
			)
		);
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( '/?post_type=hyper_newsletter&hyper_season=' . $season->slug );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => 'Preset' ) );
		$this->assertSame( 'Newsletters', $banner['title'] );
		$this->assertSame( 'newsletter', $banner['type'] );
		$this->assertSame( 'News from Season 31', $banner['subtitle'] );

		// A competition taxonomy in the request does not leak into a banner registered without `taxonomies`.
		$this->go_to( '/?post_type=hyper_newsletter&' . Plugin::TYPE . '=' . $district->slug );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => 'Preset' ) );
		$this->assertSame( 'newsletter', $banner['type'] );
		$this->assertSame( 'Newsletters', $banner['title'] );
		$this->assertSame( 'News', $banner['subtitle'] );
	}

	public function test_competition_banner_ignores_another_post_types_archive(): void {
		// Only the competition closure is registered, so nothing later can mask a leak.
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		if ( ! post_type_exists( 'hyper_newsletter' ) ) {
			register_post_type( 'hyper_newsletter', array( 'public' => true, 'has_archive' => true ) );
			$this->registered_post_types[] = 'hyper_newsletter';
		}

		$this->go_to( '/?post_type=hyper_newsletter&' . Plugin::TYPE . '=' . $district->slug );
		$input  = array( 'title' => 'Theme title' );
		$banner = apply_filters( 'hyperpress_banner_content', $input );

		$this->assertSame( $input, $banner );
		$this->assertArrayNotHasKey( 'type', $banner );
	}

	public function test_competition_banner_ignores_a_page_carrying_a_competition_taxonomy(): void {
		// Expected: a singular request (here a page) never takes the taxonomy-page branch, so the banner is untouched.
		$week    = $this->term( 'Week 1', 'week-1', Plugin::WEEK );
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'About' ) );

		$this->go_to( '/?page_id=' . $page_id . '&' . Plugin::WEEK . '=' . $week->slug );
		$input  = array( 'title' => 'Theme title' );
		$banner = apply_filters( 'hyperpress_banner_content', $input );

		$this->assertSame( $input, $banner );
	}
}
