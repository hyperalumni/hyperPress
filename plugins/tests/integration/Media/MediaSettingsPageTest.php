<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\Settings;
use WP_UnitTestCase;

final class MediaSettingsPageTest extends WP_UnitTestCase {

	/**
	 * The Media boxes from the Meta Box filter.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function boxes(): array {
		return array_values(
			array_filter(
				apply_filters( 'rwmb_meta_boxes', array() ),
				static fn( $box ) => 'hyperpress_media_box' === ( $box['id'] ?? '' )
			)
		);
	}

	public function test_the_settings_page_is_registered_under_the_hyperpress_menu(): void {
		$pages = array_values(
			array_filter(
				apply_filters( 'mb_settings_pages', array() ),
				static fn( $page ) => 'hyperpress_media' === ( $page['id'] ?? '' )
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_media', $pages[0]['option_name'] );
		$this->assertSame( 'Media', $pages[0]['menu_title'] );
		$this->assertSame( 'Media', $pages[0]['page_title'] );
		$this->assertSame( 'hyperpress', $pages[0]['parent'] );
		$this->assertSame( 'manage_options', $pages[0]['capability'] );
		$this->assertSame( 'no-boxes', $pages[0]['style'] );
	}

	public function test_one_box_with_one_field_is_attached_to_the_settings_page(): void {
		$boxes = $this->boxes();

		$this->assertCount( 1, $boxes );
		$this->assertSame( 'hyperpress_media', $boxes[0]['settings_pages'] );
		$this->assertSame( 'NextGen Gallery', $boxes[0]['title'] );
		$this->assertSame( array( 'hyperpress_media_photos_page' ), wp_list_pluck( $boxes[0]['fields'], 'id' ) );
		$this->assertSame( Settings::PHOTOS_PAGE, $boxes[0]['fields'][0]['id'] );
	}

	public function test_the_photos_page_field_is_a_single_page_picker_with_the_old_label_and_description(): void {
		$field = $this->boxes()[0]['fields'][0];

		$this->assertSame( 'Page Displaying Photos', $field['name'] );
		$this->assertSame( 'For breadcrumb generation', $field['desc'] );
		$this->assertSame( 'post', $field['type'] );
		$this->assertSame( 'page', $field['post_type'] );
		$this->assertSame( 'select_advanced', $field['field_type'] );
		$this->assertEmpty( $field['multiple'] ?? false );
		$this->assertSame( 'Select a page', $field['placeholder'] );
		$this->assertSame(
			array(
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			),
			$field['query_args']
		);
		$this->assertSame( 'absint', $field['sanitize_callback'] );
	}

	public function test_the_callback_gives_an_absolute_integer_and_zero_for_junk(): void {
		$callback = $this->boxes()[0]['fields'][0]['sanitize_callback'];

		$this->assertSame( 12, call_user_func( $callback, '12abc' ) );
		$this->assertSame( 3, call_user_func( $callback, -3 ) );
		$this->assertSame( 0, call_user_func( $callback, 'junk' ) );
	}
}
