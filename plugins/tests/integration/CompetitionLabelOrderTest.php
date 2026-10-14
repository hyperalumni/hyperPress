<?php
/**
 * Tests for the order of the real labels in the label registry.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Label\Registry;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Competition\Labels
 */
final class CompetitionLabelOrderTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Registry::reset();
	}

	public function tear_down(): void {
		Registry::reset( true );
		parent::tear_down();
	}

	public function test_week_label_comes_after_the_type_label(): void {
		$expected = array(
			'season.posted_during',
			'program.programs',
			'district.district',
			'competition.type',
			'week.week',
		);

		$keys = array_values( array_intersect( array_keys( Registry::instance()->all() ), $expected ) );

		$this->assertSame( $expected, $keys );
	}

	public function test_competition_priorities_are_distinct(): void {
		$all = Registry::instance()->all();

		$this->assertSame( 36, $all['district.district']['priority'] );
		$this->assertSame( 37, $all['competition.type']['priority'] );
		$this->assertSame( 38, $all['week.week']['priority'] );
	}
}
