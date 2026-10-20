<?php
namespace HyperPress\Tests\Integration\Sponsor;

use HyperPress\Sponsor\Ordering;
use WP_Query;
use WP_UnitTestCase;

final class OrderingTest extends WP_UnitTestCase {

	/** Counter that gives every created post a later date, so the default (date DESC) order is the reverse of creation. */
	private int $clock = 0;

	public function set_up(): void {
		parent::set_up();
		$this->clock = 0;
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

	/**
	 * @param string[] $seasons Season numbers, e.g. array( '30', '32' ) or a free name such as 'Alumni'.
	 * @param array    $tiers   Tier levels (ints become "Level N") or free tier names.
	 */
	private function make_sponsor( string $title, array $seasons = array(), array $tiers = array(), int $menu_order = 0 ): int {
		++$this->clock;
		$id = self::factory()->post->create(
			array(
				'post_type'  => 'hyper_sponsor',
				'post_title' => $title,
				'menu_order' => $menu_order,
				'post_date'  => gmdate( 'Y-m-d H:i:s', 1700000000 + $this->clock * 60 ),
			)
		);
		$season_ids = array();
		foreach ( $seasons as $season ) {
			$name         = ctype_digit( $season ) ? 'Season ' . $season : $season;
			$season_ids[] = $this->term_id( 'hyper_season', $name, sanitize_title( $name ) );
		}
		$tier_ids = array();
		foreach ( $tiers as $tier ) {
			$name       = is_int( $tier ) ? 'Level ' . $tier : (string) $tier;
			$tier_ids[] = $this->term_id( 'hyper_tier', $name, sanitize_title( $name ) );
		}
		wp_set_object_terms( $id, $season_ids, 'hyper_season' );
		wp_set_object_terms( $id, $tier_ids, 'hyper_tier' );

		return $id;
	}

	/**
	 * @return string[] Titles of the main query's posts, in order.
	 */
	private function main_titles(): array {
		return array_map( 'get_the_title', wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
	}

	public function test_orders_by_newest_season_then_tier_highest_first(): void {
		$this->make_sponsor( 's30-L4', array( '30' ), array( 4 ) );
		$this->make_sponsor( 's31-L2', array( '31' ), array( 2 ) );
		$this->make_sponsor( 's32-L1', array( '32' ), array( 1 ) );
		$this->make_sponsor( 's32-L3', array( '32' ), array( 3 ) );
		$this->make_sponsor( 's31-L4', array( '31' ), array( 4 ) );
		$this->make_sponsor( 's30-L1', array( '30' ), array( 1 ) );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );

		$this->assertSame( array( 's32-L3', 's32-L1', 's31-L4', 's31-L2', 's30-L4', 's30-L1' ), $this->main_titles() );
	}

	public function test_multi_season_sponsor_appears_once_in_its_newest_season(): void {
		$this->make_sponsor( 'multi', array( '30', '32' ), array( 1 ) );
		$this->make_sponsor( 's31-L4', array( '31' ), array( 4 ) );
		$this->make_sponsor( 's32-L4', array( '32' ), array( 4 ) );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );

		$this->assertSame( array( 's32-L4', 'multi', 's31-L4' ), $this->main_titles() );
	}

	public function test_multi_tier_sponsor_uses_its_highest_tier(): void {
		$this->make_sponsor( 'multi', array( '31' ), array( 1, 4 ) );
		$this->make_sponsor( 'l3', array( '31' ), array( 3 ) );
		$this->make_sponsor( 'l2', array( '31' ), array( 2 ) );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );

