<?php
namespace HyperPress\Tests\Integration\Frontpage;

use DateTimeImmutable;
use HyperPress\Countdown\Assets;
use HyperPress\Frontpage\Countdowns;
use HyperPress\Frontpage\Settings;
use WP_UnitTestCase;

final class CountdownCandidatesTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb_posts'], $GLOBALS['hyperpress_test_rwmb'] );
		$GLOBALS['wp_scripts'] = null;
		update_option( 'timezone_string', '' );
		delete_option( Settings::OPTION );
		parent::tear_down();
	}

	/**
	 * @param array<string,mixed> $meta Stored meta of this countdown (Meta Box stub).
	 * @param array<string,mixed> $post Post overrides.
	 */
	private function countdown( array $meta = array(), array $post = array() ): int {
		$id = self::factory()->post->create( array_merge( array( 'post_type' => 'hyper_countdown', 'post_status' => 'publish', 'post_title' => 'Kickoff' ), $post ) );
		$GLOBALS['hyperpress_test_rwmb_posts'][ $id ] = array_merge( array( 'hyperpress_countdown_event_date_time' => '2030-01-01 10:00' ), $meta );

		return $id;
	}

	/** @param array<int,mixed> $ids Values stored in the Countdowns field. */
	private function select( array $ids ): void {
		update_option( Settings::OPTION, array( Settings::COUNTDOWNS => $ids ) );
	}

	/** @return array<int,array<string,mixed>> */
	private function candidates(): array {
		return Countdowns::selected_countdown_candidates( array() );
	}

	public function test_the_filter_is_hooked_before_the_other_sources(): void {
		$this->assertSame( 5, has_filter( Countdowns::CANDIDATES_FILTER, array( Countdowns::class, 'selected_countdown_candidates' ) ) );
	}

	public function test_ids_are_sanitized_and_duplicates_ignored(): void {
		$a = $this->countdown();
		$b = $this->countdown();
		$this->select( array( (string) $a, '7" x="y', 'abc', '0', $a, (string) $b, array( 'x' ) ) );

		$ids = array_column( $this->candidates(), 'id' );

		$this->assertSame( array( "countdown-$a", "countdown-$b" ), $ids );
	}

	public function test_unpublished_and_non_countdown_posts_are_skipped(): void {
		$draft = $this->countdown( array(), array( 'post_status' => 'draft' ) );
		$post  = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$ok    = $this->countdown();
		$this->select( array( $draft, $post, $ok ) );

		$this->assertSame( array( "countdown-$ok" ), array_column( $this->candidates(), 'id' ) );
	}

	public function test_a_missing_or_malformed_option_adds_nothing_and_keeps_earlier_candidates(): void {
		$existing = array( array( 'id' => 'other' ) );

		$this->assertSame( $existing, Countdowns::selected_countdown_candidates( $existing ) );

		update_option( Settings::OPTION, 'garbage' );
		$this->assertSame( $existing, Countdowns::selected_countdown_candidates( $existing ) );

		$this->select( array() );
		$this->assertSame( $existing, Countdowns::selected_countdown_candidates( $existing ) );
		$this->assertSame( array(), Countdowns::selected_countdown_candidates( 'nonsense' ) );
	}

	public function test_an_explicit_hide_after_is_the_hard_end_and_there_is_no_fallback(): void {
		$id = $this->countdown(
			array(
				'hyperpress_countdown_hide_before' => '2029-12-01 08:00',
				'hyperpress_countdown_hide_after'  => '2030-01-02 20:00',
			)
		);
		$this->select( array( $id ) );

		$candidate = $this->candidates()[0];

		$this->assertSame( '2030-01-01 10:00', $candidate['start']->format( 'Y-m-d H:i' ) );
		$this->assertSame( '2029-12-01 08:00', $candidate['show_from']->format( 'Y-m-d H:i' ) );
		$this->assertSame( '2030-01-02 20:00', $candidate['show_until']->format( 'Y-m-d H:i' ) );
		$this->assertNull( $candidate['fallback_after'] );
		$this->assertSame( 10, $candidate['priority'] );
	}

	public function test_without_hide_after_it_becomes_a_fallback_at_the_end_of_its_day(): void {
		$this->select( array( $this->countdown( array( 'hyperpress_countdown_event_date_time' => '2030-01-01 23:50' ) ) ) );

		$candidate = $this->candidates()[0];

		$this->assertNull( $candidate['show_until'] );
		$this->assertSame( '2030-01-01 23:59:59', $candidate['fallback_after']->format( 'Y-m-d H:i:s' ) );
	}

	public function test_the_end_of_day_is_in_the_site_timezone_across_a_dst_change(): void {
		update_option( 'timezone_string', 'America/New_York' );
		$this->select( array( $this->countdown( array( 'hyperpress_countdown_event_date_time' => '2030-03-10 10:00' ) ) ) );

		$fallback = $this->candidates()[0]['fallback_after'];

		$this->assertSame( '2030-03-10 23:59:59', $fallback->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( -14400, $fallback->getOffset() ); // EDT: the clocks went forward that morning.
	}

	public function test_render_returns_the_countdown_markup(): void {
		$id = $this->countdown( array( 'hyperpress_countdown_event_name' => 'Big Game' ) );
		$this->select( array( $id ) );

		$html = ( $this->candidates()[0]['render'] )();

		$this->assertStringContainsString( 'countdown align-center', $html );
		$this->assertStringContainsString( 'Big Game', $html );
	}

	public function test_a_candidate_works_with_pick(): void {
		$id = $this->countdown();
		$this->select( array( $id ) );

		$picked = Countdowns::pick( $this->candidates(), new DateTimeImmutable( '2029-12-25 00:00:00', wp_timezone() ) );

		$this->assertSame( "countdown-$id", $picked['id'] );
	}

	public function test_the_render_does_not_re_check_the_window_the_picker_already_applied(): void {
		$id = $this->countdown();
		$this->select( array( $id ) );
		$seen = array();
		add_filter(
			'pre_do_shortcode_tag',
			static function ( $output, $tag, $attr ) use ( &$seen ) {
				if ( 'hyperpress_countdown' === $tag ) {
					$seen[] = $attr;
					return '<div>rendered</div>';
				}
				return $output;
			},
			10,
			3
		);

		( $this->candidates()[0]['render'] )();

		$this->assertSame( array( array( 'id' => (string) $id, 'ignoreshowhide' => 'true' ) ), $seen );
		remove_all_filters( 'pre_do_shortcode_tag' );
	}

	public function test_render_gives_an_empty_string_when_the_shortcode_is_not_registered(): void {
		$id = $this->countdown();
		$this->select( array( $id ) );
		$render = $this->candidates()[0]['render'];
		remove_shortcode( 'hyperpress_countdown' );

		try {
			$this->assertSame( '', $render() );
		} finally {
			\HyperPress\Countdown\Shortcode::register();
		}
	}
}
