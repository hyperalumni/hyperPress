<?php
namespace HyperPress\Tests\Integration\Frontpage;

use HyperPress\Frontpage\Sanitizers;
use WP_UnitTestCase;

final class FrontpageSettingsPageTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unregister_post_type( 'hp_private' );
		parent::tear_down();
	}

	/**
	 * The Front Page boxes from the Meta Box filter.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function boxes(): array {
		return array_values(
			array_filter(
				apply_filters( 'rwmb_meta_boxes', array() ),
				static fn( $box ) => 'hyperpress_frontpage_box' === ( $box['id'] ?? '' )
			)
		);
	}

	/**
	 * Fields of the Front Page box keyed by id, in order.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function fields(): array {
		$fields = array();
		foreach ( $this->boxes()[0]['fields'] as $field ) {
			$fields[ $field['id'] ] = $field;
		}
		return $fields;
	}

	public function test_the_settings_page_is_registered_under_the_hyperpress_menu(): void {
		$pages = array_values(
			array_filter(
				apply_filters( 'mb_settings_pages', array() ),
				static fn( $page ) => 'hyperpress_frontpage' === ( $page['id'] ?? '' )
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_frontpage', $pages[0]['option_name'] );
		$this->assertSame( 'Front Page', $pages[0]['menu_title'] );
		$this->assertSame( 'Front Page', $pages[0]['page_title'] );
		$this->assertSame( 'hyperpress', $pages[0]['parent'] );
	}

	public function test_one_box_is_attached_to_the_settings_page(): void {
		$boxes = $this->boxes();

		$this->assertCount( 1, $boxes );
		$this->assertSame( 'hyperpress_frontpage', $boxes[0]['settings_pages'] );
	}

	public function test_the_box_has_the_eight_fields_in_order_with_their_types(): void {
		$this->assertSame(
			array(
				'hyperpress_frontpage_categories'                => 'select_advanced',
				'hyperpress_frontpage_post_types'                => 'select_advanced',
				'hyperpress_frontpage_banner_title'              => 'text',
				'hyperpress_frontpage_banner_subtitle'           => 'textarea',
				'hyperpress_frontpage_banner_background_color'   => 'color',
				'hyperpress_frontpage_banner_button_text'        => 'text',
				'hyperpress_frontpage_banner_button_page'        => 'post',
				'hyperpress_frontpage_countdowns'                => 'post',
			),
			array_map( static fn( $field ) => $field['type'], $this->fields() )
		);
	}

	public function test_multiple_flags_and_sanitize_callbacks(): void {
		$fields = $this->fields();

		$this->assertTrue( $fields['hyperpress_frontpage_categories']['multiple'] );
		$this->assertSame( array(), $fields['hyperpress_frontpage_categories']['std'] );
		$this->assertSame( array( Sanitizers::class, 'categories' ), $fields['hyperpress_frontpage_categories']['sanitize_callback'] );

		$this->assertTrue( $fields['hyperpress_frontpage_post_types']['multiple'] );
		$this->assertSame( array( 'post' ), $fields['hyperpress_frontpage_post_types']['std'] );
		$this->assertSame( array( Sanitizers::class, 'post_types' ), $fields['hyperpress_frontpage_post_types']['sanitize_callback'] );

		$this->assertTrue( $fields['hyperpress_frontpage_countdowns']['multiple'] );
		$this->assertSame( array( 'hyper_countdown' ), (array) $fields['hyperpress_frontpage_countdowns']['post_type'] );
		$this->assertSame( 'page', $fields['hyperpress_frontpage_banner_button_page']['post_type'] );
	}

	public function test_word_limits_and_raw_subtitle(): void {
		$fields = $this->fields();

		$this->assertSame( 4, $fields['hyperpress_frontpage_banner_title']['limit'] );
		$this->assertSame( 'word', $fields['hyperpress_frontpage_banner_title']['limit_type'] );
		$this->assertSame( 4, $fields['hyperpress_frontpage_banner_button_text']['limit'] );
		$this->assertSame( 100, $fields['hyperpress_frontpage_banner_subtitle']['limit'] );
		$this->assertTrue( $fields['hyperpress_frontpage_banner_subtitle']['raw'] );
		// No `std`: Meta Box drops the key of a cleared field and would redisplay a `std`, so the
		// user could never clear the subtitle. The default comes from the default_option filter
		// (see FrontpageSettingsTest).
		$this->assertArrayNotHasKey( 'std', $fields['hyperpress_frontpage_banner_subtitle'] );
	}

	public function test_the_post_type_options_list_public_types_only(): void {
		register_post_type( 'hp_private', array( 'public' => false ) );

		$options = $this->fields()['hyperpress_frontpage_post_types']['options'];

		$this->assertArrayHasKey( 'post', $options );
		$this->assertArrayNotHasKey( 'hp_private', $options );
	}

	public function test_the_category_options_list_categories_as_id_to_name(): void {
		$id = self::factory()->category->create( array( 'name' => 'Empty Cat' ) ); // No posts: empty terms are listed too.

		$options = $this->fields()['hyperpress_frontpage_categories']['options'];

		$this->assertSame( 'Empty Cat', $options[ $id ] );
		$this->assertContains( 'Uncategorized', $options );
		$this->assertArrayHasKey( (int) get_option( 'default_category' ), $options );
	}
}
