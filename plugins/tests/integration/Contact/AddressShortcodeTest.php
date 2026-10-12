<?php
namespace HyperPress\Tests\Integration\Contact;

use HyperPress\Contact\AddressShortcode;
use HyperPress\Contact\Settings;
use WP_UnitTestCase;

final class AddressShortcodeTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		AddressShortcode::register();
		$this->seed( array() );
	}

	/**
	 * Stores the three page addresses plus any extras.
	 *
	 * @param array<int,array<string,string>> $extra Additional addresses.
	 */
	private function seed( array $extra ): void {
		update_option(
			Settings::OPTION,
			array(
				Settings::ADDRESSES => array_merge(
					array(
						array(
							'slug'    => 'meeting',
							'heading' => 'Team Meeting Address',
							'name'    => 'HYPER @ Long Robotics Center',
							'street'  => '18 Spear Street',
							'city'    => 'Quincy',
							'state'   => 'MA',
							'zip'     => '02169',
							'phone'   => '(617) 376-1100',
						),
						array(
							'slug'    => 'shipping',
							'heading' => 'Shipping Address',
							'name'    => 'HYPER Robotics',
							'extra'   => 'ATTN: Michael Gendron',
							'street'  => '100 Main Street',
							'city'    => 'Boston',
							'state'   => 'MA',
							'zip'     => '02110',
						),
					),
					$extra
				),
			)
		);
	}

	public function test_renders_the_full_card(): void {
		$out = do_shortcode( '[hyperpress_address id="meeting"]' );

		$this->assertStringContainsString( '<div class="card">', $out );
		$this->assertStringContainsString( '<div class="card-divider"><h5>Team Meeting Address</h5></div>', $out );
		$this->assertStringContainsString( '<address class="vcard">', $out );
		$this->assertStringContainsString( '<span class="fn">HYPER @ Long Robotics Center</span>', $out );
		$this->assertStringContainsString( '<span class="street-address">18 Spear Street</span>', $out );
		$this->assertStringContainsString( '<span class="locality">Quincy</span>, <span class="state">MA</span> <span class="zip">02169</span>', $out );
		$this->assertStringContainsString( '<a href="tel:+16173761100">(617) 376-1100</a>', $out );
	}

	public function test_address_without_phone_and_with_extra_line(): void {
		$out = do_shortcode( '[hyperpress_address id="shipping"]' );

		$this->assertStringNotContainsString( 'class="phone"', $out );
		$fn     = strpos( $out, '<span class="fn">' );
		$extra  = strpos( $out, 'ATTN: Michael Gendron' );
		$street = strpos( $out, '<span class="street-address">' );
		$this->assertNotFalse( $fn );
		$this->assertNotFalse( $extra );
		$this->assertNotFalse( $street );
		$this->assertGreaterThan( $fn, $extra );
		$this->assertLessThan( $street, $extra );
	}

	public function test_unknown_or_missing_id_is_silent_for_visitors(): void {
		wp_set_current_user( 0 );
		$this->assertSame( '', do_shortcode( '[hyperpress_address id="nope"]' ) );
		$this->assertSame( '', do_shortcode( '[hyperpress_address]' ) );
	}

	public function test_unknown_id_shows_a_comment_to_administrators(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame(
			'<!-- hyperpress_address: no address with id "nope" -->',
			do_shortcode( '[hyperpress_address id="nope"]' )
		);
	}

	public function test_id_cannot_break_out_of_the_comment(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$out = AddressShortcode::render( array( 'id' => 'x--><script>' ) );

		$this->assertStringNotContainsString( '<script', $out );
		$this->assertSame( 1, substr_count( $out, '-->' ) );
	}

	public function test_heading_and_name_are_escaped(): void {
		$this->seed(
			array(
				array(
					'slug'    => 'evil',
					'heading' => '<script>alert(1)</script>',
					'name'    => 'A & B',
				),
			)
		);
		$out = do_shortcode( '[hyperpress_address id="evil"]' );

		$this->assertStringContainsString( '&lt;script&gt;', $out );
		$this->assertStringNotContainsString( '<script>', $out );
		$this->assertStringContainsString( 'A &amp; B', $out );
	}

	public function test_locality_uses_whichever_parts_exist(): void {
		$card = AddressShortcode::card(
			array(
				'heading' => 'H',
				'city'    => 'Quincy',
				'zip'     => '02169',
			)
		);
		$this->assertStringContainsString( '<span class="locality">Quincy</span> <span class="zip">02169</span>', $card );
		$this->assertStringNotContainsString( 'class="state"', $card );
	}

	/**
	 * @dataProvider tel_provider
	 */
	public function test_tel_href( string $phone, string $expected ): void {
		$this->assertSame( $expected, AddressShortcode::tel_href( $phone ) );
	}

	/**
	 * @return array<string,array{string,string}>
	 */
	public static function tel_provider(): array {
		return array(
			'us formatted'   => array( '(617) 376-1100', '+16173761100' ),
			'international'  => array( '+44 20 7946 0958', '+442079460958' ),
			'extension'      => array( '617.376.1100 x23', '+16173761100' ),
			'ext dot'        => array( '(617) 376-1100 ext. 5', '+16173761100' ),
			'leading one'    => array( '1-617-376-1100', '+16173761100' ),
			'plus extension' => array( '+44 20 7946 0958 x12', '+442079460958' ),
			'no digits'      => array( 'call us', '' ),
		);
	}

	public function test_phone_without_digits_is_plain_text(): void {
		$this->seed(
			array(
				array(
					'slug'    => 'text',
					'heading' => 'Text',
					'phone'   => 'call us',
				),
			)
		);
		$out = do_shortcode( '[hyperpress_address id="text"]' );

		$this->assertStringContainsString( '<span class="phone">call us</span>', $out );
		$this->assertStringNotContainsString( 'tel:', $out );
	}
}
