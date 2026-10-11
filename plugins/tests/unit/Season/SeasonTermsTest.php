<?php
namespace HyperPress\Tests\Unit\Season;

use DateTimeImmutable;
use HyperPress\Season\SeasonTerms;
use PHPUnit\Framework\TestCase;

final class SeasonTermsTest extends TestCase {

	/**
	 * @return array<string,array{string,int,string}>
	 */
	public static function boundary_dates(): array {
		return array(
			'day before the rollover'  => array( '2026-07-31', 31, '2025-2026' ),
			'rollover day'             => array( '2026-08-01', 32, '2026-2027' ),
			'before the season starts' => array( '2026-08-31', 32, '2026-2027' ),
			'season start'             => array( '2026-09-01', 32, '2026-2027' ),
			'mid season'               => array( '2027-01-15', 32, '2026-2027' ),
			'first of January'         => array( '2027-01-01', 32, '2026-2027' ),
			'next rollover'            => array( '2027-08-01', 33, '2027-2028' ),
			'first season'             => array( '1995-08-01', 1, '1995-1996' ),
		);
	}

	/**
	 * @dataProvider boundary_dates
	 */
	public function test_last_season_follows_the_august_rollover( string $date, int $number, string $years ): void {
		$terms = SeasonTerms::default_terms( new DateTimeImmutable( $date ) );
		$last  = end( $terms );

		$this->assertCount( $number, $terms );
		$this->assertSame( 'Season ' . $number, $last['name'] );
		$this->assertSame( $years, $last['description'] );
	}

	public function test_first_entry_is_season_one(): void {
		$terms = SeasonTerms::default_terms( new DateTimeImmutable( '2026-10-11' ) );

		$this->assertSame( array( 'name' => 'Season 1', 'description' => '1995-1996' ), $terms[0] );
		$this->assertSame( array( 'name' => 'Season 2', 'description' => '1996-1997' ), $terms[1] );
	}

	public function test_every_entry_follows_the_pattern(): void {
		$terms = SeasonTerms::default_terms( new DateTimeImmutable( '2026-10-11' ) );

		foreach ( $terms as $index => $term ) {
			$number = $index + 1;
			$this->assertSame( 'Season ' . $number, $term['name'] );
			$this->assertSame( ( 1994 + $number ) . '-' . ( 1995 + $number ), $term['description'] );
		}
	}
}
