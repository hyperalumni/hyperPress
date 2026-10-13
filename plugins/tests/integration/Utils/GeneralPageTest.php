<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\AdminMenu;
use HyperPress\Utils\GeneralPage;
use WP_UnitTestCase;

final class GeneralPageTest extends WP_UnitTestCase {

	public function test_pages_registers_general_under_the_hyperpress_menu(): void {
		$pages = GeneralPage::pages( array() );

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_general', $pages[0]['id'] );
		$this->assertSame( 'hyperpress_general', $pages[0]['option_name'] );
		$this->assertSame( AdminMenu::SLUG, $pages[0]['parent'] );
		$this->assertSame( 'General', $pages[0]['menu_title'] );
		$this->assertSame( 'General', $pages[0]['page_title'] );
		$this->assertSame( 'manage_options', $pages[0]['capability'] );
	}

	public function test_boxes_registers_one_text_field_for_the_key(): void {
		$boxes = GeneralPage::boxes( array() );

		$this->assertCount( 1, $boxes );
		$this->assertSame( 'hyperpress_general', $boxes[0]['settings_pages'] );
		$this->assertCount( 1, $boxes[0]['fields'] );
		$field = $boxes[0]['fields'][0];
		$this->assertSame( 'hyperpress_general_google_maps_api_key', $field['id'] );
		$this->assertSame( 'text', $field['type'] );
		$this->assertSame( 'sanitize_text_field', $field['sanitize_callback'] );
	}

	public function test_register_adds_both_filters(): void {
		GeneralPage::register();

		$this->assertNotFalse( has_filter( 'mb_settings_pages', array( GeneralPage::class, 'pages' ) ) );
		$this->assertNotFalse( has_filter( 'rwmb_meta_boxes', array( GeneralPage::class, 'boxes' ) ) );
	}
}
