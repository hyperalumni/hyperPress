<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Plugin;
use HyperPress\Event\Single;
use WP_Post;
use WP_UnitTestCase;

final class SingleTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	private function event(): int {
		return self::factory()->post->create(
			array(
				'post_type'    => Plugin::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => 'Spring Open',
				'post_content' => 'Body [hyperpress_test_marker]',
			)
		);
	}

	public function test_full_screen_only_on_a_singular_event(): void {
		$this->go_to( get_permalink( $this->event() ) );
		$this->assertTrue( apply_filters( 'hyperpress_single_full_screen', false ) );

		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_status' => 'publish' ) ) ) );
		$this->assertFalse( apply_filters( 'hyperpress_single_full_screen', false ) );

		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) ) ) );
		$this->assertFalse( apply_filters( 'hyperpress_single_full_screen', false ) );

		$term = self::factory()->term->create( array( 'taxonomy' => Plugin::SERIES ) );
		$this->go_to( get_term_link( $term, Plugin::SERIES ) );
		$this->assertFalse( apply_filters( 'hyperpress_single_full_screen', false ) );
	}

	public function test_content_template_only_for_events(): void {
		$id = $this->event();
		$this->go_to( get_permalink( $id ) );
		$GLOBALS['post'] = get_post( $id );
		setup_postdata( $GLOBALS['post'] );
		$path = apply_filters( 'content_template', '' );
		$this->assertSame( dirname( __DIR__, 3 ) . '/hyperpress-event/templates/content.php', $path );
		$this->assertFileExists( $path );

		$GLOBALS['post'] = get_post( self::factory()->post->create() );
		setup_postdata( $GLOBALS['post'] );
		$this->assertSame( 'x', Single::content_template( 'x' ) );
		$this->assertSame( '', apply_filters( 'content_template', '' ) );
	}

	public function test_content_template_is_not_used_for_an_event_in_a_listing(): void {
		$id = $this->event();
		update_post_meta( $id, 'hyperpress_event_start', '2030-03-10 09:00' );
		$this->go_to( '/?s=Spring' );
		$this->assertTrue( is_search() );

		$this->assertTrue( have_posts() );
		the_post();
		$this->assertSame( $id, get_the_ID() );
		$this->assertSame( '', apply_filters( 'content_template', '' ) );
		$this->assertSame( 'x', Single::content_template( 'x' ) );

		// What the theme's loop would print: the theme's own template, never the event's full content.
		$template = apply_filters( 'content_template', '' );
		$this->assertStringNotContainsString( 'content.php', (string) $template );
		$this->assertStringNotContainsString( 'hyperpress-event-tickets', (string) $template );
	}

	public function test_content_template_is_not_used_for_another_event_inside_the_singular_page(): void {
		$page  = $this->event();
		$other = $this->event();
		$this->go_to( get_permalink( $page ) );
		$GLOBALS['post'] = get_post( $other );
		setup_postdata( $GLOBALS['post'] );

		$this->assertSame( 'x', Single::content_template( 'x' ) );
	}

	public function test_template_prints_the_content_with_shortcodes_run(): void {
		add_shortcode(
			'hyperpress_test_marker',
			static fn(): string => '<b>MARKER-RAN</b>'
		);
		$id = $this->event();
		$this->go_to( get_permalink( $id ) );
		$GLOBALS['post'] = get_post( $id );
		setup_postdata( $GLOBALS['post'] );

		ob_start();
		include apply_filters( 'content_template', '' );
		$html = (string) ob_get_clean();
		remove_shortcode( 'hyperpress_test_marker' );

		$this->assertStringContainsString( 'entry-content', $html );
		$this->assertStringContainsString( 'MARKER-RAN', $html );
		$this->assertStringNotContainsString( '[hyperpress_test_marker]', $html );
	}

	public function test_new_event_gets_the_scaffold(): void {
		$event = new WP_Post( (object) array( 'post_type' => Plugin::POST_TYPE ) );
		$html  = apply_filters( 'default_content', '', $event );
		$this->assertStringContainsString( '[hyperpress_event_countdown]', $html );
		$this->assertStringContainsString( '[hyperpress_banner title="Event Info" anchor="info"]', $html );
		$this->assertStringContainsString( 'grid-x', $html );
		// The rows sit directly in the content: the theme's full-screen layout centres and pads them.
		$this->assertMatchesRegularExpression( '#\\]\\s*<div class="grid-x grid-margin-x">.*</div>\\s*\\[hyperpress_event_tickets\\]#s', $html );
		$this->assertStringNotContainsString( 'class="content"', $html );
		$this->assertStringContainsString( '[hyperpress_event_tickets]', $html );
		$this->assertStringContainsString( '[hyperpress_event_photos]', $html );

		$this->assertSame( 'Typed', apply_filters( 'default_content', 'Typed', $event ) );
		$post = new WP_Post( (object) array( 'post_type' => 'post' ) );
		$this->assertSame( '', apply_filters( 'default_content', '', $post ) );
	}
}
