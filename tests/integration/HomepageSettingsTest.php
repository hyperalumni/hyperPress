<?php
namespace HyperPress\ThemeTests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class HomepageSettingsTest extends WP_UnitTestCase {

	private WP_Customize_Manager $manager;

	public function set_up(): void {
		parent::set_up();
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$this->manager = new WP_Customize_Manager();
		do_action( 'customize_register', $this->manager );
	}

	private function sanitize( string $id, $value ) {
		$setting = $this->manager->get_setting( $id );
		$this->assertNotNull( $setting, "$id is registered" );
		return $setting->sanitize( $value );
	}

	public function test_the_native_front_page_posts_section_is_registered(): void {
		$section = $this->manager->get_section( 'hyperpress_front_page_posts' );
		$this->assertNotNull( $section );
		$this->assertSame( 'Front Page Posts', $section->title );
		$this->assertSame( 132, $section->priority );
		$this->assertSame( 'hyperpress_front_page_posts', $this->manager->get_control( 'hyperpress_home_blog_categories' )->section );
		$this->assertSame( 'hyperpress_front_page_posts', $this->manager->get_control( 'hyperpress_home_blog_post_types' )->section );
		$this->assertNull( $this->manager->get_section( 'hyperpress_homepage' ) );
	}

	public function test_the_meta_box_front_page_entry_has_its_own_section_id(): void {
		$entries = hyperpress_homepage_customize_metabox( array() );
		$this->assertCount( 1, $entries );
		$entry = $entries[0];
		$this->assertSame( 'hyperpress_front_page', $entry['id'] );
		$this->assertSame( 'Front Page', $entry['title'] );
		$this->assertSame( 131, $entry['priority'] );
		$this->assertArrayHasKey( 'panel', $entry );
		$this->assertArrayNotHasKey( $entry['id'], $this->manager->sections() );
	}

	public function test_the_unused_banner_button_text_setting_is_not_registered(): void {
		$this->assertNull( $this->manager->get_setting( 'hyperpress_home_banner_button_text' ) );
		$this->assertNull( $this->manager->get_control( 'home_banner_button_text' ) );
	}

	public function test_post_types_drop_non_public_and_unknown_types(): void {
		register_post_type( 'hp_private', array( 'public' => false ) );
		register_post_type( 'hp_public', array( 'public' => true ) );

		$clean = $this->sanitize( 'hyperpress_home_blog_post_types', array( 'post', 'hp_public', 'hp_private', 'nope', 'x"y' ) );

		$this->assertSame( array( 'post', 'hp_public' ), array_values( $clean ) );

		unregister_post_type( 'hp_private' );
		unregister_post_type( 'hp_public' );
	}

	public function test_post_types_from_a_non_array_value_become_empty(): void {
		$this->assertSame( array(), $this->sanitize( 'hyperpress_home_blog_post_types', 'post' ) );
	}

	public function test_a_category_that_does_not_exist_becomes_zero(): void {
		$this->assertSame( 0, $this->sanitize( 'hyperpress_home_blog_categories', '999999' ) );
	}

	public function test_an_existing_category_is_kept(): void {
		$id = self::factory()->category->create();
		$this->assertSame( $id, $this->sanitize( 'hyperpress_home_blog_categories', (string) $id ) );
	}

	public function test_a_category_list_keeps_only_existing_ids(): void {
		$id = self::factory()->category->create();
		$this->assertSame( array( $id ), array_values( $this->sanitize( 'hyperpress_home_blog_categories', array( (string) $id, '999999', 'abc' ) ) ) );
	}
}
