<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * The footer prints the social links from the hyperpress-socials shared list.
 */
final class FooterSocialsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		if ( ! class_exists( '\HyperPress\Socials\Links' ) ) {
			$this->markTestSkipped( 'Needs the hyperpress-socials plugin' );
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

	public function test_an_external_link_opens_in_a_new_tab_with_noopener_noreferrer(): void {
		set_theme_mod( 'hyperpress_socials_github', 'https://github.com/hyper' );

		$html = $this->footer_html();

		$this->assertMatchesRegularExpression( '~href="https://github\.com/hyper"[^>]*target="_blank" rel="noopener noreferrer">~', $html );
	}

	public function test_a_lookalike_url_containing_the_site_url_is_still_external(): void {
		set_theme_mod( 'hyperpress_socials_github', 'https://evil.example/?r=' . get_site_url() );

		$html = $this->footer_html();

		$this->assertMatchesRegularExpression( '~href="https://evil\.example/[^"]*"[^>]*target="_blank" rel="noopener noreferrer">~', $html );
	}

	public function test_a_link_to_this_site_opens_in_the_same_tab_without_rel(): void {
		set_theme_mod( 'hyperpress_socials_github', get_site_url() . '/repo' );

		$html = $this->footer_html();

		$this->assertMatchesRegularExpression( '~target="_self">\s*<i class="fa-brands fa-github fa-inverse"~', $html );
		$this->assertStringNotContainsString( 'rel="noopener', $html );
	}

	public function test_a_draft_contact_page_prints_no_envelope_icon(): void {
		$page = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);
		set_theme_mod( 'hyperpress_socials_contact', $page );

		$html = $this->footer_html();

		$this->assertStringNotContainsString( 'fa-envelope', $html );
	}

	public function test_a_published_contact_page_links_to_its_permalink(): void {
		$page = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		set_theme_mod( 'hyperpress_socials_contact', $page );

		$html = $this->footer_html();

		$this->assertStringContainsString( 'href="' . esc_url( get_permalink( $page ) ) . '"', $html );
		$this->assertMatchesRegularExpression( '~target="_self">\s*<i class="fa-regular fa-envelope fa-inverse"~', $html );
	}

	public function test_the_links_follow_the_plugins_order_not_the_order_they_were_set(): void {
		set_theme_mod( 'hyperpress_socials_discord', 'https://discord.example/invite' );
		set_theme_mod( 'hyperpress_socials_facebook', 'https://facebook.example/page' );
		set_theme_mod( 'hyperpress_socials_github', 'https://github.example/repo' );

		$html = $this->footer_html();

		$github   = strpos( $html, 'fa-github' );
		$facebook = strpos( $html, 'fa-facebook-f' );
		$discord  = strpos( $html, 'fa-discord' );

		$this->assertNotFalse( $github );
		$this->assertNotFalse( $facebook );
		$this->assertNotFalse( $discord );
		$this->assertLessThan( $facebook, $github );
		$this->assertLessThan( $discord, $facebook );
	}

	public function test_the_list_is_printed_even_when_no_social_is_configured(): void {
		$html = $this->footer_html();

		$this->assertStringContainsString( '<ul class="menu simple">', $html );
		$this->assertStringNotContainsString( '<li>', $html );
	}
}