		$this->assertSame( array( 'multi', 'l3', 'l2' ), $this->main_titles() );
	}

	public function test_missing_season_and_missing_tier_sort_last(): void {
		$this->make_sponsor( 'no-season', array(), array( 4 ) );
		$this->make_sponsor( 'unnumbered-season', array( 'Alumni' ), array( 4 ) );
		$this->make_sponsor( 's31-no-tier', array( '31' ), array() );
		$this->make_sponsor( 's31-L1', array( '31' ), array( 1 ) );
		$this->make_sponsor( 'bare', array(), array() );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );

		$this->assertSame( array( 's31-L1', 's31-no-tier', 'unnumbered-season', 'no-season', 'bare' ), $this->main_titles() );
	}

	public function test_same_season_and_tier_fall_back_to_menu_order_then_title(): void {
		$this->make_sponsor( 'b-zero', array( '31' ), array( 2 ), 0 );
		$this->make_sponsor( 'a-zero', array( '31' ), array( 2 ), 0 );
		$this->make_sponsor( 'z-first', array( '31' ), array( 2 ), -5 );
		$this->make_sponsor( 'a-last', array( '31' ), array( 2 ), 7 );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );

		$this->assertSame( array( 'z-first', 'a-zero', 'b-zero', 'a-last' ), $this->main_titles() );
	}

	public function test_season_scoped_archive_orders_by_tier(): void {
		$this->make_sponsor( 'l1', array( '31' ), array( 1 ) );
		$this->make_sponsor( 'l4', array( '31' ), array( 4 ) );
		$this->make_sponsor( 'l3', array( '31' ), array( 3 ) );
		$this->make_sponsor( 'other-season', array( '30' ), array( 4 ) );
		flush_rewrite_rules();

		$this->go_to( home_url( '/seasons/season-31/sponsors/' ) );

		$this->assertTrue( is_post_type_archive( 'hyper_sponsor' ) );
		$this->assertSame( array( 'l4', 'l3', 'l1' ), $this->main_titles() );
	}

	public function test_season_scoped_archive_ignores_the_season_of_other_seasons(): void {
		$this->make_sponsor( 'both-seasons-l1', array( '31', '32' ), array( 1 ) );
		$this->make_sponsor( 'only-31-l4', array( '31' ), array( 4 ) );
		$this->make_sponsor( 'only-31-no-tier', array( '31' ), array() );
		flush_rewrite_rules();

		$this->go_to( home_url( '/seasons/season-31/sponsors/' ) );
		$this->assertSame( array( 'only-31-l4', 'both-seasons-l1', 'only-31-no-tier' ), $this->main_titles() );

		// The unscoped archive still ranks the multi-season sponsor by its newest season.
		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$this->assertSame( array( 'both-seasons-l1', 'only-31-l4', 'only-31-no-tier' ), $this->main_titles() );
	}

	public function test_equal_sponsors_fall_back_to_id(): void {
		$first  = $this->make_sponsor( 'same', array( '31' ), array( 2 ), 0 );
		$second = $this->make_sponsor( 'same', array( '31' ), array( 2 ), 0 );
		$third  = $this->make_sponsor( 'same', array( '31' ), array( 2 ), 0 );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );

		$this->assertSame( array( $first, $second, $third ), wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
	}

	public function test_pagination_slices_the_full_order(): void {
		$this->make_sponsor( 's30-L1', array( '30' ), array( 1 ) );
		$this->make_sponsor( 's32-L1', array( '32' ), array( 1 ) );
		$this->make_sponsor( 's31-L4', array( '31' ), array( 4 ) );
		$this->make_sponsor( 's32-L4', array( '32' ), array( 4 ) );
		$this->make_sponsor( 's31-L1', array( '31' ), array( 1 ) );
		$expected = array( 's32-L4', 's32-L1', 's31-L4', 's31-L1', 's30-L1' );

		update_option( 'posts_per_page', 2 );
		try {
			$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
			$this->assertSame( array_slice( $expected, 0, 2 ), $this->main_titles() );
			$this->assertSame( 5, $GLOBALS['wp_query']->found_posts );

			$this->go_to( home_url( '/?post_type=hyper_sponsor&paged=2' ) );
			$this->assertSame( array_slice( $expected, 2, 2 ), $this->main_titles() );
			$this->assertSame( 5, $GLOBALS['wp_query']->found_posts );

			$this->go_to( home_url( '/?post_type=hyper_sponsor&paged=3' ) );
			$this->assertSame( array_slice( $expected, 4, 2 ), $this->main_titles() );
		} finally {
			delete_option( 'posts_per_page' );
		}
	}

	public function test_admin_main_query_is_not_reordered(): void {
		$this->make_sponsor( 's32-L4', array( '32' ), array( 4 ) );
		$this->make_sponsor( 's30-L1', array( '30' ), array( 1 ) );

		// go_to() resets the screen, so enter the admin after it and re-run the main query.
		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		set_current_screen( 'edit-post' );
		try {
			$this->assertTrue( is_admin() );
			$GLOBALS['wp_the_query']->query( array( 'post_type' => 'hyper_sponsor' ) );
			$this->assertTrue( $GLOBALS['wp_the_query']->is_main_query() );
			$this->assertTrue( $GLOBALS['wp_the_query']->is_post_type_archive( 'hyper_sponsor' ) );
			$titles = $this->main_titles();
		} finally {
			set_current_screen( 'front' );
		}

		// Default order is newest post first (the season/tier order would be s32-L4, s30-L1).
		$this->assertSame( array( 's30-L1', 's32-L4' ), $titles );
		$this->assertFalse( is_admin() );
	}

	public function test_feed_keeps_the_default_chronological_order(): void {
		$this->make_sponsor( 's32-L4', array( '32' ), array( 4 ) );
		$this->make_sponsor( 's30-L1', array( '30' ), array( 1 ) );
		$this->make_sponsor( 's31-L2', array( '31' ), array( 2 ) );
		flush_rewrite_rules();

		$this->go_to( home_url( '/sponsors/feed/' ) );

		$this->assertTrue( is_feed() );
		$this->assertTrue( is_post_type_archive( 'hyper_sponsor' ) );
		// Newest post first, not season/tier order.
		$this->assertSame( array( 's31-L2', 's30-L1', 's32-L4' ), $this->main_titles() );
	}

	public function test_tiers_rank_by_trailing_number_then_unnumbered_by_slug(): void {
		$this->make_sponsor( 'none', array( '31' ), array() );
		$this->make_sponsor( 'alpha', array( '31' ), array( 'alpha' ) );
		$this->make_sponsor( 'l2', array( '31' ), array( 'Level 2' ) );
		$this->make_sponsor( 'zeta', array( '31' ), array( 'zeta' ) );
		$this->make_sponsor( 'l4', array( '31' ), array( 4 ) );
		$this->make_sponsor( 'l10', array( '31' ), array( 'Level 10' ) );
		$this->make_sponsor( 'multi', array( '31' ), array( 'alpha', 'Level 4', 'zeta' ) );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );

		// "multi" has level-4 as its highest-ranking tier: it sorts with l4 (title ASC: l4, multi).
		$this->assertSame( array( 'l10', 'l4', 'multi', 'l2', 'zeta', 'alpha', 'none' ), $this->main_titles() );
	}

	public function test_explicit_orderby_is_respected(): void {
		$this->make_sponsor( 'b', array( '32' ), array( 4 ) );
		$this->make_sponsor( 'c', array( '31' ), array( 1 ) );
		$this->make_sponsor( 'a', array( '30' ), array( 2 ) );

		$set_orderby = static function ( WP_Query $query ): void {
			if ( $query->is_main_query() ) {
				$query->set( 'orderby', 'title' );
				$query->set( 'order', 'ASC' );
			}
		};
		add_action( 'pre_get_posts', $set_orderby );
		try {
			$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		} finally {
			remove_action( 'pre_get_posts', $set_orderby );
		}

		$this->assertSame( array( 'a', 'b', 'c' ), $this->main_titles() );
	}

	public function test_secondary_query_keeps_the_default_order(): void {
		$this->make_sponsor( 's30-L1', array( '30' ), array( 1 ) );
		$this->make_sponsor( 's32-L4', array( '32' ), array( 4 ) );
		$this->make_sponsor( 's31-L2', array( '31' ), array( 2 ) );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$this->assertSame( array( 's32-L4', 's31-L2', 's30-L1' ), $this->main_titles() );

		$query = new WP_Query( array( 'post_type' => 'hyper_sponsor' ) );

		// Default order is newest post first.
		$this->assertSame( array( 's31-L2', 's32-L4', 's30-L1' ), array_map( 'get_the_title', wp_list_pluck( $query->posts, 'ID' ) ) );
	}

	public function test_season_compare_ranks_by_trailing_number(): void {
		$this->assertLessThan( 0, Ordering::season_compare( 'Season 31', 'Season 4' ) );
		$this->assertGreaterThan( 0, Ordering::season_compare( 'Season 4', 'Season 31' ) );
		$this->assertLessThan( 0, Ordering::season_compare( 'Season 1', 'Alpha' ) );
		$this->assertGreaterThan( 0, Ordering::season_compare( 'Alpha', 'Season 1' ) );
		$this->assertLessThan( 0, Ordering::season_compare( '2024', 'Season 5' ) );
		$this->assertSame( 0, Ordering::season_compare( 'Season 5', 'Season 5' ) );

		// Equal numbers: by name, descending (like tier_compare() by slug).
		$this->assertLessThan( 0, Ordering::season_compare( 'Beta 3', 'Alpha 3' ) );
		$this->assertGreaterThan( 0, Ordering::season_compare( 'Alpha 3', 'Beta 3' ) );
		// Unnumbered seasons also fall back to the name, after all numbered ones.
		$this->assertLessThan( 0, Ordering::season_compare( 'Zeta', 'Alpha' ) );

		// Accepts terms and plain names, mixed.
		$term_31 = get_term( $this->term_id( 'hyper_season', 'Season 31', 'season-31' ), 'hyper_season' );
		$term_4  = get_term( $this->term_id( 'hyper_season', 'Season 4', 'season-4' ), 'hyper_season' );
		$this->assertLessThan( 0, Ordering::season_compare( $term_31, $term_4 ) );
		$this->assertGreaterThan( 0, Ordering::season_compare( 'Season 4', $term_31 ) );

		$names = array( 'Alpha', 'Season 4', 'Season 31', 'Season 5' );
		usort( $names, array( Ordering::class, 'season_compare' ) );
		$this->assertSame( array( 'Season 31', 'Season 5', 'Season 4', 'Alpha' ), $names );
	}

	public function test_opt_in_secondary_query_is_ordered(): void {
		$this->make_sponsor( 's30-L1', array( '30' ), array( 1 ) );
		$this->make_sponsor( 's32-L1', array( '32' ), array( 1 ) );
		$this->make_sponsor( 's31-L4', array( '31' ), array( 4 ) );
		$this->make_sponsor( 'multi', array( '30', '32' ), array( 4 ) );
		$this->make_sponsor( 'bare', array(), array() );

		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$main_ids = wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' );

		$query = new WP_Query(
			array(
				'post_type'         => 'hyper_sponsor',
				Ordering::ORDER_VAR => true,
				'fields'            => 'ids',
				'posts_per_page'    => -1,
			)
		);

		$this->assertSame( array( 'multi', 's32-L1', 's31-L4', 's30-L1', 'bare' ), array_map( 'get_the_title', $main_ids ) );
		$this->assertSame( $main_ids, $query->posts );

		// Without the opt-in, or for another post type, the default order stays.
		$plain = new WP_Query( array( 'post_type' => 'hyper_sponsor', 'fields' => 'ids', 'posts_per_page' => -1 ) );
		$this->assertNotSame( $main_ids, $plain->posts );
		$this->assertSame( array(), ( new WP_Query( array( 'post_type' => 'post', Ordering::ORDER_VAR => true, 'fields' => 'ids' ) ) )->posts );
	}

	public function test_opt_in_respects_an_explicit_orderby(): void {
		$this->make_sponsor( 'b', array( '32' ), array( 4 ) );
		$this->make_sponsor( 'a', array( '30' ), array( 2 ) );

		$query = new WP_Query(
			array(
				'post_type'         => 'hyper_sponsor',
				Ordering::ORDER_VAR => true,
				'orderby'           => 'title',
				'order'             => 'ASC',
			)
		);

		$this->assertSame( array( 'a', 'b' ), array_map( 'get_the_title', wp_list_pluck( $query->posts, 'ID' ) ) );
	}

	public function test_other_post_type_archive_is_untouched(): void {
		register_post_type( 'zz_other', array( 'public' => true, 'has_archive' => true ) );
		foreach ( array( 'b', 'a', 'c' ) as $i => $title ) {
			self::factory()->post->create(
				array(
					'post_type'  => 'zz_other',
					'post_title' => $title,
					'post_date'  => gmdate( 'Y-m-d H:i:s', 1700000000 + $i * 60 ),
				)
			);
		}

		try {
			$this->go_to( home_url( '/?post_type=zz_other' ) );
			$this->assertTrue( is_post_type_archive( 'zz_other' ) );
			$this->assertSame( array( 'c', 'a', 'b' ), $this->main_titles() );
		} finally {
			unregister_post_type( 'zz_other' );
		}
	}
}
