<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class ContactTemplateTest extends WP_UnitTestCase {

	private const TEMPLATE = 'page-templates/page-contact.php';

	/** @var callable|null The hyperpress_map shortcode registered before the test, if any. */
	private $saved_map_shortcode = null;

	public function set_up(): void {
		parent::set_up();
		$this->saved_map_shortcode = $GLOBALS['shortcode_tags']['hyperpress_map'] ?? null;
		remove_shortcode( 'hyperpress_map' );
	}

	public function tear_down(): void {
		remove_shortcode( 'hyperpress_map' );
		if ( null !== $this->saved_map_shortcode ) {
			$GLOBALS['shortcode_tags']['hyperpress_map'] = $this->saved_map_shortcode;
		}
		parent::tear_down();
	}

	/** Render a page using the Contact template and return the HTML. */
	private function render_contact_page(): string {
		global $post; // The template loader includes templates in global scope, so $post is available there.
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Get in touch',
				'post_content' => 'Visit our office today.',
				'meta_input'   => array( '_wp_page_template' => self::TEMPLATE ),
			)
		);
		$this->go_to( get_permalink( $page_id ) );
		$template = locate_template( self::TEMPLATE );
		$this->assertNotSame( '', $template, 'The Contact template file exists.' );
		ob_start();
		include $template;
		$html = ob_get_clean();
		$this->assertStringNotContainsString( 'Fatal error', $html );
		return $html;
	}

	public function test_template_is_listed_as_contact(): void {
		$templates = wp_get_theme()->get_page_templates();

		$this->assertArrayHasKey( self::TEMPLATE, $templates );
		$this->assertSame( 'Contact', $templates[ self::TEMPLATE ] );
	}

	public function test_map_renders_between_the_banner_and_the_main_container(): void {
		add_shortcode(
			'hyperpress_map',
			static function () {
				return '<div class="stub-map"></div>';
			}
		);

		$html = $this->render_contact_page();

		$banner = strpos( $html, 'class="banner' );
		$map    = strpos( $html, 'stub-map' );
		$main   = strpos( $html, 'main-container' );
		$this->assertNotFalse( $banner, 'Banner is rendered.' );
		$this->assertNotFalse( $map, 'Map is rendered.' );
		$this->assertNotFalse( $main, 'Main container is rendered.' );
		$this->assertLessThan( $map, $banner );
		$this->assertLessThan( $main, $map );
		$this->assertStringContainsString( 'Visit our office today.', $html );
	}

	public function test_renders_without_the_map_shortcode(): void {
		$html = $this->render_contact_page();

		$this->assertStringNotContainsString( '[hyperpress_map]', $html );
		$this->assertStringNotContainsString( 'hyperpress_map', $html );
		$this->assertStringContainsString( 'Visit our office today.', $html );
	}
}
