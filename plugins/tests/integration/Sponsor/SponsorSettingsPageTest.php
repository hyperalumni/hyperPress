<?php
namespace HyperPress\Tests\Integration\Sponsor;

use HyperPress\Sponsor\Settings;
use HyperPress\Utils\Banner;
use WP_UnitTestCase;

final class SponsorSettingsPageTest extends WP_UnitTestCase {

	/**
	 * The Sponsor boxes from the Meta Box filter.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function boxes(): array {
		return array_values(
			array_filter(
				apply_filters( 'rwmb_meta_boxes', array() ),
				static fn( $box ) => 'hyperpress_sponsor_box' === ( $box['id'] ?? '' )
			)
		);
	}

	public function test_the_settings_page_is_registered_under_the_hyperpress_menu(): void {
		$pages = array_values(
			array_filter(
				apply_filters( 'mb_settings_pages', array() ),
				static fn( $page ) => 'hyperpress_sponsor' === ( $page['id'] ?? '' )
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_sponsor', $pages[0]['option_name'] );
		$this->assertSame( 'Sponsor', $pages[0]['menu_title'] );
		$this->assertSame( 'Sponsor', $pages[0]['page_title'] );
		$this->assertSame( 'hyperpress', $pages[0]['parent'] );
		$this->assertSame( 'manage_options', $pages[0]['capability'] );
		$this->assertSame( 'no-boxes', $pages[0]['style'] );
	}

	public function test_one_box_is_attached_to_the_settings_page(): void {
		$boxes = $this->boxes();

		$this->assertCount( 1, $boxes );
		$this->assertSame( 'hyperpress_sponsor', $boxes[0]['settings_pages'] );
	}

	public function test_the_box_has_the_subtitle_field(): void {
		$fields = $this->boxes()[0]['fields'];

		$this->assertCount( 1, $fields );
		$this->assertSame( 'hyperpress_sponsor_banner_subtitle', $fields[0]['id'] );
		$this->assertSame( 'Sponsor Banner Subtitle', $fields[0]['name'] );
		$this->assertSame( 'textarea', $fields[0]['type'] );
		$this->assertTrue( $fields[0]['raw'] );
		$this->assertSame( array( Banner::class, 'sanitize_subtitle' ), $fields[0]['sanitize_callback'] );
		// No `std`: Meta Box drops the key of a cleared field and would redisplay a `std`, so the
		// user could never clear the subtitle. The default comes from the default_option filter
		// (see SponsorSubtitleSettingTest: get_option() with no row returns Settings::defaults()).
		$this->assertArrayNotHasKey( 'std', $fields[0] );
	}

	public function test_the_unsaved_default_subtitle_comes_from_the_option_not_the_field(): void {
		delete_option( 'hyperpress_sponsor' );

		$this->assertSame( 'Helping Make HYPER Happen', get_option( 'hyperpress_sponsor' )[ Settings::BANNER_SUBTITLE ] );
	}

	public function test_the_sponsor_information_box_remains(): void {
		$ids = wp_list_pluck( apply_filters( 'rwmb_meta_boxes', array() ), 'id' );

		$this->assertContains( 'hyperpress_sponsor_information', $ids );
	}
}
