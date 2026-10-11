<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class FooterEscapingTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		if ( ! class_exists( '\HyperPress\Social\Links' ) ) {
			$this->markTestSkipped( 'Needs the hyperpress-social plugin' );
		}
	}

	private function footer_html(): string {
		$this->go_to( home_url( '/' ) );
		// WordPress core still hooks the deprecated the_block_template_skip_link() on wp_footer, which trips the test case's deprecation check.
		remove_action( 'wp_footer', 'the_block_template_skip_link' );

		ob_start();
		include get_template_directory() . '/footer.php';
		return (string) ob_get_clean();
	}

	public function test_breakout_payloads_in_the_theme_mods_are_not_printed_raw(): void {
		$payload = '"><img src=x onerror=1>';
		set_theme_mod( 'hyperpress_site_copyright_name', $payload );
		foreach ( array( 'github', 'facebook', 'discord', 'add_calendar' ) as $network ) {
			set_theme_mod( 'hyperpress_social_' . $network, 'https://example.org/' . $payload );
		}
		// A link to this site is opened in the same tab, so both target values are exercised.
		set_theme_mod( 'hyperpress_social_youtube', get_site_url() . '/' . $payload );

		$html = $this->footer_html();

		$this->assertStringContainsString( 'fa-brands fa-github fa-inverse', $html, 'the social links are printed' );
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringNotContainsString( 'onerror=1>', $html, 'the payload is not printed as markup' );
	}

	public function test_the_link_targets_and_icons_are_plain_attribute_values(): void {
		set_theme_mod( 'hyperpress_social_github', 'https://github.com/hyper/repo' );
		set_theme_mod( 'hyperpress_social_youtube', get_site_url() . '/channel' );

		$html = $this->footer_html();

		$this->assertMatchesRegularExpression( '~target="_blank" rel="noopener noreferrer">\s*<i class="fa-brands fa-github fa-inverse"~', $html );
		$this->assertMatchesRegularExpression( '~target="_self">\s*<i class="fa-brands fa-youtube fa-inverse"~', $html );
	}
}
