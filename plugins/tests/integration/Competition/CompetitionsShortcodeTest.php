<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Competitions;
use HyperPress\Competition\Countdown;
use HyperPress\Competition\Schedule;
use HyperPress\Countdown\Assets;
use WP_UnitTestCase;

final class CompetitionsShortcodeTest extends WP_UnitTestCase {

	private const DIVIDER = 'class="competition-divider"';

	public function set_up(): void {
		parent::set_up();
		update_option( 'timezone_string', 'America/New_York' );
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
		Countdown::register();
		Competitions::register();
		add_filter( 'hyperpress_competition_now', fn() => new \DateTimeImmutable( '2030-01-01 12:00', wp_timezone() ) );
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb_posts'] );
		remove_all_filters( 'hyperpress_competition_now' );
		delete_option( 'timezone_string' );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	/**
	 * @param array<string,mixed>      $meta  Meta fixtures.
	 * @param list<string>             $starts Start times, one schedule row each.
	 * @param array<string,string>     $terms  Taxonomy => term name.
	 */
	private function competition( string $title, array $meta = array(), array $starts = array(), array $terms = array() ): int {
		$id = self::factory()->post->create( array( 'post_type' => 'hyper_competition', 'post_status' => 'publish', 'post_title' => $title ) );
		if ( array() !== $starts ) {
			update_post_meta( $id, Schedule::FIELD, array_map( static fn( string $start ): array => array( 'label' => 'Day', 'start' => $start ), $starts ) );
		}
		foreach ( $terms as $taxonomy => $name ) {
			$term = get_term_by( 'name', $name, $taxonomy );
			$term_id = $term instanceof \WP_Term ? $term->term_id : (int) wp_insert_term( $name, $taxonomy )['term_id'];
			wp_set_object_terms( $id, array( $term_id ), $taxonomy );
		}
		$GLOBALS['hyperpress_test_rwmb_posts'][ $id ] = $meta;
		return $id;
	}

	public function test_item_has_title_link_week_range_venue_address_blurb_countdown_and_buttons(): void {
		$id   = $this->competition(
			'North Shore District Event',
			array(
				'hyperpress_competition_venue_name'         => 'Reading Memorial High School',
				'hyperpress_competition_venue_address'      => '62 Oakland Road, Reading, MA 01867',
				'hyperpress_competition_blurb'              => 'Team HYPER heads to <a href="https://example.org">Reading</a>',
				'hyperpress_competition_ne_first_event_url' => array( 'url' => 'https://external.example/ne', 'title' => '' ),
			),
			array( '2030-03-13 16:00', '2030-03-15 09:00' ),
			array( 'hyper_week' => 'Week 2' )
		);
		$html = Competitions::render( array() );

		$this->assertStringContainsString( '<h2 class="entry-title"><a href="' . get_permalink( $id ) . '" rel="bookmark">North Shore District Event</a></h2>', $html );
		$this->assertStringContainsString( '<h3 class="competition-when">Week 2: March 13–15, 2030</h3>', $html );
		$this->assertSame( 1, substr_count( $html, 'Reading Memorial High School' ), 'the venue is printed once, outside the countdown' );
		$this->assertStringContainsString( '<p class="competition-address">62 Oakland Road, Reading, MA 01867</p>', $html );
		$this->assertStringContainsString( '<div class="competition-blurb"><p>Team HYPER heads to <a href="https://example.org">Reading</a></p>', $html );
		$this->assertStringContainsString( 'data-date="2030-03-13T16:00:00-04:00"', $html );
		$this->assertStringContainsString( '<a class="button small-down-expanded" href="https://external.example/ne" target="_blank" rel="noopener noreferrer">NE FIRST Event</a>', $html );
	}

	public function test_items_are_in_listing_order_with_dividers(): void {
		$this->competition( 'Earlier Event', array(), array( '2029-03-01 09:00' ) );
		$this->competition( 'Later Event', array(), array( '2030-03-01 09:00' ) );
		$html = Competitions::render( array() );

		$this->assertSame( 2, substr_count( $html, '<article' ) );
		$this->assertSame( 1, substr_count( $html, self::DIVIDER ) );
		$this->assertLessThan( strpos( $html, 'Earlier Event' ), strpos( $html, 'Later Event' ), 'unfinished before finished' );
	}

	public function test_a_competition_with_only_a_title_leaves_no_empty_markup(): void {
		$this->competition( 'Bare Event' );
		$html = Competitions::render( array() );

		$this->assertStringContainsString( 'Bare Event', $html );
		foreach ( array( '<h3', 'competition-venue', 'competition-address', 'competition-blurb', 'competition-links', 'data-date', 'Array' ) as $absent ) {
			$this->assertStringNotContainsString( $absent, $html, $absent );
		}
	}

	public function test_heading_uses_whichever_of_week_and_dates_exist(): void {
		$this->competition( 'Dated Event', array(), array( '2030-03-13 16:00' ) );
		$this->assertStringContainsString( '<h3 class="competition-when">March 13, 2030</h3>', Competitions::render( array() ) );

		$this->competition( 'Weekly Event', array(), array(), array( 'hyper_week' => 'Week 3' ) );
		$this->assertStringContainsString( '<h3 class="competition-when">Week 3</h3>', Competitions::render( array() ) );
	}

	public function test_output_is_escaped(): void {
		$this->competition(
			'Fish & Chips',
			array(
				'hyperpress_competition_venue_name'    => 'Arena <b>One</b>',
				'hyperpress_competition_venue_address' => '1 <i>Main</i> St',
				'hyperpress_competition_blurb'         => 'Hi <script>alert(1)</script>',
			)
		);
		$html = Competitions::render( array() );

		$this->assertMatchesRegularExpression( '/Fish &(amp|#038); Chips/', $html );
		$this->assertStringContainsString( 'Arena &lt;b&gt;One&lt;/b&gt;', $html );
		$this->assertStringContainsString( '1 &lt;i&gt;Main&lt;/i&gt; St', $html );
		$this->assertStringNotContainsString( '<script', $html );
	}

	public function test_empty_result_is_an_empty_string(): void {
		$this->competition( 'Any Event' );

		$this->assertSame( '', Competitions::render( array( 'district' => 'no-such-district' ) ) );
	}

	public function test_shortcode_attributes_are_passed_to_the_listing(): void {
		$this->competition( 'NE Event', array(), array(), array( 'hyper_district' => 'NE FIRST' ) );
		$this->competition( 'MA Event', array(), array(), array( 'hyper_district' => 'FTC MA' ) );
		$html = do_shortcode( '[hyperpress_competitions district="ne-first"]' );

		$this->assertStringContainsString( 'NE Event', $html );
		$this->assertStringNotContainsString( 'MA Event', $html );
	}
}
