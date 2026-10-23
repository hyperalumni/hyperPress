<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Content;
use HyperPress\Competition\Schedule;
use HyperPress\Countdown\Assets;
use WP_UnitTestCase;

final class ContentTest extends WP_UnitTestCase {

	/** Shorthand in a fixture's meta for a one-day schedule that counts down. */
	private const START = 'start_shorthand';

	public function set_up(): void {
		parent::set_up();
		update_option( 'timezone_string', 'America/New_York' );
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
		Content::register();
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb_posts'] );
		remove_all_filters( 'hyperpress_competition_now' );
		delete_option( 'timezone_string' );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	/**
	 * @param array<string,mixed>            $meta  Meta fixtures; self::START becomes a one-row schedule.
	 * @param string                         $title Post title.
	 * @param list<array<string,mixed>>|null $days  Raw schedule rows, stored as given.
	 */
	private function competition( array $meta, string $title = 'Granite State', ?array $days = null ): int {
		$id = self::factory()->post->create(
			array( 'post_type' => 'hyper_competition', 'post_status' => 'publish', 'post_title' => $title )
		);
		if ( isset( $meta[ self::START ] ) ) {
			$days = array( array( 'label' => '', 'start' => $meta[ self::START ] ) );
			unset( $meta[ self::START ] );
		}
		if ( null !== $days ) {
			update_post_meta( $id, Schedule::FIELD, $days );
		}
		$GLOBALS['hyperpress_test_rwmb_posts'][ $id ] = $meta;
		return $id;
	}

	/** @return array<string,mixed> */
	private function full_meta(): array {
		return array(
			'hyperpress_competition_venue_name'         => 'Arena <b>One</b>',
			'hyperpress_competition_host_name'          => 'Host & Co',
			'hyperpress_competition_venue_address'      => '1 Main St',
			self::START                                 => '2030-07-01 10:00',
			'hyperpress_competition_ne_first_event_url' => array( 'url' => 'https://external.example/e?a=1&b=2', 'title' => 'Event "page"' ),
			'hyperpress_competition_stream_url'         => array( 'url' => '', 'title' => 'Empty' ),
		);
	}

	/**
	 * Result of Content::is_full_view() while the last rendered competition was set up.
	 */
	private bool $rendered_full_view = false;

	private function render( int $id ): string {
		global $post;
		$post = get_post( $id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );
		$this->rendered_full_view = Content::is_full_view();
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();
		wp_reset_postdata();
		return $html;
	}

	private function date_text(): string {
		$when = new \DateTimeImmutable( '2030-07-01 10:00', wp_timezone() );
		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $when->getTimestamp() );
	}

	public function test_filter_supplies_the_template_only_for_competitions(): void {
		$id = $this->competition( array() );
		$this->go_to( get_permalink( $id ) );
		$this->assertTrue( have_posts() );
		the_post();
		$path = Content::add_content_template( '' );
		$this->assertStringContainsString( 'hyperpress-competition/templates/', $path );
		$this->assertTrue( is_readable( $path ) );

		$other = self::factory()->post->create();
		$GLOBALS['post'] = get_post( $other ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $GLOBALS['post'] );
		$this->assertSame( '', Content::add_content_template( '' ) );
	}

