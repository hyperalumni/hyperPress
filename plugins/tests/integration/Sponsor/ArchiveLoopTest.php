<?php
namespace HyperPress\Tests\Integration\Sponsor;

use HyperPress\Sponsor\ArchiveLoop;
use WP_Query;
use WP_UnitTestCase;

final class ArchiveLoopTest extends WP_UnitTestCase {

	/** Counter that gives every created post a later date, so the default (date DESC) order is the reverse of creation. */
	private int $clock = 0;

	public function set_up(): void {
		parent::set_up();
		$this->clock = 0;
		ArchiveLoop::register();
	}

	public function tear_down(): void {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	private function term_id( string $taxonomy, string $name, string $slug ): int {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term ) {
			return (int) $term->term_id;
		}
		$ids = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
		$this->assertIsArray( $ids );

		return (int) $ids['term_id'];
	}

	private function make_sponsor( string $title, string $season, int $tier ): int {
		++$this->clock;
		$id = self::factory()->post->create(
			array(
				'post_type'  => 'hyper_sponsor',
				'post_title' => $title,
				'post_date'  => gmdate( 'Y-m-d H:i:s', 1700000000 + $this->clock * 60 ),
			)
		);
		wp_set_object_terms( $id, array( $this->term_id( 'hyper_season', 'Season ' . $season, 'season-' . $season ) ), 'hyper_season' );
		wp_set_object_terms( $id, array( $this->term_id( 'hyper_tier', 'Level ' . $tier, 'level-' . $tier ) ), 'hyper_tier' );

		return $id;
	}

	public function test_filter_returns_the_loop_only_for_the_sponsor_archive_main_query(): void {
		$this->make_sponsor( 'a', '31', 3 );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$path = ArchiveLoop::posts_template( '' );
		$this->assertIsString( $path );
		$this->assertStringEndsWith( 'templates/loop.php', $path );
		$this->assertTrue( is_readable( $path ) );

		$this->go_to( home_url( '/?post_type=page' ) );
		$this->assertSame( '', ArchiveLoop::posts_template( '' ) );

		$this->go_to( home_url( '/?post_type=hyper_sponsor&feed=rss2' ) );
		$this->assertSame( '', ArchiveLoop::posts_template( '' ) );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		set_current_screen( 'dashboard' );
		$this->assertSame( 'kept', ArchiveLoop::posts_template( 'kept' ) );
	}

	public function test_page_size_defaults_to_24_and_is_filterable(): void {
		$this->make_sponsor( 'a', '31', 3 );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$this->assertSame( 24, (int) $GLOBALS['wp_query']->get( 'posts_per_page' ) );

		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => 2 );
		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$this->assertSame( 2, (int) $GLOBALS['wp_query']->get( 'posts_per_page' ) );

		// A secondary sponsor query and the sponsor feed keep their own page size.
		$secondary = new WP_Query( array( 'post_type' => 'hyper_sponsor' ) );
		$this->assertSame( (int) get_option( 'posts_per_page' ), (int) $secondary->get( 'posts_per_page' ) );

