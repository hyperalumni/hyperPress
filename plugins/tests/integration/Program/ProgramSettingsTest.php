<?php
namespace HyperPress\Tests\Integration\Program;

use HyperPress\Program\Settings;
use WP_UnitTestCase;

final class ProgramSettingsTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_program' );
		parent::tear_down();
	}

	public function test_the_unsaved_option_returns_the_defaults(): void {
		delete_option( 'hyperpress_program' );

		$this->assertSame( array( 'post' ), Settings::post_types() );
		$this->assertSame( Settings::defaults(), get_option( 'hyperpress_program' ) );
	}

	public function test_a_saved_list_comes_back(): void {
		update_option( 'hyperpress_program', array( 'hyperpress_program_post_types' => array( 'page', 'hyper_countdown', '', 5 ) ) );

		$this->assertSame( array( 'page', 'hyper_countdown' ), Settings::post_types() );
	}

	public function test_a_saved_empty_post_type_list_stays_empty(): void {
		update_option( 'hyperpress_program', array( 'hyperpress_program_post_types' => array() ) );

		$this->assertSame( array(), Settings::post_types() );
	}

	public function test_a_saved_row_without_the_key_means_none(): void {
		update_option( 'hyperpress_program', array( 'other_key' => 'x' ) );

		$this->assertSame( array(), Settings::post_types() );
	}

	public function test_a_saved_empty_option_means_none(): void {
		update_option( 'hyperpress_program', array() );

		$this->assertSame( array(), Settings::post_types() );
	}

	public function test_a_missing_option_gives_the_default(): void {
		delete_option( 'hyperpress_program' );

		$this->assertSame( array( 'post' ), Settings::post_types() );
	}

	public function test_a_non_array_key_value_gives_the_default(): void {
		update_option( 'hyperpress_program', array( 'hyperpress_program_post_types' => 'post' ) );

		$this->assertSame( array( 'post' ), Settings::post_types() );
	}

	public function test_a_non_array_option_gives_the_defaults(): void {
		update_option( 'hyperpress_program', 'garbage' );

		$this->assertSame( array( 'post' ), Settings::post_types() );
	}

	public function test_a_non_array_post_types_value_gives_the_defaults(): void {
		update_option( 'hyperpress_program', array( 'hyperpress_program_post_types' => 'post' ) );

		$this->assertSame( array( 'post' ), Settings::post_types() );
	}

	public function test_an_explicit_default_wins(): void {
		delete_option( 'hyperpress_program' );

		$this->assertSame( 'mine', get_option( 'hyperpress_program', 'mine' ) );
		$this->assertSame( array(), get_option( 'hyperpress_program', array() ) );
	}
}