	public function test_full_view_on_the_competitions_own_page(): void {
		$id = $this->competition( $this->full_meta() );
		$this->go_to( get_permalink( $id ) );
		the_post();
		$this->assertTrue( Content::is_full_view() );
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'data-date="2030-07-01T10:00:00-04:00"', $html );
		$this->assertSame( 1, substr_count( $html, 'Arena &lt;b&gt;One&lt;/b&gt;' ) );
		$this->assertStringContainsString( 'Host &amp; Co', $html );
		$this->assertStringContainsString( '1 Main St', $html );
		$this->assertMatchesRegularExpression( '#<a class="button small-down-expanded" href="https://external\.example/e\?a=1(&amp;|&\#038;)b=2" target="_blank" rel="noopener noreferrer">Event &quot;page&quot;</a>#', $html );
		$this->assertSame( 1, substr_count( $html, '<a ' ) );
	}

	public function test_full_view_prints_date_and_venue_inside_the_countdown_only(): void {
		$id = $this->competition( $this->full_meta() );
		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<h4 class="cell align-center"><time>Monday July 01, 2030 @ 10:00AM</time></h4>', $html );
		$this->assertStringContainsString( '<h4 class="cell align-center">Arena &lt;b&gt;One&lt;/b&gt;</h4>', $html );
		$this->assertStringNotContainsString( 'competition-date', $html );
		$this->assertStringNotContainsString( 'competition-venue', $html );
		$this->assertSame( 1, substr_count( $html, '<time>' ) );
	}

	public function test_full_view_without_a_start_date_still_shows_the_venue(): void {
		$meta = $this->full_meta();
		unset( $meta[ self::START ] );
		$id = $this->competition( $meta );
		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'data-date', $html );
		$this->assertStringContainsString( '<p class="competition-venue">Arena &lt;b&gt;One&lt;/b&gt;</p>', $html );
		$this->assertStringNotContainsString( 'competition-date', $html );
	}

	public function test_full_view_without_a_venue_has_no_venue_line(): void {
		$meta = $this->full_meta();
		unset( $meta['hyperpress_competition_venue_name'] );
		$id = $this->competition( $meta );
		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<time>', $html );
		$this->assertStringNotContainsString( 'Arena', $html );
		$this->assertStringNotContainsString( 'competition-venue', $html );
	}

	public function test_full_view_labels_address_and_host(): void {
		$id = $this->competition( $this->full_meta() );
		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<p class="competition-address"><strong>Address:</strong> 1 Main St</p>', $html );
		$this->assertStringContainsString( '<p class="competition-host"><strong>Hosted by:</strong> Host &amp; Co</p>', $html );
	}

	public function test_full_view_groups_links_into_info_and_stream_buttons(): void {
		$meta                                      = $this->full_meta();
		$meta['hyperpress_competition_stream_url'] = array(
			array( 'url' => 'https://external.example/sat?x=1&y=2', 'title' => 'Saturday <b>' ),
			array( 'url' => 'https://external.example/sun', 'title' => 'Sunday' ),
		);
		$id = $this->competition( $meta );
		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<div class="competition-links">', $html );
		$this->assertStringContainsString( '<h6 class="subheader">Event Info</h6>', $html );
		$this->assertStringContainsString( '<h6 class="subheader">Watch Live</h6>', $html );
		$this->assertLessThan( strpos( $html, 'Watch Live' ), strpos( $html, 'Event Info' ) );
		$this->assertMatchesRegularExpression( '#<a class="button alert small-down-expanded" href="https://external\.example/sat\?x=1(&amp;|&\#038;)y=2" target="_blank" rel="noopener noreferrer">Saturday &lt;b&gt; <i class="fa-solid fa-circle-play" aria-hidden="true"></i></a>#', $html );
		$this->assertStringContainsString( '>Sunday <i class="fa-solid fa-circle-play" aria-hidden="true"></i></a>', $html );
		$this->assertSame( 2, substr_count( $html, 'class="button alert small-down-expanded"' ) );
		$this->assertMatchesRegularExpression( '#</a>\s+<a class="button alert small-down-expanded"#', $html );
		$this->assertSame( 1, substr_count( $html, 'class="button small-down-expanded"' ) );
		$this->assertStringNotContainsString( '<ul class="competition-links"', $html );
	}

	public function test_a_link_group_without_links_is_omitted_with_its_label(): void {
		$id = $this->competition(
			array( 'hyperpress_competition_stream_url' => array( 'url' => 'https://external.example/s', 'title' => '' ) )
		);
		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Watch Live', $html );
		$this->assertStringNotContainsString( 'Event Info', $html );
		$this->assertStringContainsString( '>Stream <i class="fa-solid fa-circle-play" aria-hidden="true"></i></a>', $html );

		$id = $this->competition(
			array( 'hyperpress_competition_agenda_url' => array( 'url' => 'https://external.example/a', 'title' => '' ) )
		);
		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Event Info', $html );
		$this->assertStringNotContainsString( 'Watch Live', $html );
		$this->assertStringNotContainsString( 'button alert', $html );
		$this->assertStringNotContainsString( 'fa-circle-play', $html );
	}

	public function test_internal_link_opens_in_the_same_tab_without_rel(): void {
		$id   = $this->competition(
			array( 'hyperpress_competition_agenda_url' => array( 'url' => home_url( '/agenda/' ), 'title' => 'Agenda' ) )
		);
		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();

		$this->assertMatchesRegularExpression( '#<a class="button small-down-expanded" href="[^"]*/agenda/" target="_self">Agenda</a>#', $html );
		$this->assertStringNotContainsString( 'noopener', $html );
	}

	public function test_full_view_shows_the_blurb_and_the_feed_view_does_not(): void {
		$meta                                  = $this->full_meta();
		$meta['hyperpress_competition_blurb'] = 'Headed to <a href="https://example.org">Reading</a>';
		$id                                    = $this->competition( $meta );

		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$full = (string) ob_get_clean();
		$this->assertStringContainsString( '<div class="competition-blurb"><p>Headed to <a href="https://example.org">Reading</a></p>', $full );

		$this->go_to( home_url( '/' ) );
		$this->assertStringNotContainsString( 'Headed to', $this->render( $id ) );
	}

	public function test_summary_in_a_list(): void {
		$id = $this->competition( $this->full_meta() );
		$this->go_to( home_url( '/' ) );
		$html = $this->render( $id );

		$this->assertStringContainsString( '<a href="' . get_permalink( $id ) . '" rel="bookmark">Granite State</a>', $html );
		$this->assertStringContainsString( $this->date_text(), $html );
		$this->assertStringContainsString( 'Arena &lt;b&gt;One&lt;/b&gt;', $html );
		$this->assertStringContainsString( '1 Main St', $html );
		$this->assertStringContainsString( '<p class="competition-date">', $html );
		$this->assertStringContainsString( '<p class="competition-venue">', $html );
		$this->assertStringNotContainsString( '<strong>Address:</strong>', $html );
		$this->assertStringNotContainsString( 'countdown', $html );
		$this->assertStringNotContainsString( 'target="_blank"', $html );
		$this->assertStringNotContainsString( 'hyperpress-map', $html );
	}

	public function test_summary_when_listed_inside_another_page(): void {
		$id   = $this->competition( $this->full_meta() );
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		$this->go_to( get_permalink( $page ) );
		$html = $this->render( $id );

		$this->assertFalse( $this->rendered_full_view );
		$this->assertStringNotContainsString( 'countdown', $html );
		$this->assertStringNotContainsString( 'data-date', $html );
		$this->assertStringContainsString( 'rel="bookmark"', $html );
	}

	public function test_summary_for_a_competition_listed_on_another_competitions_page(): void {
		$page_owner = $this->competition( $this->full_meta(), 'Page Owner' );
		$listed     = $this->competition( $this->full_meta(), 'Listed' );
		$this->go_to( get_permalink( $page_owner ) );
		$this->assertTrue( is_singular( 'hyper_competition' ) );
		$html = $this->render( $listed );

		$this->assertFalse( $this->rendered_full_view );
		$this->assertStringContainsString( '<a href="' . get_permalink( $listed ) . '" rel="bookmark">Listed</a>', $html );
		$this->assertStringNotContainsString( 'countdown', $html );
		$this->assertStringNotContainsString( 'data-date', $html );
		$this->assertStringNotContainsString( 'target="_blank"', $html );
		$this->assertStringNotContainsString( 'Host &amp; Co', $html );
	}

	public function test_summary_does_not_show_the_host(): void {
		$id = $this->competition( $this->full_meta() );
		$this->go_to( home_url( '/' ) );

		$this->assertStringNotContainsString( 'Host &amp; Co', $this->render( $id ) );
	}

	public function test_thumbnail_appears_in_both_views(): void {
		$marker = static fn(): string => '<img class="thumbnail" src="https://external.example/t.jpg" alt="">';
		add_filter( 'post_thumbnail_html', $marker );

		$id = $this->competition( $this->full_meta() );
		$this->go_to( home_url( '/' ) );
		$summary = $this->render( $id );

		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$full = (string) ob_get_clean();

		remove_filter( 'post_thumbnail_html', $marker );

		$this->assertStringContainsString( 'class="thumbnail"', $summary );
		$this->assertStringContainsString( 'class="thumbnail"', $full );
	}

	public function test_empty_fields_are_skipped(): void {
		$id = $this->competition( array() );
		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'countdown', $html );
		$this->assertStringNotContainsString( 'competition-venue', $html );
		$this->assertStringNotContainsString( 'competition-address', $html );
		$this->assertStringNotContainsString( 'competition-host', $html );
		$this->assertStringNotContainsString( 'competition-date', $html );
		$this->assertStringNotContainsString( 'competition-links', $html );
		$this->assertStringNotContainsString( 'Address', $html );
		$this->assertStringNotContainsString( 'Hosted by', $html );
		$this->assertStringNotContainsString( 'Event Info', $html );
		$this->assertStringNotContainsString( 'Watch Live', $html );
	}

	/**
	 * @return list<array<string,string>> Load In (unticked), Day 1 and Day 2.
	 */
	private static function three_days(): array {
		return array(
			array( 'label' => 'Load In', 'start' => '2030-06-30 16:00', 'skip' => '1' ),
			array( 'label' => 'Day 1', 'start' => '2030-07-01 09:00' ),
			array( 'label' => 'Day 2', 'start' => '2030-07-02 09:00' ),
		);
	}

	private function full_html( int $id ): string {
		$this->go_to( get_permalink( $id ) );
		the_post();
		ob_start();
		include Content::add_content_template( '' );
		return (string) ob_get_clean();
	}

	/** @param string $text Wall-clock time in the site zone. */
	private function set_now( string $text ): void {
		remove_all_filters( 'hyperpress_competition_now' );
		add_filter( 'hyperpress_competition_now', fn() => new \DateTimeImmutable( $text, wp_timezone() ) );
	}

	public function test_single_day_has_no_schedule_list(): void {
		$html = $this->full_html( $this->competition( $this->full_meta() ) );

		$this->assertStringNotContainsString( 'competition-schedule', $html );
		$this->assertStringNotContainsString( 'Schedule', $html );
	}

	/**
	 * @return list<array<string,string>> $n counted days labelled "Day 1"... on consecutive mornings.
	 */
	private static function n_days( int $n ): array {
		$days = array();
		for ( $i = 1; $i <= $n; $i++ ) {
			$days[] = array( 'label' => 'Day ' . $i, 'start' => sprintf( '2030-07-%02d 09:00', $i ) );
		}
		return $days;
	}

	private function fmt( string $text, string $format ): string {
		return wp_date( $format, ( new \DateTimeImmutable( $text, wp_timezone() ) )->getTimestamp(), wp_timezone() );
	}

	/** @return list<string> The opening tag of every schedule card, in order. */
	private function card_tags( string $html ): array {
		preg_match_all( '#<div class="callout text-center[^"]*"[^>]*>#', $html, $m );
		return $m[0];
	}

	/** @return array<string,array{0:int,1:string}> Day count => expected column classes. */
	public static function column_cases(): array {
		return array(
			'two'   => array( 2, 'small-up-1 medium-up-2 large-up-2' ),
			'three' => array( 3, 'small-up-1 medium-up-3 large-up-3' ),
			'four'  => array( 4, 'small-up-2 medium-up-2 large-up-4' ),
			'five'  => array( 5, 'small-up-2 medium-up-3 large-up-5' ),
			'seven' => array( 7, 'small-up-2 medium-up-3 large-up-3' ),
		);
	}

	/** @dataProvider column_cases */
	public function test_schedule_cards_use_the_column_rules( int $count, string $classes ): void {
		$html = $this->full_html( $this->competition( array(), 'Granite State', self::n_days( $count ) ) );

		$this->assertStringContainsString( '<div class="competition-schedule grid-x grid-margin-x align-center ' . $classes . '">', $html );
		$this->assertSame( $count, substr_count( $html, '<div class="cell"><div class="callout text-center' ) );
		$this->assertStringContainsString( '<strong>Schedule:</strong>', $html );
		$this->assertStringNotContainsString( '<ul class="competition-schedule', $html );
	}

	public function test_two_and_three_days_show_the_full_date(): void {
		$html = $this->full_html( $this->competition( array(), 'Granite State', self::n_days( 3 ) ) );

		$this->assertStringContainsString( '<h6>Day 1</h6><div><strong>' . $this->fmt( '2030-07-01 09:00', 'l, F j' ) . '</strong></div><div>9:00 AM</div>', $html );
		$this->assertStringContainsString( '<strong>Tuesday, July 2</strong>', $html );
		$this->assertLessThan( strpos( $html, 'Day 3</h6>' ), strpos( $html, 'Day 2</h6>' ) );
		$this->assertLessThan( strpos( $html, 'Day 2</h6>' ), strpos( $html, 'Day 1</h6>' ) );
	}

	public function test_four_or_more_days_show_the_short_date(): void {
		$html = $this->full_html( $this->competition( array(), 'Granite State', self::n_days( 4 ) ) );

		$this->assertStringContainsString( '<h6>Day 1</h6><div><strong>' . $this->fmt( '2030-07-01 09:00', 'D, M j' ) . '</strong></div><div>9:00 AM</div>', $html );
		$this->assertStringContainsString( '<strong>Tue, Jul 2</strong>', $html );
		$this->assertStringNotContainsString( 'Tuesday', $html );
	}

	public function test_schedule_time_uses_the_site_zone_and_a_12_hour_clock(): void {
		$days = array( array( 'label' => 'Load In', 'start' => '2030-06-30 16:00' ), array( 'label' => 'Day 1', 'start' => '2030-07-01 09:05' ) );
		$html = $this->full_html( $this->competition( array(), 'Granite State', $days ) );

		$this->assertStringContainsString( '<strong>Sunday, June 30</strong></div><div>4:00 PM</div>', $html );
		$this->assertStringContainsString( '<div>9:05 AM</div>', $html );
	}

	public function test_a_card_without_a_label_has_no_heading(): void {
		$days = array( array( 'label' => '', 'start' => '2030-07-01 10:00' ), array( 'label' => 'Day 2', 'start' => '2030-07-02 09:00' ) );
		$html = $this->full_html( $this->competition( array(), 'Granite State', $days ) );

		$this->assertSame( 1, substr_count( $html, '<h6>' ) );
		$this->assertStringContainsString( '<div class="callout text-center primary" aria-current="true"><div><strong>' . $this->fmt( '2030-07-01 10:00', 'l, F j' ) . '</strong></div><div>10:00 AM</div>', $html );
	}

	public function test_schedule_labels_are_escaped(): void {
		$days = array( array( 'label' => 'A <b>&"x"', 'start' => '2030-07-01 09:00' ), array( 'label' => 'Day 2', 'start' => '2030-07-02 09:00' ) );
		$html = $this->full_html( $this->competition( array(), 'Granite State', $days ) );

		$this->assertStringContainsString( '<h6>A &lt;b&gt;&amp;&quot;x&quot;</h6>', $html );
		$this->assertStringNotContainsString( 'A <b>', $html );
	}

	public function test_card_states_follow_each_days_end(): void {
		$id      = $this->competition( array(), 'Granite State', self::three_days() );
		$plain   = '<div class="callout text-center">';
		$faded   = '<div class="callout text-center faded">';
		$current = '<div class="callout text-center primary" aria-current="true">';
		$cases   = array(
			'2030-06-29 08:00'    => array( $plain, $current, $plain ),
			'2030-07-01 12:00'    => array( $faded, $current, $plain ),
			'2030-07-01 19:59:59' => array( $faded, $current, $plain ),
			'2030-07-01 20:00'    => array( $faded, $faded, $current ),
			'2030-07-02 19:59:59' => array( $faded, $faded, $current ),
		);
		foreach ( $cases as $now => $tags ) {
			$this->set_now( $now );
			$this->assertSame( $tags, $this->card_tags( $this->full_html( $id ) ), $now );
		}

		$this->set_now( '2030-07-02 20:00' );
		$html = $this->full_html( $id );
		$this->assertSame( array( $faded, $faded, $faded ), $this->card_tags( $html ), 'a finished event highlights nothing' );
		$this->assertStringNotContainsString( 'aria-current', $html );
	}

	public function test_no_card_is_current_when_every_day_is_skipped(): void {
		$days = array( array( 'label' => 'A', 'start' => '2030-07-01 09:00', 'skip' => '1' ), array( 'label' => 'B', 'start' => '2030-07-02 09:00', 'skip' => '1' ) );
		$this->set_now( '2030-07-02 12:00' );
		$html = $this->full_html( $this->competition( array(), 'Granite State', $days ) );

		$this->assertStringNotContainsString( 'aria-current', $html );
		$this->assertSame( array( '<div class="callout text-center faded">', '<div class="callout text-center">' ), $this->card_tags( $html ) );
	}

	public function test_schedule_is_full_view_only(): void {
		$id = $this->competition( array(), 'Granite State', self::three_days() );
		$this->go_to( home_url( '/' ) );
		$this->assertStringNotContainsString( 'competition-schedule', $this->render( $id ) );

		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		$this->go_to( get_permalink( $page ) );
		$html = $this->render( $id );
		$this->assertFalse( $this->rendered_full_view );
		$this->assertStringNotContainsString( 'competition-schedule', $html );
		$this->assertStringNotContainsString( 'Schedule', $html );
	}

	public function test_summary_date_follows_the_current_day(): void {
		$id = $this->competition( array(), 'Granite State', self::three_days() );
		$this->go_to( home_url( '/' ) );
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$day    = static fn( string $text ): string => wp_date( $format, ( new \DateTimeImmutable( $text, wp_timezone() ) )->getTimestamp() );

		$this->set_now( '2030-06-29 08:00' );
		$this->assertStringContainsString( '<p class="competition-date">' . $day( '2030-07-01 09:00' ) . '</p>', $this->render( $id ) );

		$this->set_now( '2030-07-01 12:00' );
		$this->assertStringContainsString( '<p class="competition-date">' . $day( '2030-07-01 09:00' ) . '</p>', $this->render( $id ) );

		$this->set_now( '2030-07-01 20:00' );
		$html = $this->render( $id );
		$this->assertStringContainsString( '<p class="competition-date">' . $day( '2030-07-02 09:00' ) . '</p>', $html );
		$this->assertStringNotContainsString( $day( '2030-07-01 09:00' ), $html );
	}

	public function test_countdown_heading_shows_the_day_label_in_the_full_view(): void {
		$this->set_now( '2030-06-30 20:00' );
		$html = $this->full_html( $this->competition( array(), 'Granite State', self::three_days() ) );

		$this->assertStringContainsString( '<h2 class="cell align-center">Day 1</h2>', $html );
		$this->assertStringContainsString( 'data-date="2030-07-01T09:00:00-04:00"', $html );
	}
}
