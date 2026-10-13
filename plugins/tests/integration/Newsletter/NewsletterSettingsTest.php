<?php
namespace HyperPress\Tests\Integration\Newsletter;

use HyperPress\Newsletter\Settings;
use WP_UnitTestCase;

final class NewsletterSettingsTest extends WP_UnitTestCase {

	private const DEFAULT = 'Updates on Team HYPER from our Marketing Team';

	public function tear_down(): void {
		delete_option( 'hyperpress_newsletter' );
		parent::tear_down();
	}

	public function test_the_unsaved_option_returns_the_default(): void {
		delete_option( 'hyperpress_newsletter' );

		$this->assertSame( self::DEFAULT, Settings::banner_subtitle() );
		$this->assertSame( Settings::defaults(), get_option( 'hyperpress_newsletter' ) );
	}

	public function test_a_saved_value_comes_back(): void {
		update_option( 'hyperpress_newsletter', array( 'hyperpress_newsletter_banner_subtitle' => '<b>News</b>' ) );

		$this->assertSame( '<b>News</b>', Settings::banner_subtitle() );
	}

	public function test_a_saved_empty_value_stays_empty(): void {
		update_option( 'hyperpress_newsletter', array( 'hyperpress_newsletter_banner_subtitle' => '' ) );

		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_a_saved_array_without_the_key_means_the_subtitle_was_cleared(): void {
		update_option( 'hyperpress_newsletter', array( 'something_else' => 'x' ) );

		$this->assertSame( '', Settings::banner_subtitle() );
		update_option( 'hyperpress_newsletter', array() );
		$this->assertSame( '', Settings::banner_subtitle() );
	}

	public function test_a_non_array_option_gives_the_default(): void {
		update_option( 'hyperpress_newsletter', 'garbage' );

		$this->assertSame( self::DEFAULT, Settings::banner_subtitle() );
	}

	public function test_a_non_string_value_gives_the_default(): void {
		update_option( 'hyperpress_newsletter', array( 'hyperpress_newsletter_banner_subtitle' => 5 ) );

		$this->assertSame( self::DEFAULT, Settings::banner_subtitle() );
	}

	public function test_an_explicit_default_wins(): void {
		delete_option( 'hyperpress_newsletter' );

		$this->assertSame( 'mine', get_option( 'hyperpress_newsletter', 'mine' ) );
		$this->assertSame( array(), get_option( 'hyperpress_newsletter', array() ) );
	}
}
