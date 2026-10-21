<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Schedule;
use WP_UnitTestCase;

final class ScheduleTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		update_option( 'timezone_string', 'America/New_York' );
	}

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_competition_now' );
		parent::tear_down();
	}

	/** @param mixed $rows Value stored in the schedule meta. */
	private function post_with( $rows ): int {
		$id = self::factory()->post->create();
		update_post_meta( $id, Schedule::FIELD, $rows );
		return $id;
	}

	private function instant_at( string $text ): \DateTimeImmutable {
		return new \DateTimeImmutable( $text, wp_timezone() );
	}

	/**
	 * @return list<array{label:string,start:\DateTimeImmutable,countdown:bool}>
	 */
	private function three_days( bool $load_in_counts = true, bool $last_counts = true ): array {
		return array(
			array(
				'label'     => 'Load In',
				'start'     => $this->instant_at( '2030-06-30 16:00' ),
				'countdown' => $load_in_counts,
			),
			array(
				'label'     => 'Day 1',
				'start'     => $this->instant_at( '2030-07-01 09:00' ),
				'countdown' => true,
			),
			array(
				'label'     => 'Day 2',
				'start'     => $this->instant_at( '2030-07-02 09:00' ),
				'countdown' => $last_counts,
			),
		);
	}

	public function test_rows_are_normalised_and_sorted_by_start(): void {
		$id = $this->post_with(
			array(
				array(
					'label' => '  Day 2 ',
					'start' => '2030-07-02 09:00',
				),
				array(
					'label' => "Load In\n",
					'start' => '2030-06-30 16:00',
					'skip'  => '1',
				),
				array(
					'label' => 'Day 1',
					'start' => '2030-07-01 09:00',
					'skip'  => 0,
				),
			)
		);

		$days = Schedule::days( $id );

		$this->assertCount( 3, $days );
		$this->assertSame( array( 'Load In', 'Day 1', 'Day 2' ), array_column( $days, 'label' ) );
		$this->assertSame( array( false, true, true ), array_column( $days, 'countdown' ) );
		$this->assertSame( array( 0, 1, 2 ), array_keys( $days ) );
		$this->assertInstanceOf( \DateTimeImmutable::class, $days[0]['start'] );
		$this->assertSame( '2030-06-30T16:00:00-04:00', $days[0]['start']->format( 'c' ) );
		$this->assertSame( 'America/New_York', $days[0]['start']->getTimezone()->getName() );
	}

	public function test_invalid_or_empty_rows_are_dropped(): void {
		$id = $this->post_with(
			array(
				array(
					'label' => 'Empty',
					'start' => '',
				),
				array( 'label' => 'Missing' ),
				array(
					'label' => 'Junk',
					'start' => 'junk',
				),
				'not a row',
				array(
					'label' => 'Valid',
					'start' => '2030-07-01 09:00',
				),
			)
		);

		$days = Schedule::days( $id );

		$this->assertCount( 1, $days );
		$this->assertSame( 'Valid', $days[0]['label'] );
	}

	/**
	 * @dataProvider unusable_stored_values
	 * @param mixed $stored Stored value.
	 */
	public function test_a_non_array_stored_value_gives_no_days( $stored ): void {
		$this->assertSame( array(), Schedule::days( $this->post_with( $stored ) ) );
	}

	/**
	 * @return array<string,array<int,mixed>>
	 */
	public static function unusable_stored_values(): array {
		return array(
			'string'       => array( 'x' ),
			'empty string' => array( '' ),
			'empty array'  => array( array() ),
		);
	}

	public function test_no_meta_gives_no_days(): void {
		$this->assertSame( array(), Schedule::days( self::factory()->post->create() ) );
	}

	/**
	 * Meta Box omits an unticked checkbox from the stored row, so a missing `skip` key means counted.
	 */
	public function test_a_missing_skip_flag_is_counted_and_ticked_values_skip(): void {
		$flags = array(
			'missing'     => array(),
			'int zero'    => array( 'skip' => 0 ),
			'string zero' => array( 'skip' => '0' ),
			'empty'       => array( 'skip' => '' ),
			'false'       => array( 'skip' => false ),
			'int one'     => array( 'skip' => 1 ),
			'string one'  => array( 'skip' => '1' ),
			'true'        => array( 'skip' => true ),
		);
		$rows  = array();
		foreach ( array_values( $flags ) as $index => $flag ) {
			$rows[] = array_merge(
				array(
					'label' => 'Row ' . $index,
					'start' => '2030-07-01 0' . $index . ':00',
				),
				$flag
			);
		}

		$days = Schedule::days( $this->post_with( $rows ) );

		$this->assertSame(
			array( true, true, true, true, true, false, false, false ),
			array_column( $days, 'countdown' )
		);
	}

	/**
	 * The shape Meta Box really stores: a ticked "skip" box is '1', an unticked one has no key at all.
	 */
	public function test_the_stored_shape_meta_box_produces_counts_unticked_days(): void {
		$id = $this->post_with(
			array(
				array(
					'label' => 'Load In',
					'start' => '2030-06-30 16:00',
					'skip'  => '1',
				),
				array(
					'label' => 'Day 1',
					'start' => '2030-07-01 09:00',
				),
			)
		);

		$days = Schedule::days( $id );

		$this->assertSame( array( false, true ), array_column( $days, 'countdown' ) );
		$this->assertSame( 'Day 1', Schedule::pick( $days, $this->instant_at( '2030-06-29 08:00' ) )['label'] );
	}

	public function test_equal_starts_keep_row_order(): void {
		$days = Schedule::days(
			$this->post_with(
				array(
					array(
						'label' => 'Second row first',
						'start' => '2030-07-01 09:00',
					),
					array(
						'label' => 'Other',
						'start' => '2030-07-01 09:00',
					),
					array(
						'label' => 'Earlier',
						'start' => '2030-06-30 09:00',
					),
				)
			)
		);

		$this->assertSame( array( 'Earlier', 'Second row first', 'Other' ), array_column( $days, 'label' ) );
	}

	public function test_start_keeps_the_correct_instant_across_dst(): void {
		$days = Schedule::days(
			$this->post_with(
				array(
					array( 'start' => '2030-03-10 12:00' ),
					array( 'start' => '2030-03-09 12:00' ),
				)
			)
		);

		$this->assertSame( '2030-03-09T12:00:00-05:00', $days[0]['start']->format( 'c' ) );
		$this->assertSame( '2030-03-10T12:00:00-04:00', $days[1]['start']->format( 'c' ) );
		$this->assertSame( ( new \DateTimeImmutable( '2030-03-09T17:00:00+00:00' ) )->getTimestamp(), $days[0]['start']->getTimestamp() );
		$this->assertSame( ( new \DateTimeImmutable( '2030-03-10T16:00:00+00:00' ) )->getTimestamp(), $days[1]['start']->getTimestamp() );
	}

	public function test_pick_before_the_first_day_gives_the_first_counted_day(): void {
		$day = Schedule::pick( $this->three_days(), $this->instant_at( '2030-06-29 08:00' ) );

		$this->assertSame( 'Load In', $day['label'] );
	}

	public function test_pick_between_days_gives_the_next_counted_day(): void {
		$day = Schedule::pick( $this->three_days(), $this->instant_at( '2030-07-01 12:00' ) );

		$this->assertSame( 'Day 2', $day['label'] );
	}

	public function test_pick_after_the_last_day_gives_the_last_counted_day(): void {
		$day = Schedule::pick( $this->three_days(), $this->instant_at( '2030-07-05 12:00' ) );

		$this->assertSame( 'Day 2', $day['label'] );
	}

	public function test_pick_at_exactly_a_start_selects_that_day(): void {
		$day = Schedule::pick( $this->three_days(), $this->instant_at( '2030-07-01 09:00' ) );

		$this->assertSame( 'Day 1', $day['label'] );
	}

	public function test_pick_skips_days_with_the_countdown_unticked(): void {
		$before = Schedule::pick( $this->three_days( false ), $this->instant_at( '2030-06-29 08:00' ) );
		$after  = Schedule::pick( $this->three_days( true, false ), $this->instant_at( '2030-07-05 12:00' ) );

		$this->assertSame( 'Day 1', $before['label'] );
		$this->assertSame( 'Day 1', $after['label'] );
	}

	public function test_pick_gives_null_when_no_day_is_counted(): void {
		$none = array_map(
			static function ( array $day ): array {
				$day['countdown'] = false;
				return $day;
			},
			$this->three_days()
		);

		$this->assertNull( Schedule::pick( $none, $this->instant_at( '2030-07-01 12:00' ) ) );
		$this->assertNull( Schedule::pick( array(), $this->instant_at( '2030-07-01 12:00' ) ) );
	}

	public function test_pick_index_points_at_the_day_pick_returns(): void {
		$days  = $this->three_days();
		$cases = array(
			'2030-06-29 08:00' => 0,
			'2030-06-30 16:00' => 0,
			'2030-06-30 17:00' => 1,
			'2030-07-01 12:00' => 2,
			'2030-07-05 12:00' => 2,
		);
		foreach ( $cases as $now => $index ) {
			$this->assertSame( $index, Schedule::pick_index( $days, $this->instant_at( $now ) ), $now );
			$this->assertSame( $days[ $index ], Schedule::pick( $days, $this->instant_at( $now ) ), $now );
		}
	}

	public function test_pick_index_skips_uncounted_days_and_is_null_without_one(): void {
		$this->assertSame( 1, Schedule::pick_index( $this->three_days( false ), $this->instant_at( '2030-06-29 08:00' ) ) );
		$this->assertSame( 1, Schedule::pick_index( $this->three_days( true, false ), $this->instant_at( '2030-07-05 12:00' ) ) );

		$none = array_map( static function ( array $day ): array { $day['countdown'] = false; return $day; }, $this->three_days() );
		$this->assertNull( Schedule::pick_index( $none, $this->instant_at( '2030-07-01 12:00' ) ) );
		$this->assertNull( Schedule::pick_index( array(), $this->instant_at( '2030-07-01 12:00' ) ) );
	}

	/** @return array<string,array{0:int,1:int,2:int,3:int}> Day count, then the small, medium and large column counts. */
	public static function column_cases(): array {
		return array(
			'one'   => array( 1, 1, 1, 1 ),
			'two'   => array( 2, 1, 2, 2 ),
			'three' => array( 3, 1, 3, 3 ),
			'four'  => array( 4, 2, 2, 4 ),
			'five'  => array( 5, 2, 3, 5 ),
			'six'   => array( 6, 2, 3, 3 ),
			'seven' => array( 7, 2, 3, 3 ),
		);
	}

	/** @dataProvider column_cases */
	public function test_columns_follow_the_grid_rules( int $count, int $small, int $medium, int $large ): void {
		$this->assertSame( array( 'small' => $small, 'medium' => $medium, 'large' => $large ), Schedule::columns( $count ) );
	}

	public function test_current_uses_the_now_filter(): void {
		$id = $this->post_with(
			array(
				array(
					'label' => 'Day 1',
					'start' => '2030-07-01 09:00',
				),
				array(
					'label' => 'Day 2',
					'start' => '2030-07-02 09:00',
				),
			)
		);

		add_filter( 'hyperpress_competition_now', fn() => $this->instant_at( '2030-07-01 12:00' ) );
		$this->assertSame( 'Day 2', Schedule::current( $id )['label'] );

		remove_all_filters( 'hyperpress_competition_now' );
		add_filter( 'hyperpress_competition_now', fn() => $this->instant_at( '2030-06-01 12:00' ) );
		$this->assertSame( 'Day 1', Schedule::current( $id )['label'] );
	}

	public function test_a_non_datetime_now_filter_falls_back_to_the_real_clock(): void {
		$id = $this->post_with(
			array(
				array(
					'label' => 'Past',
					'start' => '2000-01-01 09:00',
				),
				array(
					'label' => 'Future',
					'start' => '2999-01-01 09:00',
				),
			)
		);

		add_filter( 'hyperpress_competition_now', fn() => 'yesterday' );

		$this->assertSame( 'Future', Schedule::current( $id )['label'] );
	}

	public function test_an_explicit_now_overrides_the_clock(): void {
		$id = $this->post_with(
			array(
				array(
					'label' => 'Day 1',
					'start' => '2030-07-01 09:00',
				),
			)
		);

		$this->assertSame( 'Day 1', Schedule::current( $id, $this->instant_at( '2030-07-05 00:00' ) )['label'] );
	}

	public function test_old_start_date_key_is_not_read(): void {
		$id = self::factory()->post->create();
		update_post_meta( $id, 'hyperpress_competition_event_start_date', '2030-07-01 10:00' );

		$this->assertSame( array(), Schedule::days( $id ) );
	}
}
