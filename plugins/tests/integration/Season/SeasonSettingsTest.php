<?php
namespace HyperPress\Tests\Integration\Season;

use HyperPress\Season\Settings;
use WP_UnitTestCase;

final class SeasonSettingsTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_season' );
		parent::tear_down();
	}

	public function test_the_unsaved_option_returns_the_defaults(): void {
		delete_option( 'hyperpress_season' );

		$this->assertSame( array( 'post' ), Settings::post_types() );
		$this->assertSame( '', Settings::current() );
		$this->assertSame( '', Settings::banner_subtitle() );
		$this->assertSame( Settings::defaults(), get_option( 'hyperpress_season' ) );
	}

	public function test_saved_values_come_back(): void {
		update_option(
			'hyperpress_season',
			array(
				'hyperpress_season_current'         => 'season-2026',
				'hyperpress_season_banner_subtitle' => '<b>Go</b>',
				'hyperpress_season_post_types'      => array( 'page', 'hyper_countdown', '', 5 ),
			)
		);

		$this->assertSame( 'season-2026', Settings::current() );
		$this->assertSame( '<b>Go</b>', Settings::banner_subtitle() );
		$this->assertSame( array( 'page', 'hyper_countdown' ), Settings::post_types() );
	}

	public function test_a_saved_empty_post_type_list_stays_empty(): void {
		update_option( 'hyperpress_season', array( 'hyperpress_season_post_types' => array() ) );

		$this->assertSame( array(), Settings::post_types() );
	}

	public function test_a_saved_row_without_the_key_means_none(): void {
		update_option( 'hyperpress_season', array( 'other_key' => 'x' ) );

		$this->assertSame( array(), Settings::post_types() );
	}

	public function test_a_saved_empty_option_means_none(): void {
		update_option( 'hyperpress_season', array() );

		$this->assertSame( array(), Settings::post_types() );
	}

	public function test_a_missing_option_gives_the_default(): void {
		delete_option( 'hyperpress_season' );

		$this->assertSame( array( 'post' ), Settings::post_types() );
	}

	public function test_a_non_array_key_value_gives_the_default(): void {
		update_option( 'hyperpress_season', array( 'hyperpress_season_post_types' => 'post' ) );

		$this->assertSame( array( 'post' ), Settings::post_types() );
	}

	public function test_a_non_array_option_gives_the_defaults(): void {
		update_option( 'hyperpress_season', 'garbage' );

		$this->assertSame( array( 'post' ), Settings::post_types() );
		$this->assertSame( '', Settings::current() );
		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_non_string_values_give_the_defaults(): void {
		update_option(
			'hyperpress_season',
			array(
				'hyperpress_season_current'         => array( 'x' ),
				'hyperpress_season_banner_subtitle' => 5,
				'hyperpress_season_post_types'      => 'post',
			)
		);

		$this->assertSame( '', Settings::current() );
		$this->assertSame( '', Settings::banner_subtitle() );
		$this->assertSame( array( 'post' ), Settings::post_types() );
	}

	public function test_an_explicit_default_wins(): void {
		delete_option( 'hyperpress_season' );

		$this->assertSame( 'mine', get_option( 'hyperpress_season', 'mine' ) );
		$this->assertSame( array(), get_option( 'hyperpress_season', array() ) );
	}
}