		$this->go_to( home_url( '/?post_type=hyper_sponsor&feed=rss2' ) );
		$this->assertSame( (int) get_option( 'posts_per_rss' ), (int) $GLOBALS['wp_query']->get( 'posts_per_page' ) );
		$this->assertNotSame( 2, (int) $GLOBALS['wp_query']->get( 'posts_per_page' ) );
		$this->assertNotSame( 24, (int) $GLOBALS['wp_query']->get( 'posts_per_page' ) );
	}

	public function test_filter_leaves_the_template_alone_for_a_non_main_query(): void {
		$this->make_sponsor( 'a', '31', 3 );
		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$main = $GLOBALS['wp_query'];

		// A second query installed as the global is a sponsor archive too, but it is not the main query.
		$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'hyper_sponsor' ) );
		try {
			$this->assertTrue( is_post_type_archive( 'hyper_sponsor' ) );
			$this->assertFalse( is_main_query() );
			$this->assertSame( 'kept', ArchiveLoop::posts_template( 'kept' ) );
		} finally {
			$GLOBALS['wp_query'] = $main;
		}

		$this->assertTrue( is_main_query() );
		$this->assertStringEndsWith( 'templates/loop.php', (string) ArchiveLoop::posts_template( 'kept' ) );
	}

	public function test_page_size_is_not_set_in_admin(): void {
		$this->make_sponsor( 'a', '31', 3 );
		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$main = $GLOBALS['wp_query'];
		$main->set( 'posts_per_page', 7 );

		set_current_screen( 'dashboard' );
		ArchiveLoop::set_page_size( $main );
		$this->assertSame( 7, (int) $main->get( 'posts_per_page' ), 'Admin keeps the query page size.' );

		set_current_screen( 'front' );
		ArchiveLoop::set_page_size( $main );
		$this->assertSame( 24, (int) $main->get( 'posts_per_page' ) );
	}

	public function test_previous_sponsor_id_is_zero_past_the_end(): void {
		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => 2 );
		foreach ( array( 3, 2, 1 ) as $tier ) {
			$this->make_sponsor( 'l' . $tier, '31', $tier );
		}

		// Page 2 is the last page (offset 1); page 9 starts at offset 15, past the three sponsors.
		$this->go_to( home_url( '/?post_type=hyper_sponsor&paged=9' ) );
		$this->assertSame( 9, (int) $GLOBALS['wp_query']->get( 'paged' ) );
		$this->assertSame( 0, ArchiveLoop::previous_sponsor_id( $GLOBALS['wp_query'] ) );
	}

	public function test_previous_sponsor_id(): void {
		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => 2 );
		$ids = array();
		foreach ( array( 5, 4, 3, 2, 1 ) as $tier ) {
			$ids[ $tier ] = $this->make_sponsor( 'l' . $tier, '31', $tier );
		}

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$this->assertSame( 0, ArchiveLoop::previous_sponsor_id( $GLOBALS['wp_query'] ) );

		$this->go_to( home_url( '/?post_type=hyper_sponsor&paged=2' ) );
		$this->assertSame( $ids[4], ArchiveLoop::previous_sponsor_id( $GLOBALS['wp_query'] ) );

		$this->go_to( home_url( '/?post_type=hyper_sponsor&paged=3' ) );
		$this->assertSame( $ids[2], ArchiveLoop::previous_sponsor_id( $GLOBALS['wp_query'] ) );
	}

	public function test_previous_sponsor_id_uses_the_season_scoped_order(): void {
		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => 2 );
		$this->make_sponsor( 'l1', '31', 1 );
		$l3 = $this->make_sponsor( 'l3', '31', 3 );
		$this->make_sponsor( 'l4', '31', 4 );
		$this->make_sponsor( 'other', '30', 5 );
		flush_rewrite_rules();

		$this->go_to( home_url( '/seasons/season-31/sponsors/?paged=2' ) );

		$this->assertSame( 2, (int) $GLOBALS['wp_query']->get( 'paged' ) );
		$this->assertSame( $l3, ArchiveLoop::previous_sponsor_id( $GLOBALS['wp_query'] ) );
	}

	public function test_filtered_minus_one_shows_all_and_other_values_are_clamped(): void {
		$this->make_sponsor( 'a', '31', 3 );

		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => -1 );
		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$this->assertSame( -1, (int) $GLOBALS['wp_query']->get( 'posts_per_page' ) );
		remove_all_filters( 'hyperpress_sponsor_archive_per_page' );

		foreach ( array( 0, -5 ) as $value ) {
			add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => $value, 10, 0 );
			$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
			$this->assertSame( 1, (int) $GLOBALS['wp_query']->get( 'posts_per_page' ), "Filtered {$value}." );
			remove_all_filters( 'hyperpress_sponsor_archive_per_page' );
		}
	}

	public function test_previous_sponsor_id_is_zero_when_showing_all(): void {
		$this->make_sponsor( 'a', '31', 3 );
		$this->make_sponsor( 'b', '31', 2 );
		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => -1 );

		$this->go_to( home_url( '/?post_type=hyper_sponsor&paged=2' ) );
		$this->assertSame( -1, (int) $GLOBALS['wp_query']->get( 'posts_per_page' ) );
		$this->assertSame( 0, ArchiveLoop::previous_sponsor_id( $GLOBALS['wp_query'] ) );
	}
}
