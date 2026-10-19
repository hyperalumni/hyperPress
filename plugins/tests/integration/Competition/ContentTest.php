<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Content;
use HyperPress\Countdown\Assets;
use WP_UnitTestCase;

final class ContentTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		update_option( 'timezone_string', 'America/New_York' );
		$GLOBALS['wp_scripts'] = null;
		Assets::register_scripts();
		Content::register();
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb_posts'] );
		delete_option( 'timezone_string' );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	/** @param array<string,mixed> $meta */
	private function competition( array $meta, string $title = 'Granite State' ): int {
		$id = self::factory()->post->create(
			array( 'post_type' => 'hyper_competition', 'post_status' => 'publish', 'post_title' => $title )
		);
		$GLOBALS['hyperpress_test_rwmb_posts'][ $id ] = $meta;
		return $id;
	}

	/** @return array<string,mixed> */
	private function full_meta(): array {
		return array(
			'hyperpress_competition_venue_name'         => 'Arena <b>One</b>',
			'hyperpress_competition_host_name'          => 'Host & Co',
			'hyperpress_competition_venue_address'      => '1 Main St',
			'hyperpress_competition_event_start_date'   => '2030-07-01 10:00',
			'hyperpress_competition_ne_first_event_url' => array( 'url' => 'https://example.org/e?a=1&b=2', 'title' => 'Event "page"' ),
			'hyperpress_competition_stream_url'         => array( 'url' => '', 'title' => 'Empty' ),
		);
	}

	private function render( int $id ): string {
		global $post;
		$post = get_post( $id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );
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
		$this->assertStringContainsString( 'Arena &lt;b&gt;One&lt;/b&gt;', $html );
		$this->assertStringContainsString( 'Host &amp; Co', $html );
		$this->assertStringContainsString( '1 Main St', $html );
		$this->assertStringContainsString( $this->date_text(), $html );
		$this->assertMatchesRegularExpression( '#<a href="https://example\.org/e\?a=1(&amp;|&\#038;)b=2" target="_blank" rel="noopener noreferrer">Event &quot;page&quot;</a>#', $html );
		$this->assertSame( 1, substr_count( $html, '<a ' ) );
	}

	public function test_summary_in_a_list(): void {
		$id = $this->competition( $this->full_meta() );
		$this->go_to( home_url( '/' ) );
		$html = $this->render( $id );

		$this->assertStringContainsString( '<a href="' . get_permalink( $id ) . '" rel="bookmark">Granite State</a>', $html );
		$this->assertStringContainsString( $this->date_text(), $html );
		$this->assertStringContainsString( 'Arena &lt;b&gt;One&lt;/b&gt;', $html );
		$this->assertStringContainsString( '1 Main St', $html );
		$this->assertStringNotContainsString( 'countdown', $html );
		$this->assertStringNotContainsString( 'target="_blank"', $html );
		$this->assertStringNotContainsString( 'hyperpress-map', $html );
	}

	public function test_summary_when_listed_inside_another_page(): void {
		$id   = $this->competition( $this->full_meta() );
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		$this->go_to( get_permalink( $page ) );
		$html = $this->render( $id );

		$this->assertFalse( Content::is_full_view() );
		$this->assertStringNotContainsString( 'countdown', $html );
		$this->assertStringNotContainsString( 'data-date', $html );
		$this->assertStringContainsString( 'rel="bookmark"', $html );
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
	}
}
