<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Settings;
use WP_UnitTestCase;

final class FrontpageSettingTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( Settings::OPTION );
		parent::tear_down();
	}

	public function test_with_no_saved_settings_it_is_on_with_sixty_days(): void {
		delete_option( Settings::OPTION );

		$this->assertTrue( Settings::frontpage_enabled() );
		$this->assertSame( 60, Settings::frontpage_lead_days() );
		$this->assertTrue( Settings::defaults()[ Settings::FRONTPAGE ] );
		$this->assertSame( 60, Settings::defaults()[ Settings::FRONTPAGE_LEAD_DAYS ] );
	}

	public function test_once_saved_a_missing_checkbox_key_means_off(): void {
		update_option( Settings::OPTION, array( Settings::FRONTPAGE => '1' ) );
		$this->assertTrue( Settings::frontpage_enabled() );

		// Meta Box drops the key of an unchecked checkbox.
		update_option( Settings::OPTION, array() );
		$this->assertFalse( Settings::frontpage_enabled() );

		update_option( Settings::OPTION, 'garbage' );
		$this->assertFalse( Settings::frontpage_enabled() );
	}

	public function test_valid_lead_days_are_kept(): void {
		foreach ( array( '0' => 0, '45' => 45, '365' => 365, ' 30 ' => 30 ) as $stored => $expected ) {
			update_option( Settings::OPTION, array( Settings::FRONTPAGE_LEAD_DAYS => (string) $stored ) );
			$this->assertSame( $expected, Settings::frontpage_lead_days(), "stored '$stored'" );
		}

		update_option( Settings::OPTION, array( Settings::FRONTPAGE_LEAD_DAYS => 90 ) );
		$this->assertSame( 90, Settings::frontpage_lead_days() );
	}

	/** @return array<string,array<int,mixed>> */
	public static function malformed(): array {
		return array(
			'empty'      => array( '' ),
			'text'       => array( 'x' ),
			'negative'   => array( '-1' ),
			'too big'    => array( '366' ),
			'fractional' => array( '1.5' ),
			'array'      => array( array( '5' ) ),
			'null'       => array( null ),
		);
	}

	/**
	 * @dataProvider malformed
	 * @param mixed $value Stored value.
	 */
	public function test_malformed_lead_days_give_the_default_and_sanitize_to_empty( $value ): void {
		update_option( Settings::OPTION, array( Settings::FRONTPAGE_LEAD_DAYS => $value ) );

		$this->assertSame( 60, Settings::frontpage_lead_days() );
		$this->assertSame( '', Settings::sanitize_lead_days( $value ) );
	}

	public function test_a_missing_key_or_option_gives_the_default_lead_days(): void {
		update_option( Settings::OPTION, array() );
		$this->assertSame( 60, Settings::frontpage_lead_days() );

		update_option( Settings::OPTION, 'garbage' );
		$this->assertSame( 60, Settings::frontpage_lead_days() );
	}

	public function test_sanitize_trims_and_accepts_valid_values(): void {
		$this->assertSame( '45', Settings::sanitize_lead_days( ' 45 ' ) );
		$this->assertSame( '0', Settings::sanitize_lead_days( '0' ) );
		$this->assertSame( '365', Settings::sanitize_lead_days( '365' ) );
	}

	public function test_the_settings_box_has_both_fields(): void {
		$box = null;
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $candidate ) {
			if ( 'hyperpress_competition_box' === ( $candidate['id'] ?? '' ) ) {
				$box = $candidate;
			}
		}
		$this->assertNotNull( $box );

		$fields = array();
		foreach ( $box['fields'] as $field ) {
			$fields[ $field['id'] ?? '' ] = $field;
		}

		$this->assertSame( 'checkbox', $fields[ Settings::FRONTPAGE ]['type'] );
		$this->assertSame( 'number', $fields[ Settings::FRONTPAGE_LEAD_DAYS ]['type'] );
		$this->assertSame( 0, (int) $fields[ Settings::FRONTPAGE_LEAD_DAYS ]['min'] );
		$this->assertSame( 365, (int) $fields[ Settings::FRONTPAGE_LEAD_DAYS ]['max'] );
		$this->assertSame( 60, (int) $fields[ Settings::FRONTPAGE_LEAD_DAYS ]['std'] );
		$this->assertSame( array( Settings::class, 'sanitize_lead_days' ), $fields[ Settings::FRONTPAGE_LEAD_DAYS ]['sanitize_callback'] );
	}
}
