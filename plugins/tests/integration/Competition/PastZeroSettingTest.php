<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Settings;
use WP_UnitTestCase;

final class PastZeroSettingTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( Settings::OPTION );
		parent::tear_down();
	}

	public function test_default_is_off(): void {
		delete_option( Settings::OPTION );

		$this->assertFalse( Settings::past_zero() );
	}

	public function test_checked_value_is_on(): void {
		update_option( Settings::OPTION, array( Settings::PAST_ZERO => '1' ) );
		$this->assertTrue( Settings::past_zero() );

		// Meta Box drops the key of an unchecked checkbox.
		update_option( Settings::OPTION, array() );
		$this->assertFalse( Settings::past_zero() );

		update_option( Settings::OPTION, 'garbage' );
		$this->assertFalse( Settings::past_zero() );
	}

	public function test_settings_box_has_a_checkbox_for_it(): void {
		$boxes = apply_filters( 'rwmb_meta_boxes', array() );
		$box   = null;
		foreach ( $boxes as $candidate ) {
			if ( 'hyperpress_competition_box' === ( $candidate['id'] ?? '' ) ) {
				$box = $candidate;
			}
		}

		$this->assertNotNull( $box );
		$field = null;
		foreach ( $box['fields'] as $candidate ) {
			if ( Settings::PAST_ZERO === ( $candidate['id'] ?? '' ) ) {
				$field = $candidate;
			}
		}

		$this->assertNotNull( $field );
		$this->assertSame( 'checkbox', $field['type'] );
	}
}
