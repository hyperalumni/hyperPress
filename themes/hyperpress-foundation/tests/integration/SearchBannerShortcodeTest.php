<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class SearchBannerShortcodeTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		add_shortcode(
			'hp_test_sc',
			static function (): string {
				return 'EXECUTED';
			}
		);
	}

	public function tear_down(): void {
		remove_shortcode( 'hp_test_sc' );
		parent::tear_down();
	}

	/** Build the subtitle the way template-parts/banner.php does: filter, then do_shortcode(). */
	private function rendered_subtitle( string $search ): string {
		$this->go_to( home_url( '/?s=' . rawurlencode( $search ) ) );
		$banner = apply_filters(
			'hyperpress_banner_content',
			array(
				'type'     => '',
				'title'    => '',
				'subtitle' => '',
			)
		);
		return do_shortcode( $banner['subtitle'] );
	}

	public function test_shortcodes_typed_into_the_search_box_are_not_executed(): void {
		$html = $this->rendered_subtitle( '[hp_test_sc]' );
		$this->assertStringNotContainsString( 'EXECUTED', $html );
		$this->assertStringContainsString( 'hp_test_sc', $html, 'the typed text is still displayed' );
	}

	public function test_html_typed_into_the_search_box_is_escaped(): void {
		$html = $this->rendered_subtitle( '<script>alert(1)</script>' );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}
}
