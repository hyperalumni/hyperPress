<?php
namespace HyperPress\Tests\Integration\Program;

use HyperPress\Program\Sanitizers;
use WP_UnitTestCase;

final class ProgramSettingsPageTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unregister_post_type( 'hp_public_type' );
		unregister_post_type( 'hp_private_type' );
		\HyperPress\Program\Plugin::register();
		parent::tear_down();
	}

	/**
	 * The Program boxes from the Meta Box filter.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function boxes(): array {
		return array_values(
			array_filter(
				apply_filters( 'rwmb_meta_boxes', array() ),
				static fn( $box ) => 'hyperpress_program_box' === ( $box['id'] ?? '' )
			)
		);
	}

	/**
	 * The one field of the Program box.
	 *
	 * @return array<string,mixed>
	 */
	private function field(): array {
		$fields = $this->boxes()[0]['fields'];
		$this->assertCount( 1, $fields );
		return $fields[0];
	}

	public function test_the_settings_page_is_registered_under_the_hyperpress_menu(): void {
		$pages = array_values(
			array_filter(
				apply_filters( 'mb_settings_pages', array() ),
				static fn( $page ) => 'hyperpress_program' === ( $page['id'] ?? '' )
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_program', $pages[0]['option_name'] );
		$this->assertSame( 'Program', $pages[0]['menu_title'] );
		$this->assertSame( 'Program', $pages[0]['page_title'] );
		$this->assertSame( 'hyperpress', $pages[0]['parent'] );
		$this->assertSame( 'manage_options', $pages[0]['capability'] );
		$this->assertSame( 'no-boxes', $pages[0]['style'] );
	}

	public function test_one_box_is_attached_to_the_settings_page(): void {
		$boxes = $this->boxes();

		$this->assertCount( 1, $boxes );
		$this->assertSame( 'hyperpress_program', $boxes[0]['settings_pages'] );
	}

	public function test_the_field_is_a_multiple_select_with_the_sanitizer(): void {
		$field = $this->field();

		$this->assertSame( 'hyperpress_program_post_types', $field['id'] );
		$this->assertSame( 'Programs Post Types', $field['name'] );
		$this->assertSame( 'Select the Post Types that will use the Program Taxonomy', $field['desc'] );
		$this->assertSame( 'select_advanced', $field['type'] );
		$this->assertTrue( $field['multiple'] );
		// No std: Meta Box redisplays std for an empty or missing value, so the field could never be cleared. The default comes from default_option_hyperpress_program.
		$this->assertArrayNotHasKey( 'std', $field );
		$this->assertSame( array( Sanitizers::class, 'post_types' ), $field['sanitize_callback'] );
	}

	public function test_the_post_type_options_list_public_types_only(): void {
		register_post_type(
			'hp_public_type',
			array(
				'public' => true,
				'label'  => 'Public Type',
			)
		);
		register_post_type(
			'hp_private_type',
			array(
				'public'  => false,
				'show_ui' => true,
				'label'   => 'Private Type',
			)
		);

		$options = $this->field()['options'];

		$this->assertArrayHasKey( 'post', $options );
		$this->assertSame( 'Public Type', $options['hp_public_type'] );
		$this->assertArrayNotHasKey( 'hp_private_type', $options );
	}

	public function test_the_box_is_built_when_the_program_taxonomy_is_not_registered(): void {
		unregister_taxonomy( 'hyper_program' );
		$this->assertFalse( taxonomy_exists( 'hyper_program' ) );

		$boxes = \HyperPress\Program\MetaBoxes::boxes( array() );

		$this->assertCount( 1, $boxes );
		$this->assertSame( 'hyperpress_program_box', $boxes[0]['id'] );
		$field = $boxes[0]['fields'][0];
		$this->assertSame( 'hyperpress_program_post_types', $field['id'] );
		$this->assertArrayHasKey( 'post', $field['options'] );
	}
}
