<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * The footer prints the socials through the "Footer socials" widget area, and falls back to the inline list of
 * the hyperpress-socials shared list when the area is empty.
 */
final class FooterSocialsTest extends WP_UnitTestCase {

	private const WIDGET_ID = 'hyperpress_socials-2';

	/**
	 * @var array<string, mixed>
	 */
	private array $saved_options = array();

	/**
	 * @var mixed
	 */
	private $saved_sidebars_cache;

	public function set_up(): void {
		parent::set_up();
		global $_wp_sidebars_widgets;
		$this->saved_sidebars_cache = $_wp_sidebars_widgets;

		$this->saved_options = array(
			'sidebars_widgets'          => get_option( 'sidebars_widgets' ),
			'widget_hyperpress_socials' => get_option( 'widget_hyperpress_socials' ),
		);

		if ( ! class_exists( '\HyperPress\Socials\Links' ) ) {
			$this->markTestSkipped( 'Needs the hyperpress-socials plugin' );
		}
	}

	public function tear_down(): void {
		global $_wp_sidebars_widgets;
		$_wp_sidebars_widgets = $this->saved_sidebars_cache;

		foreach ( $this->saved_options as $name => $value ) {
			if ( false === $value ) {
				delete_option( $name );
			} else {
				update_option( $name, $value );
			}
		}
		wp_unregister_sidebar_widget( self::WIDGET_ID );

		parent::tear_down();
	}

	private function footer_html(): string {
		$this->go_to( home_url( '/' ) );
		// WordPress core still hooks the deprecated the_block_template_skip_link() on wp_footer, which trips the test case's deprecation check.
		remove_action( 'wp_footer', 'the_block_template_skip_link' );

		ob_start();
		include get_template_directory() . '/footer.php';
		return (string) ob_get_clean();
	}

	private function place_the_socials_widget_in_the_footer_area( string $layout ): void {
		update_option(
			'widget_hyperpress_socials',
			array(
				2              => array(
					'title'  => '',
					'layout' => $layout,
				),
				'_multiwidget' => 1,
			)
		);
		$sidebars = array(
			'wp_inactive_widgets' => array(),
			'footer-socials'      => array( self::WIDGET_ID ),
		);
		update_option( 'sidebars_widgets', $sidebars );
		// On the front end WordPress reads the sidebars from this cache, not from the option.
		global $_wp_sidebars_widgets;
		$_wp_sidebars_widgets = $sidebars;

		// Widget instances are registered from the option when widgets_init runs, which happened before this test.
		global $wp_widget_factory;
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( 'hyperpress_socials' === $widget->id_base ) {
				$widget->_register();
			}
		}
	}

	public function test_the_footer_socials_area_is_registered_without_wrapper_markup(): void {
		$sidebar = wp_get_sidebar( 'footer-socials' );

		$this->assertIsArray( $sidebar );
		$this->assertSame( 'Footer socials', $sidebar['name'] );
		$this->assertSame( '', $sidebar['before_widget'] );
		$this->assertSame( '', $sidebar['after_widget'] );
		$this->assertSame( '', $sidebar['before_title'] );
		$this->assertSame( '', $sidebar['after_title'] );
	}

	public function test_an_empty_area_prints_the_default_inline_list(): void {
		$page = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		set_theme_mod( 'hyperpress_socials_github', 'https://github.com/hyper' );
		set_theme_mod( 'hyperpress_socials_youtube', get_site_url() . '/channel' );
		set_theme_mod( 'hyperpress_socials_contact', $page );

		$html = $this->footer_html();

		$expected = '<section class="cell shrink"><ul class="menu simple">'
			. '<li><a href="https://github.com/hyper" title="GitHub" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-github fa-inverse" aria-hidden="true"></i></a></li>'
			. '<li><a href="' . esc_url( get_site_url() . '/channel' ) . '" title="YouTube" target="_self"><i class="fa-brands fa-youtube fa-inverse" aria-hidden="true"></i></a></li>'
			. '<li><a href="' . esc_url( get_permalink( $page ) ) . '" title="Contact Us" target="_self"><i class="fa-regular fa-envelope fa-inverse" aria-hidden="true"></i></a></li>'
			. '</ul></section>';
		$this->assertStringContainsString( $expected, preg_replace( '~>\s+<~', '><', $html ) );
	}

	public function test_a_socials_widget_in_the_area_replaces_the_default_list(): void {
		set_theme_mod( 'hyperpress_socials_github', 'https://github.com/hyper' );
		$this->place_the_socials_widget_in_the_footer_area( 'grid' );

		$html = $this->footer_html();

		$this->assertStringContainsString( 'fa-stack', $html );
		$this->assertStringNotContainsString( '<ul class="menu simple">', $html );
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
