<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Plugin;
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

	public function set_up(): void {
		parent::set_up();
		global $wp_filter;
		$this->saved_filters = $wp_filter['hyperpress_banner_content'] ?? null;
		remove_all_filters( 'hyperpress_banner_content' );

		if ( ! taxonomy_exists( 'hyper_season' ) ) {
			register_taxonomy( 'hyper_season', array( Plugin::POST_TYPE ), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
		}
		foreach ( array( Plugin::DISTRICT, Plugin::WEEK, Plugin::TYPE ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy, array( Plugin::POST_TYPE ), array( 'public' => true ) );
			}
		}
		if ( ! post_type_exists( Plugin::POST_TYPE ) ) {
			register_post_type( Plugin::POST_TYPE, array( 'public' => true, 'has_archive' => true ) );
		}
		flush_rewrite_rules();
		update_option( Settings::OPTION, array( Settings::BANNER_SUBTITLE => 'Compete' ) );
		set_theme_mod( 'hyperpress_hyper_green', '#0a0' );
		Plugin::register_banner();
	}

	public function tear_down(): void {
		global $wp_filter;
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

	public function test_type_archive_banner(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );

		$this->go_to( get_term_link( $district ) );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'District Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Compete', $banner['subtitle'] );
		$this->assertSame( '#0a0', $banner['backgroundColor'] );
	}

	public function test_district_and_week_archive_banners(): void {
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

	public function test_type_page_with_season_query_keeps_competition_banner(): void {
		$district = $this->term( 'District', 'district', Plugin::TYPE );
		$season   = $this->term( 'Season 31', 'season-31', 'hyper_season' );
		// Stands in for the Season plugin's banner, which runs first and sets its own title.
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) {
				$banner['title'] = 'Season 31';
				return $banner;
			},
			5
		);

		$this->go_to( '/?hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $district->slug );
		$this->set_queried( $season );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'District Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );

		$this->set_queried( $district );
		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertSame( 'District Competitions', $banner['title'] );
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
		$this->assertSame( 'News', $banner['subtitle'] );
	}
}
