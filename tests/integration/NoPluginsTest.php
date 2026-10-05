<?php
namespace HyperPress\ThemeTests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

/**
 * Run with HYPERPRESS_TEST_WITHOUT_PLUGINS=1 (no HYPER plugins, no Meta Box): the theme must still render.
 */
final class NoPluginsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		if ( ! getenv( 'HYPERPRESS_TEST_WITHOUT_PLUGINS' ) ) {
			$this->markTestSkipped( 'Run with HYPERPRESS_TEST_WITHOUT_PLUGINS=1' );
		}
	}

	private function render( string $url ): string {
		$this->go_to( $url );
		$map = array(
			'is_404'       => 'get_404_template',
			'is_search'    => 'get_search_template',
			'is_front_page' => 'get_front_page_template',
			'is_singular'  => 'get_singular_template',
			'is_archive'   => 'get_archive_template',
			'is_home'      => 'get_home_template',
		);
		$template = get_index_template();
		foreach ( $map as $check => $getter ) {
			if ( $check() && $getter() ) {
				$template = $getter();
				break;
			}
		}
		ob_start();
		include apply_filters( 'template_include', $template );
		$html = (string) ob_get_clean();
		$this->assertNotSame( '', trim( $html ), "$url rendered" );
		return $html;
	}

	public function test_the_plugins_really_are_absent(): void {
		$this->assertFalse( function_exists( 'rwmb_meta' ) );
		$this->assertFalse( class_exists( 'HyperPress\Season\SortedSeasons' ) );
		$this->assertFalse( class_exists( 'HyperPress\Utils\Controls\PostTypeDropdownControl' ) );
	}

	public function test_a_page_renders(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'About us' ) );
		$this->assertStringContainsString( 'About us', $this->render( get_permalink( $id ) ) );
	}

	public function test_a_post_renders(): void {
		$id = self::factory()->post->create( array( 'post_title' => 'Plain post' ) );
		$this->assertStringContainsString( 'Plain post', $this->render( get_permalink( $id ) ) );
	}

	public function test_the_blog_page_renders(): void {
		$blog = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'The blog' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $blog );
		self::factory()->post->create( array( 'post_title' => 'Listed post' ) );
		$this->render( get_permalink( $blog ) );
	}

	public function test_an_archive_renders(): void {
		$cat = self::factory()->category->create( array( 'name' => 'News' ) );
		$id  = self::factory()->post->create();
		wp_set_post_categories( $id, array( $cat ) );
		$this->render( get_category_link( $cat ) );
	}

	public function test_the_front_page_renders(): void {
		self::factory()->post->create();
		$this->render( home_url( '/' ) );
	}

	public function test_the_customizer_loads(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );
		// The homepage section itself comes from Meta Box, so only the settings are expected here.
		$this->assertNotNull( $manager->get_setting( 'hyperpress_home_blog_post_types' ) );
		$this->assertNull( $manager->get_control( 'hyperpress_home_blog_post_types' ), 'no control without hyperpress-utils' );
	}
}
