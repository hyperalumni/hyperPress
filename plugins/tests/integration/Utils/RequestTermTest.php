<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\RequestTerm;
use WP_UnitTestCase;

final class RequestTermTest extends WP_UnitTestCase {

	/** @var string[] Taxonomies this test registered, so tear_down removes only those. */
	private array $registered = array();

	public function set_up(): void {
		parent::set_up();
		if ( ! taxonomy_exists( 'hyper_season' ) ) {
			register_taxonomy( 'hyper_season', array( 'post' ), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
			$this->registered[] = 'hyper_season';
		}
		if ( ! taxonomy_exists( 'hyper_competition_type' ) ) {
			register_taxonomy( 'hyper_competition_type', array( 'post' ), array( 'public' => true, 'hierarchical' => true, 'rewrite' => array( 'slug' => 'competition-types' ) ) );
			$this->registered[] = 'hyper_competition_type';
		}
		flush_rewrite_rules();
	}

	public function tear_down(): void {
		foreach ( $this->registered as $taxonomy ) {
			unregister_taxonomy( $taxonomy );
		}
		$this->registered = array();
		parent::tear_down();
	}

	/**
	 * Create two seasons and a parent/child competition type. The slugs are prefixed "rt-" because the plugins seed
	 * real seasons and types (season-31, qualifying, district, ...) that the factory would collide with.
	 */
	private function make_terms( bool $season_first = true ): void {
		$season = static function (): void {
			self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => 'RT Season A', 'slug' => 'rt-season-a' ) );
			self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => 'RT Season B', 'slug' => 'rt-season-b' ) );
		};
		$type   = static function (): void {
			$parent = self::factory()->term->create( array( 'taxonomy' => 'hyper_competition_type', 'name' => 'RT Qualifying', 'slug' => 'rt-qualifying' ) );
			self::factory()->term->create( array( 'taxonomy' => 'hyper_competition_type', 'name' => 'RT District', 'slug' => 'rt-district', 'parent' => $parent ) );
		};
		if ( $season_first ) {
			$season();
			$type();
		} else {
			$type();
			$season();
		}
	}

	/** Point the request at a raw season query var value, as core would see it. */
	private function season_request( $value ): void {
		$this->go_to( '/' );
		set_query_var( 'hyper_season', $value );
	}

	public function test_returns_term_for_query_var(): void {
		$this->make_terms();
		$this->go_to( '/?hyper_season=rt-season-a' );
		$this->assertSame( 'RT Season A', RequestTerm::get( 'hyper_season' )->name );
	}

	public function test_each_taxonomy_gets_its_own_term(): void {
		foreach ( array( true, false ) as $season_first ) {
			$this->make_terms( $season_first );
			$this->go_to( '/?hyper_season=rt-season-a&hyper_competition_type=rt-district' );
			$this->assertSame( 'rt-season-a', RequestTerm::get( 'hyper_season' )->slug );
			$this->assertSame( 'rt-district', RequestTerm::get( 'hyper_competition_type' )->slug );
			foreach ( get_terms( array( 'taxonomy' => array( 'hyper_season', 'hyper_competition_type' ), 'hide_empty' => false, 'slug' => array( 'rt-season-a', 'rt-season-b', 'rt-qualifying', 'rt-district' ), 'fields' => 'ids' ) ) as $id ) {
				wp_delete_term( $id, get_term( $id )->taxonomy );
			}
		}
	}

	public function test_hierarchical_path_resolves_to_last_segment(): void {
		$this->make_terms();
		$this->go_to( '/?hyper_competition_type=rt-qualifying/rt-district' );
		$this->assertSame( 'RT District', RequestTerm::get( 'hyper_competition_type' )->name );
	}

	public function test_unknown_slug_returns_null(): void {
		$this->make_terms();
		$this->go_to( '/?hyper_competition_type=does-not-exist' );
		$this->assertNull( RequestTerm::get( 'hyper_competition_type' ) );
	}

	public function test_empty_query_var_returns_null(): void {
		$this->make_terms();
		$this->go_to( '/' );
		$this->assertNull( RequestTerm::get( 'hyper_season' ) );
	}

	public function test_unregistered_taxonomy_returns_null(): void {
		$this->go_to( '/?hyper_nothing=whatever' );
		$this->assertNull( RequestTerm::get( 'hyper_nothing' ) );
	}

	public function test_comma_list_returns_first_term_in_given_order(): void {
		$this->make_terms();
		$this->season_request( 'rt-season-a,rt-season-b' );
		$this->assertSame( 'rt-season-a', RequestTerm::get( 'hyper_season' )->slug );
		$this->season_request( 'rt-season-b,rt-season-a' );
		$this->assertSame( 'rt-season-b', RequestTerm::get( 'hyper_season' )->slug );
	}

	public function test_plus_and_form_returns_first_term(): void {
		$this->make_terms();
		// Core splits on a literal plus; a "+" typed in a URL arrives as a space, so only %2B reaches it as "+".
		$this->go_to( '/?hyper_season=rt-season-b%2Brt-season-a' );
		$this->assertSame( 'rt-season-b', RequestTerm::get( 'hyper_season' )->slug );
		$this->season_request( 'rt-season-a+rt-season-b' );
		$this->assertSame( 'rt-season-a', RequestTerm::get( 'hyper_season' )->slug );
	}

	public function test_first_unknown_slug_falls_through_to_next_known_one(): void {
		$this->make_terms();
		$this->season_request( 'nope,rt-season-b' );
		$this->assertSame( 'rt-season-b', RequestTerm::get( 'hyper_season' )->slug );
		$this->season_request( 'nope+rt-season-a' );
		$this->assertSame( 'rt-season-a', RequestTerm::get( 'hyper_season' )->slug );
	}

	public function test_all_unknown_slugs_return_null(): void {
		$this->make_terms();
		$this->season_request( 'nope,never' );
		$this->assertNull( RequestTerm::get( 'hyper_season' ) );
	}

	public function test_hierarchical_pieces_in_a_list_use_their_last_segment(): void {
		$this->make_terms();
		$this->go_to( '/?hyper_competition_type=rt-qualifying/rt-district,other' );
		$this->assertSame( 'rt-district', RequestTerm::get( 'hyper_competition_type' )->slug );
		$this->go_to( '/?hyper_competition_type=other,rt-qualifying/rt-district' );
		$this->assertSame( 'rt-district', RequestTerm::get( 'hyper_competition_type' )->slug );
	}

	public function test_whitespace_and_empty_pieces_are_ignored(): void {
		$this->make_terms();
		$this->season_request( ',rt-season-a,,' );
		$this->assertSame( 'rt-season-a', RequestTerm::get( 'hyper_season' )->slug );
		$this->season_request( ' , rt-season-b ' );
		$this->assertSame( 'rt-season-b', RequestTerm::get( 'hyper_season' )->slug );
		$this->season_request( ' , ,+' );
		$this->assertNull( RequestTerm::get( 'hyper_season' ) );
	}

	public function test_array_valued_query_var_returns_null(): void {
		$this->make_terms();
		$this->season_request( array( 'rt-season-a', 'rt-season-b' ) );
		$this->assertNull( RequestTerm::get( 'hyper_season' ) );
	}

	public function test_reads_the_taxonomys_own_custom_query_var(): void {
		register_taxonomy( 'hyper_rt_custom', array( 'post' ), array( 'public' => true, 'query_var' => 'rt_custom_qv' ) );
		$this->registered[] = 'hyper_rt_custom';
		self::factory()->term->create( array( 'taxonomy' => 'hyper_rt_custom', 'name' => 'Custom One', 'slug' => 'custom-one' ) );

		$this->go_to( '/' );
		set_query_var( 'hyper_rt_custom', 'custom-one' );
		$this->assertNull( RequestTerm::get( 'hyper_rt_custom' ), 'The taxonomy key is not its query var.' );
		set_query_var( 'rt_custom_qv', 'custom-one' );
		$this->assertSame( 'custom-one', RequestTerm::get( 'hyper_rt_custom' )->slug );
	}

	public function test_taxonomy_without_query_var_returns_null(): void {
		register_taxonomy( 'hyper_rt_novar', array( 'post' ), array( 'public' => true, 'query_var' => false ) );
		$this->registered[] = 'hyper_rt_novar';
		self::factory()->term->create( array( 'taxonomy' => 'hyper_rt_novar', 'name' => 'No Var', 'slug' => 'no-var' ) );

		$this->go_to( '/' );
		set_query_var( 'hyper_rt_novar', 'no-var' );
		$this->assertNull( RequestTerm::get( 'hyper_rt_novar' ) );
	}
}
