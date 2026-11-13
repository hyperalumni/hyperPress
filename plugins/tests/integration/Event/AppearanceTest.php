<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Appearance;
use HyperPress\Event\Fields;
use HyperPress\Event\Plugin;
use HyperPress\Event\Sections;
use HyperPress\Event\Settings;
use WP_UnitTestCase;

final class AppearanceTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		delete_option( Settings::OPTION );
	}

	private function event( array $meta = array() ): int {
		$id = self::factory()->post->create(
			array(
				'post_type'  => Plugin::POST_TYPE,
				'post_title' => 'Summer Camp',
			)
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}

		return $id;
	}

	public function test_color_resolution_order(): void {
		$id = $this->event();
		$this->assertSame( '', Appearance::color( $id ) );

		update_option( Settings::OPTION, array( Settings::DEFAULT_BANNER_COLOR => '#111111' ) );
		$this->assertSame( '#111111', Appearance::color( $id ) );

		update_post_meta( $id, Fields::BANNER_COLOR, '#222222' );
		$this->assertSame( '#222222', Appearance::color( $id ) );
		$this->assertSame( '#333333', Appearance::color( $id, '#333333' ) );

		// Invalid values are skipped to the next level.
		update_post_meta( $id, Fields::BANNER_COLOR, 'red' );
		$this->assertSame( '#111111', Appearance::color( $id ) );
		$this->assertSame( '#111111', Appearance::color( $id, 'blue' ) );
		update_option( Settings::OPTION, array( Settings::DEFAULT_BANNER_COLOR => 'nope' ) );
		$this->assertSame( '', Appearance::color( $id, 'blue' ) );
	}

	private function page( array $args = array(), string $color = '' ): int {
		$id = self::factory()->post->create( array_merge( array( 'post_type' => 'page' ), $args ) );
		if ( '' !== $color ) {
			update_post_meta( $id, 'hyperpress_banner_background_color', $color );
		}

		return $id;
	}

	private function set_hub( int $page_id, array $extra = array() ): void {
		update_option( Settings::OPTION, array_merge( array( Settings::HUB_PAGE => $page_id ), $extra ) );
	}

	public function test_hub_page_colour_is_used_when_nothing_else_is_set(): void {
		$id = $this->event();
		$this->set_hub( $this->page( array(), '#00aa00' ) );

		$this->assertSame( '#00aa00', Appearance::color( $id ) );
		// Event override and shortcode attribute still win.
		update_post_meta( $id, Fields::BANNER_COLOR, '#222222' );
		$this->assertSame( '#222222', Appearance::color( $id ) );
		$this->assertSame( '#333333', Appearance::color( $id, '#333333' ) );
	}

	public function test_own_hub_colour_wins_over_the_top_level_ancestor(): void {
		$top = $this->page( array(), '#0000aa' );
		$mid = $this->page( array( 'post_parent' => $top ) );
		$hub = $this->page( array( 'post_parent' => $mid ), '#00aa00' );
		$this->set_hub( $hub );

		$this->assertSame( '#00aa00', Appearance::color( $this->event() ) );
	}

	public function test_top_level_ancestor_colour_is_used_when_the_hub_has_none(): void {
		$top = $this->page( array(), '#0000aa' );
		$mid = $this->page( array( 'post_parent' => $top ), '#aa0000' );
		$hub = $this->page( array( 'post_parent' => $mid ) );
		$this->set_hub( $hub );

		$this->assertSame( '#0000aa', Appearance::color( $this->event() ) );
	}

	public function test_settings_default_wins_over_the_hub_colour(): void {
		$this->set_hub( $this->page( array(), '#00aa00' ), array( Settings::DEFAULT_BANNER_COLOR => '#111111' ) );

		$this->assertSame( '#111111', Appearance::color( $this->event() ) );
	}

	public function test_invalid_hub_colours_are_skipped(): void {
		$top = $this->page( array(), '#0000aa' );
		$hub = $this->page( array( 'post_parent' => $top ), 'green' );
		$this->set_hub( $hub );
		$this->assertSame( '#0000aa', Appearance::color( $this->event() ) );

		update_post_meta( $top, 'hyperpress_banner_background_color', 'blue' );
		$this->assertSame( '', Appearance::color( $this->event() ) );
	}

	public function test_no_hub_page_leaves_the_colour_unchanged(): void {
		$this->page( array(), '#00aa00' );
		$this->assertSame( '', Appearance::color( $this->event() ) );

		// A hub that is not a published page is ignored too.
		$this->set_hub( $this->page( array( 'post_status' => 'draft' ), '#00aa00' ) );
		$this->assertSame( '', Appearance::color( $this->event() ) );
	}

	public function test_page_and_section_banners_inherit_the_hub_colour(): void {
		$id = $this->event();
		$this->set_hub( $this->page( array(), '#00aa00' ) );

		$this->go_to( get_permalink( $id ) );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
		$this->assertSame( '#00aa00', $banner['backgroundColor'] );

		$GLOBALS['post'] = get_post( $id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->assertStringContainsString( 'background-color: #00aa00', do_shortcode( '[hyperpress_banner title="x"]' ) );
		$this->assertStringContainsString( 'background-color: #00aa00', Sections::banner( $id, Sections::atts( array( 'title' => 'T' ), 'D' ) ) );
	}

	public function test_subtitle_resolution_order(): void {
		$id = $this->event();
		$this->assertSame( '', Appearance::subtitle( $id ) );

		update_option( Settings::OPTION, array( Settings::FALLBACK_SUBTITLE => 'Team HYPER event' ) );
		$this->assertSame( 'Team HYPER event', Appearance::subtitle( $id ) );

		update_post_meta( $id, Fields::SUBTITLE, 'Own subtitle' );
		$this->assertSame( 'Own subtitle', Appearance::subtitle( $id ) );
	}

	public function test_default_color_filter_answers_only_for_the_current_event(): void {
		$id = $this->event( array( Fields::BANNER_COLOR => '#abcdef' ) );

		$GLOBALS['post'] = get_post( $id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->assertStringContainsString( 'background-color: #abcdef', do_shortcode( '[hyperpress_banner title="x"]' ) );

		$page            = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$GLOBALS['post'] = get_post( $page ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->assertStringNotContainsString( 'background-color', do_shortcode( '[hyperpress_banner title="x"]' ) );
		$this->assertSame( '#123456', apply_filters( 'hyperpress_banner_shortcode_default_color', '#123456', array() ) );
	}

	public function test_page_banner_uses_event_title_subtitle_and_colour(): void {
		$id = $this->event(
			array(
				Fields::SUBTITLE     => 'Three days of fun',
				Fields::BANNER_COLOR => '#654321',
			)
		);
		$this->go_to( get_permalink( $id ) );

		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );

		$this->assertSame( 'Summer Camp', $banner['title'] );
		$this->assertSame( 'Three days of fun', $banner['subtitle'] );
		$this->assertSame( '#654321', $banner['backgroundColor'] );
	}

	public function test_section_banner_passes_anchor_and_colour_and_is_empty_without_text(): void {
		$id    = $this->event( array( Fields::BANNER_COLOR => '#00ff00' ) );
		$atts  = Sections::atts( array( 'anchor' => 'gallery' ), 'Gallery' );
		$this->assertSame( 'Gallery', $atts['title'] );
		$this->assertSame( 'gallery', $atts['anchor'] );
		$this->assertSame( '', $atts['subtitle'] );
		$this->assertSame( '', $atts['color'] );

		$html = Sections::banner( $id, $atts );
		$this->assertStringContainsString( 'id="gallery"', $html );
		$this->assertStringContainsString( 'background-color: #00ff00', $html );
		$this->assertStringContainsString( 'Gallery', $html );

		$own = Sections::banner( $id, Sections::atts( array( 'title' => 'T', 'color' => '#010101' ), 'D' ) );
		$this->assertStringContainsString( 'background-color: #010101', $own );

		$this->assertSame( '', Sections::banner( $id, Sections::atts( array( 'title' => '' ), '' ) ) );
	}
}
