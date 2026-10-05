<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class HeaderMarkupTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		// Core still hooks the deprecated the_block_template_skip_link() on wp_body_open.
		remove_action( 'wp_body_open', 'the_block_template_skip_link' );
	}

	private function render_header(): string {
		$this->go_to( home_url( '/' ) );
		ob_start();
		include get_template_directory() . '/header.php'; // get_header() would require_once, so only the first render in a process would output.
		return ob_get_clean();
	}

	public function test_wp_body_open_fires_right_after_the_body_tag(): void {
		add_action(
			'wp_body_open',
			static function () {
				echo '<!--body-open-marker-->';
			}
		);
		$before = did_action( 'wp_body_open' );

		$html = $this->render_header();

		$this->assertSame( $before + 1, did_action( 'wp_body_open' ) );
		$this->assertMatchesRegularExpression( '/<body[^>]*>\s*<!--body-open-marker-->/', $html );
	}

	public function test_body_classes_are_on_the_body_not_on_html(): void {
		$html = $this->render_header();

		$this->assertMatchesRegularExpression( '/<html\b[^>]*>/', $html );
		preg_match( '/<html\b[^>]*>/', $html, $html_tag );
		$this->assertStringNotContainsString( 'class=', $html_tag[0], 'the <html> element must not carry body classes' );
		$this->assertStringContainsString( 'lang=', $html_tag[0], 'language_attributes() stays on <html>' );

		preg_match( '/<body\b[^>]*>/', $html, $body_tag );
		$this->assertStringContainsString( 'class="', $body_tag[0] );
		$this->assertMatchesRegularExpression( '/class="[^"]*\btopbar\b/', $body_tag[0], 'the mobile menu layout class (library/custom-nav.php) is on <body>' );
	}

	public function test_the_admin_bar_class_lands_on_the_body(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter( 'show_admin_bar', '__return_true' );
		_wp_admin_bar_init();

		preg_match( '/<body\b[^>]*>/', $this->render_header(), $body_tag );

		$this->assertMatchesRegularExpression( '/class="[^"]*\badmin-bar\b/', $body_tag[0] );
	}}
