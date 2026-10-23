<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Listing;
use HyperPress\Competition\Schedule;
use WP_UnitTestCase;

final class ListingTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		update_option( 'timezone_string', 'America/New_York' );
	}

	public function tear_down(): void {
		delete_option( 'timezone_string' );
		delete_option( 'hyperpress_season' );
		unregister_taxonomy_for_object_type( 'hyper_program', 'hyper_competition' );
		parent::tear_down();
	}

	private function term( string $taxonomy, string $name ): int {
		$existing = get_term_by( 'name', $name, $taxonomy );
		if ( $existing instanceof \WP_Term ) {
			return $existing->term_id;
		}
		$created = wp_insert_term( $name, $taxonomy );
		$this->assertIsArray( $created );
		return (int) $created['term_id'];
	}

	/**
	 * @param array<string,string>           $terms  Taxonomy => term name.
	 * @param list<string>|string|null       $starts Start times of the days (one row each), or null for no days.
	 */
	private function competition( string $title, array $terms = array(), $starts = null, string $status = 'publish' ): int {
		$id = self::factory()->post->create( array( 'post_type' => 'hyper_competition', 'post_status' => $status, 'post_title' => $title ) );
		if ( null !== $starts ) {
			$rows = array_map( static fn( string $start ): array => array( 'label' => '', 'start' => $start ), (array) $starts );
			update_post_meta( $id, Schedule::FIELD, $rows );
		}
		foreach ( $terms as $taxonomy => $name ) {
			wp_set_object_terms( $id, array( $this->term( $taxonomy, $name ) ), $taxonomy );
		}
		return $id;
	}

	private function now( string $text ): \DateTimeImmutable {
		return new \DateTimeImmutable( $text, wp_timezone() );
	}

	/**
	 * @param list<int> $ids IDs.
	 * @return list<string> The titles.
	 */
	private function titles( array $ids ): array {
		return array_map( static fn( int $id ): string => get_the_title( $id ), $ids );
	}

	public function test_defaults_to_the_current_season(): void {
		$this->competition( 'Old', array( 'hyper_season' => 'Season 31' ) );
		$this->competition( 'New', array( 'hyper_season' => 'Season 32' ) );
		update_option( 'hyperpress_season', array( 'hyperpress_season_current' => get_term_by( 'name', 'Season 32', 'hyper_season' )->slug ) );

		$this->assertSame( array( 'New' ), $this->titles( Listing::ids( array() ) ) );
	}

	public function test_no_current_season_means_no_season_filter(): void {
		$this->competition( 'Old', array( 'hyper_season' => 'Season 31' ) );
		$this->competition( 'New', array( 'hyper_season' => 'Season 32' ) );

		$this->assertEqualsCanonicalizing( array( 'Old', 'New' ), $this->titles( Listing::ids( array() ) ) );
	}

	public function test_season_attribute_overrides_the_default(): void {
		$this->competition( 'Old', array( 'hyper_season' => 'Season 31' ) );
		$this->competition( 'New', array( 'hyper_season' => 'Season 32' ) );
		update_option( 'hyperpress_season', array( 'hyperpress_season_current' => get_term_by( 'name', 'Season 32', 'hyper_season' )->slug ) );

		$this->assertSame( array( 'Old' ), $this->titles( Listing::ids( array( 'season' => 'season-31' ) ) ) );
	}

	public function test_filters_match_slug_or_name_and_combine(): void {
		$this->competition( 'NE District', array( 'hyper_district' => 'NE FIRST', 'hyper_competition_type' => 'District' ) );
		$this->competition( 'NE Champs', array( 'hyper_district' => 'NE FIRST', 'hyper_competition_type' => 'District Championship' ) );
		$this->competition( 'MA District', array( 'hyper_district' => 'FTC MA', 'hyper_competition_type' => 'District' ) );

		$this->assertEqualsCanonicalizing( array( 'NE District', 'NE Champs' ), $this->titles( Listing::ids( array( 'district' => 'NE FIRST' ) ) ) );
		$this->assertEqualsCanonicalizing( array( 'NE District', 'NE Champs' ), $this->titles( Listing::ids( array( 'district' => 'ne-first' ) ) ) );
		$this->assertSame( array( 'NE District' ), $this->titles( Listing::ids( array( 'district' => 'ne-first', 'type' => 'District' ) ) ) );
	}

	public function test_unknown_term_returns_nothing(): void {
		$this->competition( 'Any', array( 'hyper_season' => 'Season 32', 'hyper_district' => 'NE FIRST', 'hyper_competition_type' => 'District' ) );
		register_taxonomy_for_object_type( 'hyper_program', 'hyper_competition' );

		foreach ( array( 'season', 'district', 'type', 'program' ) as $attribute ) {
			$this->assertSame( array(), Listing::ids( array( $attribute => 'no-such-term' ) ), $attribute );
		}
	}

	public function test_program_is_ignored_when_not_attached_to_competitions(): void {
		$this->competition( 'Any' );

		$this->assertFalse( is_object_in_taxonomy( 'hyper_competition', 'hyper_program' ) );
		$this->assertSame( array( 'Any' ), $this->titles( Listing::ids( array( 'program' => 'ftc' ) ) ) );
		$this->assertSame( array( 'Any' ), $this->titles( Listing::ids( array( 'program' => 'no-such-term' ) ) ) );
	}

	public function test_program_filters_and_includes_children_when_attached(): void {
		register_taxonomy_for_object_type( 'hyper_program', 'hyper_competition' );
		$first = $this->term( 'hyper_program', 'FIRST' );
		foreach ( array( 'FRC', 'FTC' ) as $name ) {
			wp_update_term( $this->term( 'hyper_program', $name ), 'hyper_program', array( 'parent' => $first ) );
		}
		$this->competition( 'FRC one', array( 'hyper_program' => 'FRC' ) );
		$this->competition( 'FTC one', array( 'hyper_program' => 'FTC' ) );
		$this->competition( 'No program' );

		$this->assertSame( array( 'FRC one' ), $this->titles( Listing::ids( array( 'program' => 'FRC' ) ) ) );
		$this->assertEqualsCanonicalizing( array( 'FRC one', 'FTC one' ), $this->titles( Listing::ids( array( 'program' => 'first' ) ) ) );
	}

	public function test_only_published_competitions(): void {
		$this->competition( 'Live' );
		$this->competition( 'Draft', array(), null, 'draft' );
		$this->competition( 'Private', array(), null, 'private' );

		$this->assertSame( array( 'Live' ), $this->titles( Listing::ids( array() ) ) );
	}

	public function test_password_protected_competitions_are_not_listed(): void {
		$this->competition( 'Open' );
		self::factory()->post->create( array( 'post_type' => 'hyper_competition', 'post_status' => 'publish', 'post_title' => 'Locked', 'post_password' => 'secret' ) );

		$this->assertSame( array( 'Open' ), $this->titles( Listing::ids( array() ) ) );
	}

	public function test_upcoming_first_by_next_start_then_past_ascending(): void {
		$this->competition( 'Past Late', array(), '2030-05-01 09:00' );
		$this->competition( 'Later', array(), '2030-09-01 09:00' );
		$this->competition( 'Past Early', array(), '2030-03-01 09:00' );
		$this->competition( 'Soon', array(), '2030-07-10 09:00' );

		$this->assertSame(
			array( 'Soon', 'Later', 'Past Early', 'Past Late' ),
			$this->titles( Listing::ids( array(), $this->now( '2030-07-01 12:00' ) ) )
		);
	}

	public function test_all_past_are_ascending_by_date(): void {
		$this->competition( 'B', array(), '2030-05-01 09:00' );
		$this->competition( 'C', array(), '2030-09-01 09:00' );
		$this->competition( 'A', array(), '2030-03-01 09:00' );

		$this->assertSame( array( 'A', 'B', 'C' ), $this->titles( Listing::ids( array(), $this->now( '2031-01-01 00:00' ) ) ) );
	}

	public function test_a_running_competition_ranks_by_its_next_unfinished_day(): void {
		$this->competition( 'Running', array(), array( '2030-06-25 09:00', '2030-07-05 09:00' ) );
		$this->competition( 'Single', array(), '2030-07-03 09:00' );

		$this->assertSame( array( 'Single', 'Running' ), $this->titles( Listing::ids( array(), $this->now( '2030-07-01 12:00' ) ) ) );
	}

	public function test_ties_break_by_title(): void {
		$this->competition( 'B', array(), '2030-07-10 09:00' );
		$this->competition( 'A', array(), '2030-07-10 09:00' );

		$this->assertSame( array( 'A', 'B' ), $this->titles( Listing::ids( array(), $this->now( '2030-07-01 12:00' ) ) ) );
	}

	public function test_competitions_without_days_come_last_by_title(): void {
		$this->competition( 'Z undated' );
		$this->competition( 'A undated' );
		$this->competition( 'Past', array(), '2030-03-01 09:00' );
		$this->competition( 'Soon', array(), '2030-07-10 09:00' );

		$this->assertSame(
			array( 'Soon', 'Past', 'A undated', 'Z undated' ),
			$this->titles( Listing::ids( array(), $this->now( '2030-07-01 12:00' ) ) )
		);
	}
}
