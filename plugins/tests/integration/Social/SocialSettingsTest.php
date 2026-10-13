<?php
namespace HyperPress\Tests\Integration\Social;

use HyperPress\Social\Settings;
use WP_UnitTestCase;

final class SocialSettingsTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_social' );
		parent::tear_down();
	}

	public function test_nothing_saved_gives_empty_values(): void {
		delete_option( 'hyperpress_social' );

		foreach ( array_keys( Settings::NETWORKS ) as $key ) {
			$this->assertSame( '', Settings::url( $key ), $key );
		}
		$this->assertSame( 0, Settings::contact_page() );
		$this->assertFalse( get_option( 'hyperpress_social' ) );
	}

	public function test_saved_values_come_back(): void {
		update_option(
			'hyperpress_social',
			array(
				'hyperpress_social_facebook'     => 'https://facebook.test/hyper',
				'hyperpress_social_add_calendar' => '/calendar/',
				'hyperpress_social_contact'      => '12',
			)
		);

		$this->assertSame( 'https://facebook.test/hyper', Settings::url( 'facebook' ) );
		$this->assertSame( '/calendar/', Settings::url( 'add_calendar' ) );
		$this->assertSame( '', Settings::url( 'github' ) );
		$this->assertSame( 12, Settings::contact_page() );
	}

	public function test_a_non_array_option_gives_empty_values(): void {
		foreach ( array( 'garbage', 5, true ) as $value ) {
			update_option( 'hyperpress_social', $value );

			$this->assertSame( '', Settings::url( 'facebook' ) );
			$this->assertSame( 0, Settings::contact_page() );
		}
	}

	public function test_a_wrongly_typed_url_value_gives_an_empty_string(): void {
		update_option(
			'hyperpress_social',
			array(
				'hyperpress_social_facebook'  => array( 'https://facebook.test' ),
				'hyperpress_social_github'    => 5,
				'hyperpress_social_instagram' => null,
			)
		);

		$this->assertSame( '', Settings::url( 'facebook' ) );
		$this->assertSame( '', Settings::url( 'github' ) );
		$this->assertSame( '', Settings::url( 'instagram' ) );
	}

	public function test_unknown_keys_and_the_contact_key_are_not_url_networks(): void {
		update_option(
			'hyperpress_social',
			array(
				'hyperpress_social_contact' => '12',
				'hyperpress_social_other'   => 'https://other.test',
			)
		);

		$this->assertSame( '', Settings::url( 'contact' ) );
		$this->assertSame( '', Settings::url( 'other' ) );
		$this->assertSame( '', Settings::url( '' ) );
	}

	public function test_the_contact_page_accepts_a_positive_integer_or_digit_string(): void {
		update_option( 'hyperpress_social', array( 'hyperpress_social_contact' => 7 ) );
		$this->assertSame( 7, Settings::contact_page() );

		update_option( 'hyperpress_social', array( 'hyperpress_social_contact' => '12' ) );
		$this->assertSame( 12, Settings::contact_page() );

		update_option( 'hyperpress_social', array( 'hyperpress_social_contact' => '007' ) );
		$this->assertSame( 7, Settings::contact_page() );
	}

	public function test_a_bad_contact_page_value_gives_zero(): void {
		foreach ( array( 'abc', '12abc', ' 12', '-5', -5, '', '0', 0, '1.5', 1.5, true, array( 12 ), null ) as $value ) {
			update_option( 'hyperpress_social', array( 'hyperpress_social_contact' => $value ) );

			$this->assertSame( 0, Settings::contact_page(), var_export( $value, true ) );
		}

		update_option( 'hyperpress_social', array( 'something_else' => '12' ) );
		$this->assertSame( 0, Settings::contact_page() );
	}
}
