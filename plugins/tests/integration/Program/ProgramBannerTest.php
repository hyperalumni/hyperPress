<?php
namespace HyperPress\Tests\Integration\Program;

use HyperPress\Competition\Plugin as Competition;
use HyperPress\Program\Plugin;
use HyperPress\Program\ProgramBanner;
use WP_UnitTestCase;

final class ProgramBannerTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();
		set_theme_mod( 'hyperpress_hyper_red', '#c0392b' );
		// Stand-in for the theme's generic archive fallback (library/banner/archive.php, priority 90).
		add_filter( 'hyperpress_banner_content', array( $this, 'generic_archive' ), 90 );
	}

	public function tear_down(): void {
		remove_filter( 'hyperpress_banner_content', array( $this, 'generic_archive' ), 90 );
		remove_theme_mod( 'hyperpress_hyper_red' );
		$GLOBALS['wp_query']->queried_object    = null;
		$GLOBALS['wp_query']->queried_object_id = 0;
		parent::tear_down();
	}

	public function generic_archive( $banner ) {
		if ( is_archive() && empty( $banner['title'] ) ) {
			if ( empty( $banner['type'] ) ) {
				$banner['type'] = 'archive';
			}
			$banner['title'] = 'Archive';
		}
		return $banner;
	}

	private function banner(): array {
		return apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
	}

	/**
	 * The plugins seed FIRST > FTC outside the test transaction, so look them up first.
	 */
	private function ftc(): \WP_Term {
		$term = get_term_by( 'slug', 'ftc', Plugin::TAXONOMY );
		if ( ! $term ) {
			$first = self::factory()->term->create( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'FIRST', 'slug' => 'first' ) );
			$term  = self::factory()->term->create_and_get(
				array(
					'taxonomy'    => Plugin::TAXONOMY,
					'name'        => 'FTC',
					'slug'        => 'ftc',
					'description' => 'FIRST Tech Challenge',
					'parent'      => $first,
				)
			);
		}
		return $term;
	}

	private function set_description( \WP_Term $term, string $description ): void {
		global $wpdb;
		$wpdb->update( $wpdb->term_taxonomy, array( 'description' => $description ), array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
		clean_term_cache( $term->term_id, Plugin::TAXONOMY );
	}

	public function test_query_string_program_gets_the_program_banner(): void {
		$this->ftc();
		$this->go_to( '/?hyper_program=ftc' );

		$banner = $this->banner();

		$this->assertSame( 'program', $banner['type'] );
		$this->assertSame( 'FTC', $banner['title'] );
		$this->assertSame( 'FIRST Tech Challenge', $banner['subtitle'] );
		$this->assertSame( '#c0392b', $banner['backgroundColor'] );
	}

	public function test_pretty_child_path_gets_the_childs_own_banner(): void {
		$this->ftc();
		$this->go_to( home_url( '/programs/first/ftc/' ) );

		$this->assertFalse( is_404() );
		$banner = $this->banner();

		$this->assertSame( 'program', $banner['type'] );
		$this->assertSame( 'FTC', $banner['title'] );
		$this->assertSame( 'FIRST Tech Challenge', $banner['subtitle'] );
		$this->assertSame( '#c0392b', $banner['backgroundColor'] );
	}

	public function test_parent_program_path_works(): void {
		$this->ftc();
		$first = get_term_by( 'slug', 'first', Plugin::TAXONOMY );
		$this->go_to( home_url( '/programs/first/' ) );

		$banner = $this->banner();

		$this->assertSame( 'program', $banner['type'] );
		$this->assertSame( $first->name, $banner['title'] );
		$this->assertSame( trim( wp_strip_all_tags( $first->description ) ), $banner['subtitle'] );
	}

	public function test_empty_description_leaves_the_subtitle_empty(): void {
		$term = self::factory()->term->create_and_get( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'rt-nodesc', 'slug' => 'rt-nodesc', 'description' => '' ) );
		$this->go_to( '/?hyper_program=rt-nodesc' );

		$banner = $this->banner();

		$this->assertSame( 'program', $banner['type'] );
		$this->assertSame( $term->name, $banner['title'] );
		$this->assertSame( '', $banner['subtitle'] );
	}

	public function test_description_markup_is_stripped(): void {
		$term = self::factory()->term->create_and_get( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'rt-markup', 'slug' => 'rt-markup' ) );
		$this->set_description( $term, " <script>alert(1)</script><b>Hello</b> <a href=\"#\">World</a> " );
		$this->go_to( '/?hyper_program=rt-markup' );

		$banner = $this->banner();

		$this->assertSame( 'Hello World', $banner['subtitle'] );
	}

	public function test_season_banner_wins_over_the_program_banner(): void {
		$this->ftc();
		$season = self::factory()->term->create_and_get( array( 'taxonomy' => 'hyper_season', 'name' => 'rt-season', 'slug' => 'rt-season' ) );
		$this->go_to( '/?hyper_program=ftc&hyper_season=rt-season' );
		$program                                = $this->ftc();
		$GLOBALS['wp_query']->queried_object    = $program;
		$GLOBALS['wp_query']->queried_object_id = $program->term_id;

		$banner = $this->banner();

		$this->assertSame( 'season', $banner['type'] );
		$this->assertSame( $season->name, $banner['title'] );
	}

	public function test_competition_banner_wins_over_the_program_banner(): void {
		$this->ftc();
		self::factory()->term->create( array( 'taxonomy' => Competition::DISTRICT, 'name' => 'rt-district', 'slug' => 'rt-district' ) );
		$this->go_to( '/?hyper_program=ftc&hyper_district=rt-district' );
		$program                                = $this->ftc();
		$GLOBALS['wp_query']->queried_object    = $program;
		$GLOBALS['wp_query']->queried_object_id = $program->term_id;

		$banner = $this->banner();

		$this->assertSame( 'competition', $banner['type'] );
	}

	public function test_post_type_archive_with_a_program_is_not_claimed(): void {
		$this->ftc();
		$this->go_to( '/?post_type=' . Competition::POST_TYPE . '&hyper_program=ftc' );

		$this->assertTrue( is_post_type_archive( Competition::POST_TYPE ) );
		$banner = $this->banner();

		$this->assertSame( 'competition', $banner['type'] );
	}

	public function test_program_banner_does_not_override_a_claimed_type(): void {
		$this->ftc();
		$this->go_to( '/?hyper_program=ftc' );

		$banner = ProgramBanner::filter( array( 'type' => 'other', 'title' => 'Kept' ) );

		$this->assertSame( 'other', $banner['type'] );
		$this->assertSame( 'Kept', $banner['title'] );
	}

	public function test_plain_page_is_untouched(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $page_id ) );

		$banner = $this->banner();

		$this->assertArrayNotHasKey( 'type', $banner );
	}

	public function test_season_only_archive_is_untouched_by_the_program_banner(): void {
		self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => 'rt-season-b', 'slug' => 'rt-season-b' ) );
		$this->go_to( '/?hyper_season=rt-season-b' );

		$banner = $this->banner();

		$this->assertSame( 'season', $banner['type'] );
		$this->assertSame( 'rt-season-b', $banner['title'] );
	}
}
