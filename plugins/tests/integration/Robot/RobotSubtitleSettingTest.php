<?php
namespace HyperPress\Tests\Integration\Robot;

use HyperPress\Robot\Settings;
use WP_UnitTestCase;

final class RobotSubtitleSettingTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_robot' );
		parent::tear_down();
	}

	public function test_the_unsaved_option_returns_the_empty_default(): void {
		delete_option( 'hyperpress_robot' );

		$this->assertSame( '', Settings::banner_subtitle() );
		$this->assertSame( Settings::defaults(), get_option( 'hyperpress_robot' ) );
	}

	public function test_a_saved_value_comes_back(): void {
		update_option( 'hyperpress_robot', array( 'hyperpress_robot_banner_subtitle' => '<b>Machines</b>' ) );

		$this->assertSame( '<b>Machines</b>', Settings::banner_subtitle() );
	}

	public function test_a_saved_array_without_the_key_means_the_subtitle_was_cleared(): void {
		update_option( 'hyperpress_robot', array( 'something_else' => 'x' ) );

		$this->assertSame( '', Settings::banner_subtitle() );
		update_option( 'hyperpress_robot', array() );
		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_a_non_array_option_gives_the_default(): void {
		update_option( 'hyperpress_robot', 'garbage' );

		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_a_non_string_value_gives_the_default(): void {
		update_option( 'hyperpress_robot', array( 'hyperpress_robot_banner_subtitle' => 5 ) );

		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_an_explicit_default_wins(): void {
		delete_option( 'hyperpress_robot' );

		$this->assertSame( 'mine', get_option( 'hyperpress_robot', 'mine' ) );
		$this->assertSame( array(), get_option( 'hyperpress_robot', array() ) );
	}
}
