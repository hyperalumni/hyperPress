<?php
namespace HyperPress\Tests\Integration\Competition;

use WP_UnitTestCase;

final class MetaBoxesTest extends WP_UnitTestCase {

	private const BOX    = 'hyperpress_competition_information';
	private const FIELD  = 'hyperpress_competition_event_days';
	private const OLD_ID = 'hyperpress_competition_event_start_date';

	/** @return array<string,mixed> */
	private function information_box(): array {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			if ( self::BOX === ( $box['id'] ?? '' ) ) {
				return $box;
			}
		}
		$this->fail( 'information meta box not found' );
	}

	/** @return array<string,mixed> */
	private function days_field(): array {
		foreach ( $this->information_box()['fields'] as $field ) {
			if ( self::FIELD === ( $field['id'] ?? '' ) ) {
				return $field;
			}
		}
		$this->fail( 'event days field not found' );
	}

	public function test_event_days_is_a_cloneable_group(): void {
		$field = $this->days_field();

		$this->assertSame( 'group', $field['type'] );
		$this->assertTrue( $field['clone'] );
		$this->assertTrue( $field['sort_clone'] );
		$this->assertNotEmpty( $field['add_button'] );
		$this->assertArrayNotHasKey( 'clone_as_multiple', $field );
	}

	public function test_event_days_sub_fields(): void {
		$fields = $this->days_field()['fields'];
		$by_id  = array_column( $fields, null, 'id' );

		$this->assertSame( array( 'label', 'start', 'skip' ), array_column( $fields, 'id' ) );
		$this->assertSame( 'text', $by_id['label']['type'] );
		$this->assertSame( 'datetime', $by_id['start']['type'] );
		$this->assertFalse( $by_id['start']['timestamp'] );
		$this->assertSame( 'Y-m-d H:i', $by_id['start']['save_format'] );
		$this->assertArrayNotHasKey( 'format', $by_id['start'] );
		$this->assertSame( 'checkbox', $by_id['skip']['type'] );
		$this->assertEmpty( $by_id['skip']['std'] ?? null, 'no default: the unticked state must be the normal one' );
	}

	public function test_old_start_date_field_is_gone(): void {
		$this->assertNotContains( self::OLD_ID, array_column( $this->information_box()['fields'], 'id' ) );
	}
}
