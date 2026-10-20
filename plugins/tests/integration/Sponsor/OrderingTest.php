<?php
namespace HyperPress\Tests\Integration\Sponsor;

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
	 * @param int[]    $tiers   Tier levels 1..4.
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
			$tier_ids[] = $this->term_id( 'hyper_tier', 'Level ' . $tier, 'level-' . $tier );
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
