<?php
namespace HyperPress\Tests\Integration\Sponsor;

use HyperPress\Sponsor\Settings;
use WP_UnitTestCase;

final class SponsorSubtitleSettingTest extends WP_UnitTestCase {

	private const DEFAULT = 'Helping Make HYPER Happen';

	public function tear_down(): void {
		delete_option( 'hyperpress_sponsor' );
		parent::tear_down();
	}

	public function test_the_unsaved_option_returns_the_default(): void {
		delete_option( 'hyperpress_sponsor' );

		$this->assertSame( self::DEFAULT, Settings::banner_subtitle() );
		$this->assertSame( Settings::defaults(), get_option( 'hyperpress_sponsor' ) );
	}

	public function test_a_saved_value_comes_back(): void {
		update_option( 'hyperpress_sponsor', array( 'hyperpress_sponsor_banner_subtitle' => '<b>Help</b>' ) );

		$this->assertSame( '<b>Help</b>', Settings::banner_subtitle() );
	}

	public function test_a_saved_empty_value_stays_empty(): void {
		update_option( 'hyperpress_sponsor', array( 'hyperpress_sponsor_banner_subtitle' => '' ) );

		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_a_saved_array_without_the_key_gives_the_default(): void {
		update_option( 'hyperpress_sponsor', array( 'something_else' => 'x' ) );

		$this->assertSame( self::DEFAULT, Settings::banner_subtitle() );
	}

	public function test_a_non_array_option_gives_the_default(): void {
		update_option( 'hyperpress_sponsor', 'garbage' );

		$this->assertSame( self::DEFAULT, Settings::banner_subtitle() );
	}

	public function test_a_non_string_value_gives_the_default(): void {
		update_option( 'hyperpress_sponsor', array( 'hyperpress_sponsor_banner_subtitle' => 5 ) );

		$this->assertSame( self::DEFAULT, Settings::banner_subtitle() );
	}

	public function test_an_explicit_default_wins(): void {
		delete_option( 'hyperpress_sponsor' );

		$this->assertSame( 'mine', get_option( 'hyperpress_sponsor', 'mine' ) );
		$this->assertSame( array(), get_option( 'hyperpress_sponsor', array() ) );
	}
}
