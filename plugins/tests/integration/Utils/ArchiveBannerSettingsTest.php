<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\AdminMenu;
use HyperPress\Utils\ArchiveBannerSettings;
use HyperPress\Utils\Banner;
use WP_UnitTestCase;

final class ArchiveBannerSettingsTest extends WP_UnitTestCase {

	private const OPTION  = 'hyperpress_test_archive';
	private const KEY     = 'hyperpress_test_archive_banner_subtitle';
	private const DEFAULT = 'Test default subtitle';
	private const IMAGE   = 'hyperpress_test_archive_banner_image';

	public function tear_down(): void {
		remove_all_filters( 'default_option_' . self::OPTION );
		delete_option( self::OPTION );
		parent::tear_down();
	}

	private function settings(): ArchiveBannerSettings {
		return new ArchiveBannerSettings( self::OPTION, self::KEY, self::DEFAULT, self::IMAGE );
	}

	private function image(): int {
		return (int) self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.jpg' );
	}

	public function test_defaults_are_keyed_by_the_subtitle_key(): void {
		$this->assertSame( array( self::KEY => self::DEFAULT ), $this->settings()->defaults() );
	}

	public function test_default_option_returns_the_defaults_unless_the_caller_passed_one(): void {
		$settings = $this->settings();

		$this->assertSame( array( self::KEY => self::DEFAULT ), $settings->default_option( false, self::OPTION, false ) );
		$this->assertSame( 'mine', $settings->default_option( 'mine', self::OPTION, true ) );
	}

	public function test_banner_subtitle_is_the_default_when_the_option_row_is_absent(): void {
		$settings = $this->settings();
		add_filter( 'default_option_' . self::OPTION, array( $settings, 'default_option' ), 10, 3 );
		delete_option( self::OPTION );

		$this->assertSame( self::DEFAULT, $settings->banner_subtitle() );
	}

	public function test_banner_subtitle_is_the_default_when_the_option_is_not_an_array(): void {
		update_option( self::OPTION, 'junk' );

		$this->assertSame( self::DEFAULT, $this->settings()->banner_subtitle() );
	}

	public function test_banner_subtitle_is_empty_when_the_row_exists_without_the_key(): void {
		update_option( self::OPTION, array() );

		$this->assertSame( '', $this->settings()->banner_subtitle() );
	}

	public function test_banner_subtitle_is_the_stored_string(): void {
		update_option( self::OPTION, array( self::KEY => 'Stored' ) );

		$this->assertSame( 'Stored', $this->settings()->banner_subtitle() );
	}

	public function test_banner_subtitle_is_the_default_when_the_stored_value_is_not_a_string(): void {
		update_option( self::OPTION, array( self::KEY => array( 'x' ) ) );

		$this->assertSame( self::DEFAULT, $this->settings()->banner_subtitle() );
	}

	public function test_page_builds_the_settings_page_entry(): void {
		$page = ArchiveBannerSettings::page( 'my_page', self::OPTION, 'My Title' );

		$this->assertSame(
			array(
				'id'          => 'my_page',
				'option_name' => self::OPTION,
				'menu_title'  => 'My Title',
				'page_title'  => 'My Title',
				'parent'      => AdminMenu::SLUG,
				'capability'  => 'manage_options',
				'style'       => 'no-boxes',
			),
			$page
		);
	}

	public function test_subtitle_field_omits_std_by_default(): void {
		$field = $this->settings()->subtitle_field( 'Name', 'Desc' );

		$this->assertSame( self::KEY, $field['id'] );
		$this->assertSame( 'Name', $field['name'] );
		$this->assertSame( 'Desc', $field['desc'] );
		$this->assertSame( 'textarea', $field['type'] );
		$this->assertTrue( $field['raw'] );
		$this->assertSame( array( Banner::class, 'sanitize_subtitle' ), $field['sanitize_callback'] );
		$this->assertArrayNotHasKey( 'std', $field );
	}

	public function test_subtitle_field_carries_std_when_asked(): void {
		$field = $this->settings()->subtitle_field( 'Name', 'Desc', true );

		$this->assertSame( self::DEFAULT, $field['std'] );
	}

	public function test_image_id_is_the_stored_image_attachment(): void {
		$id = $this->image();
		update_option( self::OPTION, array( self::IMAGE => $id ) );

		$this->assertSame( $id, $this->settings()->image_id() );
	}

	public function test_image_id_is_zero_for_a_deleted_attachment(): void {
		$id = $this->image();
		update_option( self::OPTION, array( self::IMAGE => $id ) );
		wp_delete_attachment( $id, true );

		$this->assertSame( 0, $this->settings()->image_id() );
	}

	public function test_image_id_is_zero_when_the_row_exists_without_the_key(): void {
		update_option( self::OPTION, array( self::KEY => 'x' ) );

		$this->assertSame( 0, $this->settings()->image_id() );
	}

	public function test_image_id_is_zero_when_the_option_row_is_missing(): void {
		delete_option( self::OPTION );

		$this->assertSame( 0, $this->settings()->image_id() );
	}

	public function test_image_id_is_zero_when_the_option_is_not_an_array(): void {
		update_option( self::OPTION, 'junk' );

		$this->assertSame( 0, $this->settings()->image_id() );
	}

	public function test_image_id_is_zero_without_an_image_key(): void {
		update_option( self::OPTION, array( self::IMAGE => $this->image() ) );

		$this->assertSame( 0, ( new ArchiveBannerSettings( self::OPTION, self::KEY, self::DEFAULT ) )->image_id() );
	}

	public function test_image_field_is_a_single_image_field_with_the_image_key(): void {
		$field = $this->settings()->image_field( 'Name', 'Desc' );

		$this->assertSame( self::IMAGE, $field['id'] );
		$this->assertSame( 'Name', $field['name'] );
		$this->assertSame( 'Desc', $field['desc'] );
		$this->assertSame( 'single_image', $field['type'] );
	}
}
