<?php
namespace HyperPress\Tests\Integration\Program;

use HyperPress\Competition\Plugin as Competition;
use HyperPress\Program\Plugin;
use WP_UnitTestCase;

final class ProgramBreadcrumbsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();
	}

	public function tear_down(): void {
		$GLOBALS['wp_query']->queried_object    = null;
		$GLOBALS['wp_query']->queried_object_id = 0;
		parent::tear_down();
	}

	/**
	 * The array the theme passes in: just the Home crumb.
	 */
	private function home_only(): array {
		return array(
			array(
				'title'   => 'Home',
				'url'     => home_url( '/' ),
				'classes' => array(
					'li'    => array(),
					'bread' => array(),
				),
			),
		);
	}

	private function trail(): array {
		return apply_filters( 'hyperpress_breadcrumbs_content', $this->home_only() );
	}

	private function titles( array $trail ): array {
		return array_column( $trail, 'title' );
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
					'taxonomy' => Plugin::TAXONOMY,
					'name'     => 'FTC',
					'slug'     => 'ftc',
					'parent'   => $first,
				)
			);
		}
		return $term;
	}

	private function first(): \WP_Term {
		$this->ftc();
		return get_term_by( 'slug', 'first', Plugin::TAXONOMY );
	}

	private function force_queried_program(): void {
		$program                                = $this->ftc();
		$GLOBALS['wp_query']->queried_object    = $program;
		$GLOBALS['wp_query']->queried_object_id = $program->term_id;
	}

	public function test_child_program_gets_programs_parent_and_term(): void {
		$first = $this->first();
		$this->go_to( home_url( '/programs/first/ftc/' ) );

		$this->assertFalse( is_404() );
		$trail = $this->trail();

		$this->assertSame( array( 'Home', 'Programs', 'FIRST', 'FTC' ), $this->titles( $trail ) );
		$this->assertArrayNotHasKey( 'url', $trail[1] );
		$this->assertSame( get_term_link( $first ), $trail[2]['url'] );
		$this->assertArrayNotHasKey( 'url', $trail[3] );
		foreach ( array( 1, 2, 3 ) as $i ) {
			$this->assertSame(
				array(
					'li'    => array(),
					'bread' => array(),
				),
				$trail[ $i ]['classes']
			);
		}
	}

	public function test_query_string_form_gets_the_same_trail(): void {
		$first = $this->first();
		$this->go_to( '/?hyper_program=ftc' );

		$trail = $this->trail();

		$this->assertSame( array( 'Home', 'Programs', 'FIRST', 'FTC' ), $this->titles( $trail ) );
		$this->assertSame( get_term_link( $first ), $trail[2]['url'] );
	}

	public function test_parent_program_gets_programs_and_term(): void {
		$this->first();
		$this->go_to( home_url( '/programs/first/' ) );

		$trail = $this->trail();

		$this->assertSame( array( 'Home', 'Programs', 'FIRST' ), $this->titles( $trail ) );
		$this->assertArrayNotHasKey( 'url', $trail[1] );
		$this->assertArrayNotHasKey( 'url', $trail[2] );
	}

	public function test_parentless_program_gets_programs_and_term(): void {
		self::factory()->term->create( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'rt-solo', 'slug' => 'rt-solo' ) );
		$this->go_to( '/?hyper_program=rt-solo' );

		$this->assertSame( array( 'Home', 'Programs', 'rt-solo' ), $this->titles( $this->trail() ) );
	}

	public function test_label_follows_the_taxonomy_object(): void {
		$this->first();
		$taxonomy              = get_taxonomy( Plugin::TAXONOMY );
		$original              = $taxonomy->labels->name;
		$taxonomy->labels->name = 'rt-Programmes';
		$this->go_to( home_url( '/programs/first/' ) );

		try {
			$titles = $this->titles( $this->trail() );
		} finally {
			$taxonomy->labels->name = $original;
		}

		$this->assertSame( array( 'Home', 'rt-Programmes', 'FIRST' ), $titles );
	}

	public function test_plain_page_is_unchanged(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $page_id ) );

		$this->assertSame( $this->home_only(), $this->trail() );
	}

	public function test_post_type_archive_with_a_program_is_unchanged_by_the_program_trail(): void {
		$this->ftc();
		$this->go_to( '/?post_type=' . Competition::POST_TYPE . '&hyper_program=ftc' );

		$this->assertTrue( is_post_type_archive( Competition::POST_TYPE ) );
		$titles = $this->titles( $this->trail() );

		$this->assertNotContains( 'Programs', $titles );
		$this->assertNotContains( 'FTC', $titles );
	}

	public function test_season_request_gets_only_the_season_trail(): void {
		$this->ftc();
		$season = self::factory()->term->create_and_get( array( 'taxonomy' => 'hyper_season', 'name' => 'rt-season', 'slug' => 'rt-season' ) );
		$this->go_to( '/?hyper_program=ftc&hyper_season=rt-season' );
		$this->force_queried_program();

		$this->assertSame( array( 'Home', 'Seasons', $season->name ), $this->titles( $this->trail() ) );
	}

	public function test_competition_taxonomy_request_gets_only_the_competition_trail(): void {
		$this->ftc();
		self::factory()->term->create( array( 'taxonomy' => Competition::DISTRICT, 'name' => 'rt-district', 'slug' => 'rt-district' ) );
		$this->go_to( '/?hyper_program=ftc&hyper_district=rt-district' );
		$this->force_queried_program();

		$this->assertSame( array( 'Home', 'Competitions', 'District', 'rt-district' ), $this->titles( $this->trail() ) );
	}

	public function test_wp_error_term_link_gives_an_empty_ancestor_url(): void {
		$this->first();
		$this->go_to( home_url( '/programs/first/ftc/' ) );
		$to_error = static function () {
			return new \WP_Error( 'rt', 'no link' );
		};
		add_filter( 'term_link', $to_error );

		try {
			$trail = $this->trail();
		} finally {
			remove_filter( 'term_link', $to_error );
		}

		$this->assertSame( array( 'Home', 'Programs', 'FIRST', 'FTC' ), $this->titles( $trail ) );
		$this->assertSame( '', $trail[2]['url'] );
	}
}
