<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use WP_UnitTestCase;

final class BannerColorTest extends WP_UnitTestCase {

	private const FILTER = 'hyperpress_banner_shortcode_default_color';

	public function tear_down(): void {
		remove_all_filters( self::FILTER );
		parent::tear_down();
	}

	public function test_color_attribute_sets_the_inline_background(): void {
		$html = do_shortcode( '[hyperpress_banner title="Welcome" color="#F15623"]' );

		$this->assertStringStartsWith( '<header role="banner" class="banner" style="background-color: #F15623">', $html );
	}

	/**
	 * @dataProvider invalid_colors
	 */
	public function test_invalid_colors_are_dropped( string $color ): void {
		$html = do_shortcode( '[hyperpress_banner title="Welcome" color=\'' . $color . '\']' );

		$this->assertStringNotContainsString( 'style=', $html );
		$this->assertStringContainsString( '<header role="banner" class="banner">', $html );
	}

	public static function invalid_colors(): array {
		return array(
			'name'      => array( 'red' ),
			'injection' => array( '#fff;background:url(x)' ),
			'markup'    => array( '"><script>' ),
		);
	}

	public function test_filter_supplies_the_default_when_the_attribute_is_empty(): void {
		$seen = array();
		add_filter(
			self::FILTER,
			static function ( $color, $atts ) use ( &$seen ) {
				$seen = $atts;
				return '#123456';
			},
			10,
			2
		);

		$html = do_shortcode( '[hyperpress_banner title="Welcome"]' );

		$this->assertStringContainsString( 'style="background-color: #123456"', $html );
		$this->assertSame( 'Welcome', $seen['title'] );
	}

	public function test_attribute_wins_over_the_filter(): void {
		add_filter(
			self::FILTER,
			static fn() => '#123456'
		);

		$html = do_shortcode( '[hyperpress_banner title="Welcome" color="#abcdef"]' );

		$this->assertStringContainsString( 'style="background-color: #abcdef"', $html );
		$this->assertStringNotContainsString( '#123456', $html );
	}

	public function test_non_hex_filter_result_is_ignored(): void {
		add_filter(
			self::FILTER,
			static fn() => 'red'
		);

		$html = do_shortcode( '[hyperpress_banner title="Welcome"]' );

		$this->assertStringNotContainsString( 'style=', $html );
	}

	public function test_markup_unchanged_without_a_color(): void {
		$this->assertSame(
			'<header role="banner" class="banner"><span class="anchor" id="about"></span><div class="main-container"><h2 class="entry-title">About us</h2><h4 class="subtitle">Who we are</h4></div></header>',
			do_shortcode( '[hyperpress_banner anchor="about" title="About us" subtitle="Who we are"]' )
		);
	}

	public function test_empty_without_title_and_subtitle(): void {
		add_filter(
			self::FILTER,
			static fn() => '#123456'
		);

		$this->assertSame( '', do_shortcode( '[hyperpress_banner color="#F15623"]' ) );
		$this->assertSame( '', do_shortcode( '[hyperpress_banner]' ) );
	}
}
