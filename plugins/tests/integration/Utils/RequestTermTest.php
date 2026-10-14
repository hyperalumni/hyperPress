<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\RequestTerm;
use WP_UnitTestCase;

final class RequestTermTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		if ( ! taxonomy_exists( 'hyper_season' ) ) {
			register_taxonomy( 'hyper_season', array( 'post' ), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
		}
		if ( ! taxonomy_exists( 'hyper_competition_type' ) ) {
			register_taxonomy( 'hyper_competition_type', array( 'post' ), array( 'public' => true, 'hierarchical' => true, 'rewrite' => array( 'slug' => 'competition-types' ) ) );
		}
		flush_rewrite_rules();
	}

	private function make_terms( bool $season_first = true ): void {
		$season = static fn() => self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => 'Season 31', 'slug' => 'season-31' ) );
		$type   = static function (): void {
			$parent = self::factory()->term->create( array( 'taxonomy' => 'hyper_competition_type', 'name' => 'Qualifying', 'slug' => 'qualifying' ) );
			self::factory()->term->create( array( 'taxonomy' => 'hyper_competition_type', 'name' => 'District', 'slug' => 'district', 'parent' => $parent ) );
		};
		if ( $season_first ) {
			$season();
			$type();
		} else {
			$type();
			$season();
		}
	}

	public function test_returns_term_for_query_var(): void {
		$this->make_terms();
		$this->go_to( '/?hyper_season=season-31' );
		$this->assertSame( 'Season 31', RequestTerm::get( 'hyper_season' )->name );
	}

	public function test_each_taxonomy_gets_its_own_term(): void {
		foreach ( array( true, false ) as $season_first ) {
			$this->make_terms( $season_first );
			$this->go_to( '/?hyper_season=season-31&hyper_competition_type=district' );
			$this->assertSame( 'season-31', RequestTerm::get( 'hyper_season' )->slug );
			$this->assertSame( 'district', RequestTerm::get( 'hyper_competition_type' )->slug );
			foreach ( get_terms( array( 'taxonomy' => array( 'hyper_season', 'hyper_competition_type' ), 'hide_empty' => false, 'fields' => 'ids' ) ) as $id ) {
				wp_delete_term( $id, get_term( $id )->taxonomy );
			}
		}
	}

	public function test_hierarchical_path_resolves_to_last_segment(): void {
		$this->make_terms();
		$this->go_to( '/?hyper_competition_type=qualifying/district' );
		$this->assertSame( 'District', RequestTerm::get( 'hyper_competition_type' )->name );
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
}
