<?php
namespace HyperPress\Tests\Integration\Label;

use HyperPress\Label\MetaBoxes;
use HyperPress\Label\Registry;
use HyperPress\Label\Resolver;
use WP_UnitTestCase;

final class LabelSettingsPageTest extends WP_UnitTestCase {

	/**
	 * A callable that returns nothing useful.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function noop(): array {
		return array();
	}

	public function set_up(): void {
		parent::set_up();
		Registry::reset();
	}

	public function tear_down(): void {
		Registry::reset();
		delete_option( 'hyperpress_label' );
		if ( post_type_exists( 'hyper_pub' ) ) {
			unregister_post_type( 'hyper_pub' );
		}
		parent::tear_down();
	}

	/**
	 * The Labels box from the Meta Box filter.
	 *
	 * @return array<string,mixed>
	 */
	private function box(): array {
		$boxes = array_values(
			array_filter(
				apply_filters( 'rwmb_meta_boxes', array() ),
				static fn( $box ) => 'hyperpress_label_box' === ( $box['id'] ?? '' )
			)
		);
		$this->assertCount( 1, $boxes );
		return $boxes[0];
	}

	/**
	 * A field of the box by id.
	 *
	 * @param string $id Field id.
	 * @return array<string,mixed>|null
	 */
	private function field( string $id ): ?array {
		foreach ( $this->box()['fields'] as $field ) {
			if ( $id === $field['id'] ) {
				return $field;
			}
		}
		return null;
	}

