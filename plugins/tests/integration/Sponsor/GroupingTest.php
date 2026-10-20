<?php
namespace HyperPress\Tests\Integration\Sponsor;

use HyperPress\Sponsor\Grouping;
use HyperPress\Sponsor\Ordering;
use WP_Term;
use WP_UnitTestCase;

final class GroupingTest extends WP_UnitTestCase {

	/** Counter that gives every created post a later date, so the default (date DESC) order is the reverse of creation. */
	private int $clock = 0;

	public function set_up(): void {
		parent::set_up();
		$this->clock = 0;
	}

	private function term( string $taxonomy, string $name, string $slug ): WP_Term {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term ) {
			$ids = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
			$this->assertIsArray( $ids );
			$term = get_term( (int) $ids['term_id'], $taxonomy );
		}
		$this->assertInstanceOf( WP_Term::class, $term );

		return $term;
	}

	private function season( string $number ): WP_Term {
		$name = ctype_digit( $number ) ? 'Season ' . $number : $number;

		return $this->term( 'hyper_season', $name, sanitize_title( $name ) );
	}

	/** @param int|string $tier Level number (becomes "Level N") or a free tier name. */
	private function tier( $tier ): WP_Term {
		$name = is_int( $tier ) ? 'Level ' . $tier : (string) $tier;

		return $this->term( 'hyper_tier', $name, sanitize_title( $name ) );
	}

	/**
	 * @param string[] $seasons Season numbers or free names.
	 * @param array    $tiers   Tier levels (ints) or free tier names.
	 */
	private function make_sponsor( string $title, array $seasons = array(), array $tiers = array() ): int {
		++$this->clock;
		$id = self::factory()->post->create(
			array(
				'post_type'  => 'hyper_sponsor',
				'post_title' => $title,
				'post_date'  => gmdate( 'Y-m-d H:i:s', 1700000000 + $this->clock * 60 ),
			)
		);
		wp_set_object_terms( $id, array_map( fn( $s ) => $this->season( $s )->term_id, $seasons ), 'hyper_season' );
		wp_set_object_terms( $id, array_map( fn( $t ) => $this->tier( $t )->term_id, $tiers ), 'hyper_tier' );

		return $id;
	}

	/** @return array{season:?WP_Term,tier:?WP_Term} */
	private function keys_of( ?WP_Term $season, ?WP_Term $tier ): array {
		return array(
			'season' => $season,
			'tier'   => $tier,
		);
	}

	/** Compare two nullable terms with a comparer; a missing term ranks after any term. */
	private function compare_nullable( ?WP_Term $a, ?WP_Term $b, callable $comparer ): int {
		if ( null === $a || null === $b ) {
			return ( null === $a ) <=> ( null === $b );
		}

		return $comparer( $a, $b );
	}

	public function test_keys_pick_newest_season_and_highest_tier(): void {
		$id   = $this->make_sponsor( 'multi', array( '30', '32' ), array( 2, 10 ) );
		$keys = Grouping::keys( $id );

		$this->assertSame( 'Season 32', $keys['season']->name );
		$this->assertSame( 'level-10', $keys['tier']->slug );

		$bare = Grouping::keys( $this->make_sponsor( 'bare' ) );
		$this->assertNull( $bare['season'] );
		$this->assertNull( $bare['tier'] );
	}

	public function test_headings_on_group_changes(): void {
		$s31 = $this->season( '31' );
		$s32 = $this->season( '32' );
		$l3  = $this->tier( 3 );
		$l2  = $this->tier( 2 );

		$cases = array(
			'first sponsor'      => array( $this->keys_of( $s32, $l3 ), null, false, array( array( 'h2', 'Season 32' ), array( 'h3', 'Level 3' ) ) ),
			'same group'         => array( $this->keys_of( $s32, $l3 ), $this->keys_of( $s32, $l3 ), false, array() ),
			'new tier'           => array( $this->keys_of( $s32, $l2 ), $this->keys_of( $s32, $l3 ), false, array( array( 'h3', 'Level 2' ) ) ),
			'new season'         => array( $this->keys_of( $s31, $l3 ), $this->keys_of( $s32, $l3 ), false, array( array( 'h2', 'Season 31' ), array( 'h3', 'Level 3' ) ) ),
			'scoped first'       => array( $this->keys_of( $s32, $l3 ), null, true, array( array( 'h3', 'Level 3' ) ) ),
			'scoped new tier'    => array( $this->keys_of( $s32, $l2 ), $this->keys_of( $s32, $l3 ), true, array( array( 'h3', 'Level 2' ) ) ),
			'scoped same group'  => array( $this->keys_of( $s32, $l3 ), $this->keys_of( $s32, $l3 ), true, array() ),
			'scoped season move' => array( $this->keys_of( $s31, $l3 ), $this->keys_of( $s32, $l3 ), true, array() ),
			'scoped season move, new tier' => array( $this->keys_of( $s31, $l2 ), $this->keys_of( $s32, $l3 ), true, array( array( 'h3', 'Level 2' ) ) ),
		);

		foreach ( $cases as $label => $case ) {
			list( $current, $previous, $scoped, $expected ) = $case;
			$headings = Grouping::headings( $current, $previous, $scoped, false );
			$this->assertSame(
				$expected,
				array_map( static fn( array $h ): array => array( $h['tag'], $h['text'] ), $headings ),
				$label
			);
		}
	}

	public function test_page_start_repeats_unchanged_headings_with_continued(): void {
		$s31 = $this->season( '31' );
		$s32 = $this->season( '32' );
		$l3  = $this->tier( 3 );
		$l2  = $this->tier( 2 );

		$pairs = static fn( array $headings ): array => array_map( static fn( array $h ): array => array( $h['tag'], $h['text'] ), $headings );

		$this->assertSame(
			array( array( 'h2', 'Season 31 (continued)' ), array( 'h3', 'Level 3 (continued)' ) ),
			$pairs( Grouping::headings( $this->keys_of( $s31, $l3 ), $this->keys_of( $s31, $l3 ), false, true ) )
		);
		$this->assertSame(
			array( array( 'h2', 'Season 31 (continued)' ), array( 'h3', 'Level 2' ) ),
			$pairs( Grouping::headings( $this->keys_of( $s31, $l2 ), $this->keys_of( $s31, $l3 ), false, true ) )
		);
		$this->assertSame(
			array( array( 'h2', 'Season 31' ), array( 'h3', 'Level 3' ) ),
			$pairs( Grouping::headings( $this->keys_of( $s31, $l3 ), $this->keys_of( $s32, $l3 ), false, true ) )
		);
		// Very first sponsor of page 1: nothing is a continuation.
		$this->assertSame(
			array( array( 'h2', 'Season 31' ), array( 'h3', 'Level 3' ) ),
			$pairs( Grouping::headings( $this->keys_of( $s31, $l3 ), null, false, true ) )
		);
		// Scoped archive: tier only, no season heading even when continued.
		$this->assertSame(
			array( array( 'h3', 'Level 3 (continued)' ) ),
			$pairs( Grouping::headings( $this->keys_of( $s31, $l3 ), $this->keys_of( $s31, $l3 ), true, true ) )
		);
	}

	public function test_scoped_season_move_is_ignored_for_continued_decisions(): void {
		$s31 = $this->season( '31' );
		$s32 = $this->season( '32' );
		$l3  = $this->tier( 3 );
		$l2  = $this->tier( 2 );

		$pairs = static fn( array $headings ): array => array_map( static fn( array $h ): array => array( $h['tag'], $h['text'] ), $headings );

		$this->assertSame(
			array( array( 'h3', 'Level 3 (continued)' ) ),
			$pairs( Grouping::headings( $this->keys_of( $s31, $l3 ), $this->keys_of( $s32, $l3 ), true, true ) )
		);
		$this->assertSame(
			array( array( 'h3', 'Level 2' ) ),
			$pairs( Grouping::headings( $this->keys_of( $s31, $l2 ), $this->keys_of( $s32, $l3 ), true, true ) )
		);
		// A sponsor without tier in a scoped archive, first on its page.
		$this->assertSame(
			array( array( 'h3', 'Other sponsors (continued)' ) ),
			$pairs( Grouping::headings( $this->keys_of( $s31, null ), $this->keys_of( $s31, null ), true, true ) )
		);
	}

	public function test_headings_when_seasons_are_missing(): void {
		$s31 = $this->season( '31' );
		$l3  = $this->tier( 3 );
		$l2  = $this->tier( 2 );

		$pairs = static fn( array $headings ): array => array_map( static fn( array $h ): array => array( $h['tag'], $h['text'] ), $headings );

		// Both without a season and in the same tier: nothing, or two "continued" headings at a page start.
		$this->assertSame( array(), $pairs( Grouping::headings( $this->keys_of( null, $l3 ), $this->keys_of( null, $l3 ), false, false ) ) );
		$this->assertSame(
			array( array( 'h2', 'Other sponsors (continued)' ), array( 'h3', 'Level 3 (continued)' ) ),
			$pairs( Grouping::headings( $this->keys_of( null, $l3 ), $this->keys_of( null, $l3 ), false, true ) )
		);
		// Both without season, new tier: tier only (the season heading stays unchanged).
		$this->assertSame(
			array( array( 'h3', 'Level 2' ) ),
			$pairs( Grouping::headings( $this->keys_of( null, $l2 ), $this->keys_of( null, $l3 ), false, false ) )
		);
		// Both bare at a page start.
		$this->assertSame(
			array( array( 'h2', 'Other sponsors (continued)' ), array( 'h3', 'Other sponsors (continued)' ) ),
			$pairs( Grouping::headings( $this->keys_of( null, null ), $this->keys_of( null, null ), false, true ) )
		);
		// Previous without a season, current with one, and the reverse: the season level changes both ways.
		$this->assertSame(
			array( array( 'h2', 'Season 31' ), array( 'h3', 'Level 3' ) ),
			$pairs( Grouping::headings( $this->keys_of( $s31, $l3 ), $this->keys_of( null, $l3 ), false, false ) )
		);
		$this->assertSame(
			array( array( 'h2', 'Other sponsors' ), array( 'h3', 'Level 3' ) ),
			$pairs( Grouping::headings( $this->keys_of( null, $l3 ), $this->keys_of( $s31, $l3 ), false, false ) )
		);
	}

	public function test_missing_season_and_tier_use_other_sponsors(): void {
		$s31 = $this->season( '31' );

		$headings = Grouping::headings( $this->keys_of( null, null ), null, false, false );
		$this->assertSame(
			array(
				array(
					'tag'  => 'h2',
					'text' => 'Other sponsors',
				),
				array(
					'tag'  => 'h3',
					'text' => 'Other sponsors',
				),
			),
			$headings
		);

		$headings = Grouping::headings( $this->keys_of( $s31, null ), $this->keys_of( $s31, $this->tier( 1 ) ), false, false );
		$this->assertSame(
			array(
				array(
					'tag'  => 'h3',
					'text' => 'Other sponsors',
				),
			),
			$headings
		);
	}

	public function test_labels_are_filterable(): void {
		$s31 = $this->season( '31' );
		$l3  = $this->tier( 3 );

		$seen   = array();
		$season = static function ( string $text, ?WP_Term $term ) use ( &$seen ): string {
			$seen['season'] = array( $text, $term ? $term->slug : null );

			return 'Saison';
		};
		$tier   = static function ( string $text, ?WP_Term $term ) use ( &$seen ): string {
			$seen['tier'] = array( $text, $term ? $term->slug : null );

			return 'Stufe';
		};
		add_filter( 'hyperpress_sponsor_archive_season_label', $season, 10, 2 );
		add_filter( 'hyperpress_sponsor_archive_tier_label', $tier, 10, 2 );
		try {
			$headings = Grouping::headings( $this->keys_of( $s31, $l3 ), null, false, false );
			$other    = Grouping::headings( $this->keys_of( null, null ), null, false, false );
		} finally {
			remove_filter( 'hyperpress_sponsor_archive_season_label', $season, 10 );
			remove_filter( 'hyperpress_sponsor_archive_tier_label', $tier, 10 );
		}

		$this->assertSame( array( 'Saison', 'Stufe' ), wp_list_pluck( $headings, 'text' ) );
		$this->assertSame( array( 'Other sponsors', null ), $seen['season'] );
		$this->assertSame( array( 'Other sponsors', null ), $seen['tier'] );
		$this->assertSame( array( 'Saison', 'Stufe' ), wp_list_pluck( $other, 'text' ) );
	}

	public function test_group_keys_follow_the_sql_order(): void {
		$this->make_sponsor( 's30-L1', array( '30' ), array( 1 ) );
		$this->make_sponsor( 's32-L1', array( '32' ), array( 1 ) );
		$this->make_sponsor( 's31-L4', array( '31' ), array( 4 ) );
		$this->make_sponsor( 's31-L10', array( '31' ), array( 'Level 10' ) );
		$this->make_sponsor( 's31-alpha', array( '31' ), array( 'alpha' ) );
		$this->make_sponsor( 's31-no-tier', array( '31' ), array() );
		$this->make_sponsor( 'multi', array( '30', '32' ), array( 1, 4 ) );
		$this->make_sponsor( 'multi-31-32', array( '31', '32' ), array( 10 ) );
		$this->make_sponsor( 'alumni', array( 'Alumni' ), array( 4 ) );
		$this->make_sponsor( 'no-season', array(), array( 4 ) );
		$this->make_sponsor( 'bare' );

		update_option( 'posts_per_page', 50 );
		try {
			$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		} finally {
			delete_option( 'posts_per_page' );
		}
		$ids = wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' );

		$this->assertCount( 11, $ids );
		$this->assertSame( $ids, array_values( array_unique( $ids ) ), 'A multi-season sponsor appears once.' );

		$previous = null;
		foreach ( $ids as $id ) {
			$keys = Grouping::keys( $id );
			if ( null !== $previous ) {
				$title   = get_the_title( $id );
				$season  = $this->compare_nullable( $previous['season'], $keys['season'], array( Ordering::class, 'season_compare' ) );
				$this->assertLessThanOrEqual( 0, $season, "Season key increases before '{$title}'." );
				if ( 0 === $season ) {
					$tier = $this->compare_nullable( $previous['tier'], $keys['tier'], array( Ordering::class, 'tier_compare' ) );
					$this->assertLessThanOrEqual( 0, $tier, "Tier key increases before '{$title}'." );
				}
			}
			$previous = $keys;
		}
	}
}
