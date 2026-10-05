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

	public function test_the_homepage_section_is_registered(): void {
		$section = $this->manager->get_section( 'hyperpress_homepage' );
		$this->assertNotNull( $section );
		$this->assertSame( 'Homepage', $section->title );
	}

	public function test_the_banner_button_text_setting_is_registered_and_sanitized(): void {
		$this->assertSame( 'Go', $this->sanitize( 'hyperpress_home_banner_button_text', '<b>Go</b><script>x</script>' ) );
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
