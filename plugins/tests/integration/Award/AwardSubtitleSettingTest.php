<?php
namespace HyperPress\Tests\Integration\Award;

use HyperPress\Award\Settings;
use WP_UnitTestCase;

final class AwardSubtitleSettingTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_award' );
		parent::tear_down();
	}

	public function test_the_unsaved_option_returns_the_empty_default(): void {
		delete_option( 'hyperpress_award' );

		$this->assertSame( '', Settings::banner_subtitle() );
		$this->assertSame( Settings::defaults(), get_option( 'hyperpress_award' ) );
	}

	public function test_a_saved_value_comes_back(): void {
		update_option( 'hyperpress_award', array( 'hyperpress_award_banner_subtitle' => '<b>Honours</b>' ) );

		$this->assertSame( '<b>Honours</b>', Settings::banner_subtitle() );
	}

	public function test_a_saved_array_without_the_key_means_the_subtitle_was_cleared(): void {
		update_option( 'hyperpress_award', array( 'something_else' => 'x' ) );

		$this->assertSame( '', Settings::banner_subtitle() );
		update_option( 'hyperpress_award', array() );
		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_a_non_array_option_gives_the_default(): void {
		update_option( 'hyperpress_award', 'garbage' );

		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_a_non_string_value_gives_the_default(): void {
		update_option( 'hyperpress_award', array( 'hyperpress_award_banner_subtitle' => 5 ) );

		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_an_explicit_default_wins(): void {
		delete_option( 'hyperpress_award' );

		$this->assertSame( 'mine', get_option( 'hyperpress_award', 'mine' ) );
		$this->assertSame( array(), get_option( 'hyperpress_award', array() ) );
	}
}