	public function test_the_settings_page_is_registered_under_the_hyperpress_menu(): void {
		$pages = array_values(
			array_filter(
				apply_filters( 'mb_settings_pages', array() ),
				static fn( $page ) => 'hyperpress_label' === ( $page['id'] ?? '' )
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_label', $pages[0]['option_name'] );
		$this->assertSame( 'Labels', $pages[0]['menu_title'] );
		$this->assertSame( 'Labels', $pages[0]['page_title'] );
		$this->assertSame( 'hyperpress', $pages[0]['parent'] );
		$this->assertSame( 'manage_options', $pages[0]['capability'] );
		$this->assertSame( 'no-boxes', $pages[0]['style'] );
	}

	public function test_the_box_is_attached_to_the_settings_page(): void {
		$this->assertSame( 'hyperpress_label', $this->box()['settings_pages'] );
		$this->assertSame( 'Labels', $this->box()['title'] );
	}

	public function test_each_post_type_has_an_enabled_checkbox_then_a_labels_list_and_the_marker_comes_last(): void {
		$ids = wp_list_pluck( $this->box()['fields'], 'id' );

		foreach ( array( 'post', 'page' ) as $type ) {
			$enabled = array_search( Resolver::enabled_key( $type ), $ids, true );
			$this->assertIsInt( $enabled, $type );
			$this->assertSame( Resolver::labels_key( $type ), $ids[ $enabled + 1 ], $type );
		}
		$this->assertSame( 'hyperpress_label_saved', end( $ids ) );
		$this->assertNotContains( Resolver::enabled_key( 'attachment' ), $ids );
		$this->assertNotContains( Resolver::labels_key( 'attachment' ), $ids );
	}

	public function test_the_enabled_field_is_a_checkbox_with_the_resolver_default_and_boolean_sanitizer(): void {
		foreach ( array( 'post', 'page' ) as $type ) {
			$field = $this->field( Resolver::enabled_key( $type ) );

			$this->assertIsArray( $field, $type );
			$this->assertSame( 'checkbox', $field['type'] );
			$this->assertSame( Resolver::default_enabled( $type ), $field['std'] );
			$this->assertSame( 'rest_sanitize_boolean', $field['sanitize_callback'] );
		}
		$this->assertSame( 'Show labels on Posts', $this->field( Resolver::enabled_key( 'post' ) )['name'] );
		$this->assertSame( 'Show labels on Pages', $this->field( Resolver::enabled_key( 'page' ) )['name'] );
		$this->assertTrue( $this->field( Resolver::enabled_key( 'post' ) )['std'] );
		$this->assertFalse( $this->field( Resolver::enabled_key( 'page' ) )['std'] );
	}

	public function test_the_labels_field_is_a_multiple_select_with_defaults_description_and_sanitizer(): void {
		$field = $this->field( Resolver::labels_key( 'post' ) );

		$this->assertIsArray( $field );
		$this->assertSame( 'Labels on Posts', $field['name'] );
		$this->assertSame( 'select_advanced', $field['type'] );
		$this->assertTrue( $field['multiple'] );
		$this->assertSame( Resolver::default_labels( 'post' ), $field['std'] );
		$this->assertContains( 'post.date', $field['std'] );
		$this->assertSame(
			'Labels selected here appear on this post type. Select none to show no labels.',
			$field['desc'] ?? $field['description'] ?? ''
		);
		$this->assertSame( array( \HyperPress\Label\Settings::class, 'sanitize_labels' ), $field['sanitize_callback'] );
		$this->assertSame( array(), $this->field( Resolver::labels_key( 'page' ) )['std'] );
	}

	public function test_the_label_options_are_group_and_title_in_registry_order(): void {
		Registry::instance()->add(
			'x.one',
			array(
				'group'    => 'G<b>',
				'title'    => 'T&1',
				'callback' => array( self::class, 'noop' ),
			)
		);
		$expected = array();
		foreach ( Registry::instance()->all() as $id => $label ) {
			$expected[ $id ] = $label['group'] . ': ' . $label['title'];
		}

		$options = $this->field( Resolver::labels_key( 'post' ) )['options'];

		$this->assertSame( $expected, $options );
		$this->assertSame( 'G<b>: T&1', $options['x.one'] );
		$this->assertArrayHasKey( 'post.date', $options );
		$this->assertSame( $options, $this->field( Resolver::labels_key( 'page' ) )['options'] );
	}

	public function test_stale_saved_ids_do_not_break_page_building(): void {
		update_option( 'hyperpress_label', array( Resolver::labels_key( 'post' ) => array( 'gone' ) ) );

		$field = $this->field( Resolver::labels_key( 'post' ) );

		$this->assertIsArray( $field );
		$this->assertArrayNotHasKey( 'gone', $field['options'] );
	}

	public function test_a_post_type_named_only_in_label_defaults_gets_fields(): void {
		Registry::reset( true );
		Registry::instance()->add(
			'x.one',
			array(
				'group'              => 'X',
				'title'              => 'One',
				'callback'           => array( self::class, 'noop' ),
				'default_post_types' => array( 'hyper_x' ),
			)
		);

		$enabled = $this->field( Resolver::enabled_key( 'hyper_x' ) );
		$labels  = $this->field( Resolver::labels_key( 'hyper_x' ) );

		$this->assertIsArray( $enabled );
		$this->assertIsArray( $labels );
		$this->assertSame( 'Show labels on hyper_x', $enabled['name'] );
		$this->assertTrue( $enabled['std'] );
		$this->assertSame( array( 'x.one' ), $labels['std'] );
		$this->assertSame( array( 'x.one' => 'X: One' ), $labels['options'] );
	}

	public function test_a_public_custom_post_type_gets_fields_with_its_plural_name(): void {
		register_post_type(
			'hyper_pub',
			array(
				'public' => true,
				'labels' => array( 'name' => 'Pubs' ),
			)
		);

		$field = $this->field( Resolver::enabled_key( 'hyper_pub' ) );

		$this->assertIsArray( $field );
		$this->assertSame( 'Show labels on Pubs', $field['name'] );
	}

	public function test_with_no_labels_registered_the_label_fields_still_build_with_empty_options(): void {
		Registry::reset( true );

		$boxes = MetaBoxes::boxes( array() );
		$field = null;
		foreach ( $boxes[0]['fields'] as $candidate ) {
			if ( Resolver::labels_key( 'post' ) === $candidate['id'] ) {
				$field = $candidate;
			}
		}

		$this->assertIsArray( $field );
		$this->assertSame( array(), $field['options'] );
	}

	public function test_the_hidden_marker_field_defaults_to_one(): void {
		$field = $this->field( 'hyperpress_label_saved' );

		$this->assertIsArray( $field );
		$this->assertSame( 'hidden', $field['type'] );
		$this->assertSame( '1', $field['std'] );
	}
}
