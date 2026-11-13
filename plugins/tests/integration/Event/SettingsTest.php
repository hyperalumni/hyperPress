<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Settings;
use HyperPress\Event\SettingsPage;
use HyperPress\Utils\AdminMenu;
use WP_UnitTestCase;

final class SettingsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		delete_option( Settings::OPTION );
	}

	public function test_defaults_without_saved_row(): void {
		$this->assertTrue( Settings::frontpage_enabled() );
		$this->assertSame( 21, Settings::frontpage_lead_days() );
		$this->assertFalse( Settings::past_zero() );
		$this->assertSame( '', Settings::default_banner_color() );
		$this->assertSame( '', Settings::fallback_subtitle() );
		$this->assertSame( 0, Settings::hub_page_id() );
	}

	public function test_saved_row_without_checkbox_key_means_off(): void {
		update_option( Settings::OPTION, array() );

		$this->assertFalse( Settings::frontpage_enabled() );
		$this->assertFalse( Settings::past_zero() );
	}

	public function test_lead_days_range(): void {
		foreach ( array(
			'30'  => 30,
			'0'   => 0,
			'366' => 21,
			'-1'  => 21,
			'abc' => 21,
		) as $input => $expected ) {
			update_option( Settings::OPTION, array( Settings::FRONTPAGE_LEAD_DAYS => $input ) );
			$this->assertSame( $expected, Settings::frontpage_lead_days(), (string) $input );
		}
	}

	public function test_color_accepts_hex_only(): void {
		update_option( Settings::OPTION, array( Settings::DEFAULT_BANNER_COLOR => '#F15623' ) );
		$this->assertSame( '#F15623', Settings::default_banner_color() );

		update_option( Settings::OPTION, array( Settings::DEFAULT_BANNER_COLOR => 'red' ) );
		$this->assertSame( '', Settings::default_banner_color() );
	}

	public function test_hub_page_must_be_a_published_page(): void {
		$published = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$draft     = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);
		$trashed   = self::factory()->post->create( array( 'post_type' => 'page' ) );
		wp_trash_post( $trashed );
		$post = self::factory()->post->create();

		update_option( Settings::OPTION, array( Settings::HUB_PAGE => $published ) );
		$this->assertSame( $published, Settings::hub_page_id() );

		foreach ( array( $draft, $trashed, $post, 999999 ) as $id ) {
			update_option( Settings::OPTION, array( Settings::HUB_PAGE => $id ) );
			$this->assertSame( 0, Settings::hub_page_id(), (string) $id );
		}
	}

	public function test_settings_page_is_under_the_hyperpress_menu(): void {
		$pages = array_values(
			array_filter(
				apply_filters( 'mb_settings_pages', array() ),
				static fn( $page ) => SettingsPage::ID === ( $page['id'] ?? '' )
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_event', $pages[0]['id'] );
		$this->assertSame( AdminMenu::SLUG, $pages[0]['parent'] );
	}
}
