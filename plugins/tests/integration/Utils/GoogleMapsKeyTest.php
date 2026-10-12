<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\GoogleMapsKey;
use WP_UnitTestCase;

final class GoogleMapsKeyTest extends WP_UnitTestCase {

	private const LEGACY_MOD = 'hyperpress_competition_google_maps_api_key';

	public function set_up(): void {
		parent::set_up();
		delete_option( GoogleMapsKey::OPTION );
		remove_theme_mod( self::LEGACY_MOD );
	}

	public function tear_down(): void {
		delete_option( GoogleMapsKey::OPTION );
		remove_theme_mod( self::LEGACY_MOD );
		parent::tear_down();
	}

	public function test_option_name_is_hyperpress_google_maps_api_key(): void {
		$this->assertSame( 'hyperpress_google_maps_api_key', GoogleMapsKey::OPTION );
	}

	public function test_returns_empty_string_when_nothing_is_set(): void {
		$this->assertSame( '', GoogleMapsKey::get() );
	}

	public function test_returns_the_global_option(): void {
		update_option( GoogleMapsKey::OPTION, 'global-key' );
		$this->assertSame( 'global-key', GoogleMapsKey::get() );
	}

	public function test_global_option_wins_over_the_theme_mod(): void {
		update_option( GoogleMapsKey::OPTION, 'global-key' );
		set_theme_mod( self::LEGACY_MOD, 'theme-key' );
		$this->assertSame( 'global-key', GoogleMapsKey::get() );
	}

	public function test_falls_back_to_the_theme_mod_when_option_is_missing(): void {
		set_theme_mod( self::LEGACY_MOD, 'theme-key' );
		$this->assertSame( 'theme-key', GoogleMapsKey::get() );
	}

	public function test_falls_back_to_the_theme_mod_when_option_is_empty(): void {
		update_option( GoogleMapsKey::OPTION, '' );
		set_theme_mod( self::LEGACY_MOD, 'theme-key' );
		$this->assertSame( 'theme-key', GoogleMapsKey::get() );
	}

	public function test_falls_back_to_the_theme_mod_when_option_is_only_whitespace(): void {
		update_option( GoogleMapsKey::OPTION, "  \t " );
		set_theme_mod( self::LEGACY_MOD, 'theme-key' );
		$this->assertSame( 'theme-key', GoogleMapsKey::get() );
	}

	public function test_trims_the_global_option(): void {
		update_option( GoogleMapsKey::OPTION, "  global-key \n" );
		$this->assertSame( 'global-key', GoogleMapsKey::get() );
	}

	public function test_trims_the_theme_mod(): void {
		set_theme_mod( self::LEGACY_MOD, "  theme-key\t" );
		$this->assertSame( 'theme-key', GoogleMapsKey::get() );
	}
}
