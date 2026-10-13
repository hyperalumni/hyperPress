<?php
namespace HyperPress\ThemeTests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

/**
 * The front page settings moved to the hyperpress-frontpage plugin (Settings > Front Page), so the theme registers no
 * front page Customizer pieces any more.
 */
final class HomepageSettingsTest extends WP_UnitTestCase {

	private WP_Customize_Manager $manager;

	public function set_up(): void {
		parent::set_up();
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$this->manager = new WP_Customize_Manager();
		do_action( 'customize_register', $this->manager );
	}

	public function test_the_customizer_has_no_front_page_posts_section(): void {
		$this->assertNull( $this->manager->get_section( 'hyperpress_front_page_posts' ) );
		$this->assertNull( $this->manager->get_section( 'hyperpress_front_page' ) );
		$this->assertNull( $this->manager->get_section( 'hyperpress_homepage' ) );
		$this->assertNull( $this->manager->get_panel( 'hyperpress_front_page' ) );
	}

	public function test_no_section_is_titled_front_page(): void {
		foreach ( $this->manager->sections() as $section ) {
			$this->assertNotContains( $section->title, array( 'Front Page', 'Front Page Posts' ) );
		}
	}

	public function test_the_old_front_page_post_settings_are_not_registered(): void {
		foreach ( array( 'hyperpress_home_blog_categories', 'hyperpress_home_blog_post_types' ) as $id ) {
			$this->assertNull( $this->manager->get_setting( $id ), "$id is not a Customizer setting" );
			$this->assertNull( $this->manager->get_control( $id ), "$id has no Customizer control" );
		}
	}

	public function test_the_moved_sanitizers_are_gone_from_the_theme(): void {
		$this->assertFalse( function_exists( 'hyperpress_sanitize_home_categories' ) );
		$this->assertFalse( function_exists( 'hyperpress_sanitize_home_post_types' ) );
	}

	public function test_the_meta_boxes_have_no_front_page_box(): void {
		$ids = array_map(
			static fn( $box ) => (string) ( $box['id'] ?? '' ),
			apply_filters( 'rwmb_meta_boxes', array() )
		);
		$this->assertNotContains( 'hyperpress_front_page', $ids );
		$this->assertFalse( function_exists( 'hyperpress_homepage_customize_metabox' ) );
	}

	public function test_the_unused_banner_button_text_setting_is_not_registered(): void {
		$this->assertNull( $this->manager->get_setting( 'hyperpress_home_banner_button_text' ) );
		$this->assertNull( $this->manager->get_control( 'home_banner_button_text' ) );
	}
}
