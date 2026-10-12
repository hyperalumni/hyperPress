<?php
namespace HyperPress\Tests\Integration\Contact;

use HyperPress\Contact\Addresses;
use HyperPress\Contact\Settings;
use WP_UnitTestCase;

final class ContactSettingsTest extends WP_UnitTestCase {

	public function test_addresses_are_normalized_to_nine_keys(): void {
		update_option(
			Settings::OPTION,
			array(
				Settings::ADDRESSES => array(
					array(
						'slug'    => 'meeting',
						'heading' => 'Team Meeting Address',
						'name'    => 'HYPER @ Long Robotics Center',
					),
				),
			)
		);

		$expected = array(
			'slug'    => 'meeting',
			'heading' => 'Team Meeting Address',
			'name'    => 'HYPER @ Long Robotics Center',
			'extra'   => '',
			'street'  => '',
			'city'    => '',
			'state'   => '',
			'zip'     => '',
			'phone'   => '',
		);

		$this->assertSame( array( $expected ), Settings::addresses() );
		$this->assertSame( $expected, Settings::address( 'meeting' ) );
		$this->assertNull( Settings::address( 'nope' ) );
	}

	public function test_addresses_is_empty_when_option_is_absent_or_not_an_array(): void {
		delete_option( Settings::OPTION );
		$this->assertSame( array(), Settings::addresses() );

		update_option( Settings::OPTION, 'garbage' );
		$this->assertSame( array(), Settings::addresses() );
	}

	public function test_map_returns_the_parsed_value(): void {
		update_option( Settings::OPTION, array( Settings::MAP => '42.25,-71.0,17' ) );

		$this->assertSame(
			array(
				'lat'  => 42.25,
				'lng'  => -71.0,
				'zoom' => 17,
			),
			Settings::map()
		);

		delete_option( Settings::OPTION );
		$this->assertNull( Settings::map() );
	}

	public function test_sanitize_generates_slug_from_heading(): void {
		$out = Addresses::sanitize(
			array(
				array(
					'heading' => 'Team Meeting Address',
					'slug'    => '',
				),
			)
		);

		$this->assertSame( 'team-meeting-address', $out[0]['slug'] );
	}

	public function test_sanitize_cleans_the_slug(): void {
		$out = Addresses::sanitize(
			array(
				array(
					'heading' => 'Ship',
					'slug'    => 'Ship To!',
				),
			)
		);

		$this->assertSame( 'ship-to', $out[0]['slug'] );
	}

	public function test_sanitize_makes_slugs_unique(): void {
		$out = Addresses::sanitize(
			array(
				array(
					'slug' => 'meeting',
					'name' => 'A',
				),
				array(
					'slug' => 'meeting',
					'name' => 'B',
				),
				array(
					'slug' => 'meeting',
					'name' => 'C',
				),
			)
		);

		$this->assertSame( array( 'meeting', 'meeting-2', 'meeting-3' ), array_column( $out, 'slug' ) );
		$this->assertSame( array( 'A', 'B', 'C' ), array_column( $out, 'name' ) );
	}

	public function test_sanitize_drops_empty_entries(): void {
		$out = Addresses::sanitize(
			array(
				array(
					'slug'    => '',
					'heading' => '',
					'name'    => '',
					'street'  => '',
				),
				array( 'name' => 'Keep' ),
			)
		);

		$this->assertCount( 1, $out );
		$this->assertSame( 'Keep', $out[0]['name'] );
	}

	public function test_sanitize_strips_tags(): void {
		$out = Addresses::sanitize( array( array( 'name' => '<b>x</b>' ) ) );

		$this->assertSame( 'x', $out[0]['name'] );
	}

	public function test_sanitize_keeps_the_phone_as_typed(): void {
		$out = Addresses::sanitize( array( array( 'phone' => '+1 (617) 376-1100 x2' ) ) );

		$this->assertSame( '+1 (617) 376-1100 x2', $out[0]['phone'] );
	}

	public function test_sanitize_preserves_order(): void {
		$out = Addresses::sanitize(
			array(
				array( 'heading' => 'Zulu' ),
				array( 'heading' => 'Alpha' ),
			)
		);

		$this->assertSame( array( 'zulu', 'alpha' ), array_column( $out, 'slug' ) );
	}

	public function test_sanitize_returns_empty_array_for_non_array(): void {
		$this->assertSame( array(), Addresses::sanitize( 'nope' ) );
		$this->assertSame( array(), Addresses::sanitize( null ) );
	}

	public function test_sanitize_falls_back_to_address_slug(): void {
		$out = Addresses::sanitize(
			array(
				array(
					'city'    => 'Boston',
					'heading' => '',
					'slug'    => '',
				),
			)
		);

		$this->assertSame( 'address', $out[0]['slug'] );
	}
}
