<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Settings;
use WP_UnitTestCase;

final class CompetitionSubtitleSettingTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_competition' );
		parent::tear_down();
	}

	public function test_the_unsaved_option_returns_the_empty_default(): void {
		delete_option( 'hyperpress_competition' );

		$this->assertSame( '', Settings::banner_subtitle() );
		$this->assertSame( Settings::defaults(), get_option( 'hyperpress_competition' ) );
	}

	public function test_a_saved_value_comes_back(): void {
		update_option( 'hyperpress_competition', array( 'hyperpress_competition_banner_subtitle' => '<b>Compete</b>' ) );

		$this->assertSame( '<b>Compete</b>', Settings::banner_subtitle() );
	}

	public function test_a_saved_array_without_the_key_gives_the_default(): void {
		update_option( 'hyperpress_competition', array( 'something_else' => 'x' ) );

		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_a_non_array_option_gives_the_default(): void {
		update_option( 'hyperpress_competition', 'garbage' );

		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_a_non_string_value_gives_the_default(): void {
		update_option( 'hyperpress_competition', array( 'hyperpress_competition_banner_subtitle' => 5 ) );

		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_an_explicit_default_wins(): void {
		delete_option( 'hyperpress_competition' );

		$this->assertSame( 'mine', get_option( 'hyperpress_competition', 'mine' ) );
		$this->assertSame( array(), get_option( 'hyperpress_competition', array() ) );
	}
}
