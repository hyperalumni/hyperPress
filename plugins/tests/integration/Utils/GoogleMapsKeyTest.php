<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\GoogleMapsKey;
use WP_UnitTestCase;

final class GoogleMapsKeyTest extends WP_UnitTestCase {

	private const OPTION     = 'hyperpress_general';
	private const OLD_OPTION = 'hyperpress_google_maps_api_key';
	private const OLD_MOD    = 'hyperpress_competition_google_maps_api_key';

	public function set_up(): void {
		parent::set_up();
		$this->clear();
	}

	public function tear_down(): void {
		$this->clear();
		parent::tear_down();
	}

	private function clear(): void {
		delete_option( self::OPTION );
		delete_option( self::OLD_OPTION );
		remove_theme_mod( self::OLD_MOD );
	}

	public function test_returns_empty_string_when_nothing_is_saved(): void {
		$this->assertSame( '', GoogleMapsKey::get() );
	}

	public function test_returns_the_trimmed_value_from_the_general_option(): void {
		update_option( self::OPTION, array( 'hyperpress_general_google_maps_api_key' => "  global-key \n" ) );
		$this->assertSame( 'global-key', GoogleMapsKey::get() );
	}

	public function test_returns_empty_string_when_the_value_is_blank(): void {
		update_option( self::OPTION, array( 'hyperpress_general_google_maps_api_key' => "  \t " ) );
		$this->assertSame( '', GoogleMapsKey::get() );
	}

	public function test_returns_empty_string_when_the_value_is_not_a_string(): void {
		update_option( self::OPTION, array( 'hyperpress_general_google_maps_api_key' => array( 'x' ) ) );
		$this->assertSame( '', GoogleMapsKey::get() );
	}

	public function test_returns_empty_string_when_the_option_is_a_string(): void {
		update_option( self::OPTION, 'global-key' );
		$this->assertSame( '', GoogleMapsKey::get() );
	}

	public function test_ignores_the_old_flat_option(): void {
		update_option( self::OLD_OPTION, 'old-key' );
		$this->assertSame( '', GoogleMapsKey::get() );
	}

	public function test_ignores_the_old_theme_mod(): void {
		set_theme_mod( self::OLD_MOD, 'theme-key' );
		$this->assertSame( '', GoogleMapsKey::get() );
	}
}
