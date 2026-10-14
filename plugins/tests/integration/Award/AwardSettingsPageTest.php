<?php
namespace HyperPress\Tests\Integration\Award;

use HyperPress\Award\Settings;
use HyperPress\Utils\Banner;
use WP_UnitTestCase;

final class AwardSettingsPageTest extends WP_UnitTestCase {

	/**
	 * The Award settings boxes from the Meta Box filter.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function boxes(): array {
		return array_values(
			array_filter(
				apply_filters( 'rwmb_meta_boxes', array() ),
				static fn( $box ) => 'hyperpress_award_box' === ( $box['id'] ?? '' )
			)
		);
	}

	public function test_the_settings_page_is_registered_under_the_hyperpress_menu(): void {
		$pages = array_values(
			array_filter(
				apply_filters( 'mb_settings_pages', array() ),
				static fn( $page ) => 'hyperpress_award' === ( $page['id'] ?? '' )
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_award', $pages[0]['option_name'] );
		$this->assertSame( 'Award', $pages[0]['menu_title'] );
		$this->assertSame( 'Award', $pages[0]['page_title'] );
		$this->assertSame( 'hyperpress', $pages[0]['parent'] );
		$this->assertSame( 'manage_options', $pages[0]['capability'] );
		$this->assertSame( 'no-boxes', $pages[0]['style'] );
	}

	public function test_one_box_with_the_subtitle_field_is_attached_to_the_settings_page(): void {
		$boxes = $this->boxes();

		$this->assertCount( 1, $boxes );
		$this->assertSame( 'hyperpress_award', $boxes[0]['settings_pages'] );
		$fields = $boxes[0]['fields'];
		$this->assertCount( 1, $fields );
		$this->assertSame( 'hyperpress_award_banner_subtitle', $fields[0]['id'] );
		$this->assertSame( 'Banner subtitle', $fields[0]['name'] );
		$this->assertSame( 'textarea', $fields[0]['type'] );
		$this->assertTrue( $fields[0]['raw'] );
		$this->assertSame( array( Banner::class, 'sanitize_subtitle' ), $fields[0]['sanitize_callback'] );
		$this->assertSame( '', $fields[0]['std'] );
		$this->assertSame( Settings::defaults()[ Settings::BANNER_SUBTITLE ], $fields[0]['std'] );
	}
}
