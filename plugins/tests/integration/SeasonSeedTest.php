<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Season\Plugin;
use HyperPress\Season\SeasonTerms;
use WP_UnitTestCase;

final class SeasonSeedTest extends WP_UnitTestCase {

	private const OPTION = 'hyperpress_season_terms_seeded';

	public function set_up(): void {
		parent::set_up();
		delete_option( self::OPTION );
	}

	private function season_count(): int {
		return count( get_terms( array( 'taxonomy' => Plugin::TAXONOMY, 'hide_empty' => false ) ) );
	}

	public function test_seed_creates_seasons_with_slugs_and_year_descriptions(): void {
		Plugin::seed_terms();

		$expected = count( SeasonTerms::default_terms( current_datetime() ) );
		$this->assertGreaterThanOrEqual( 32, $expected );
		$this->assertSame( $expected, $this->season_count() );

		$first = get_term_by( 'slug', 'season-1', Plugin::TAXONOMY );
		$this->assertSame( 'Season 1', $first->name );
		$this->assertSame( '1995-1996', $first->description );
		$this->assertSame( 0, $first->parent );

		$tenth = get_term_by( 'slug', 'season-10', Plugin::TAXONOMY );
		$this->assertSame( 'Season 10', $tenth->name );
		$this->assertSame( '2004-2005', $tenth->description );

		$last = get_term_by( 'slug', 'season-32', Plugin::TAXONOMY );
		$this->assertSame( '2026-2027', $last->description );
	}

	public function test_seed_twice_does_not_duplicate(): void {
		Plugin::seed_terms();
		$count = $this->season_count();

		delete_option( self::OPTION );
		Plugin::seed_terms();

		$this->assertSame( $count, $this->season_count() );
	}

	public function test_existing_term_with_the_same_slug_keeps_its_name(): void {
		$existing = wp_insert_term( 'Season One', Plugin::TAXONOMY, array( 'slug' => 'season-1' ) );

		Plugin::seed_terms();

		$term = get_term( $existing['term_id'], Plugin::TAXONOMY );
		$this->assertSame( 'Season One', $term->name );
		$this->assertSame( '1995-1996', $term->description );
		$this->assertSame( 1, count( get_terms( array( 'taxonomy' => Plugin::TAXONOMY, 'hide_empty' => false, 'slug' => 'season-1' ) ) ) );
	}
}
