<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class EmptyLabelTest extends WP_UnitTestCase {

	private \Closure $translate;

	public function set_up(): void {
		parent::set_up();
		$this->translate = static fn (): string => 'TRANSLATED';
		add_filter( 'gettext', $this->translate );
	}

	public function tear_down(): void {
		remove_filter( 'gettext', $this->translate );
		parent::tear_down();
	}

	/**
	 * @param array<int, mixed> $fields
	 * @return array<int, mixed> every label_description value found.
	 */
	private function label_descriptions( array $fields ): array {
		$found = array();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			if ( array_key_exists( 'label_description', $field ) ) {
				$found[] = $field['label_description'];
			}
			if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
				$found = array_merge( $found, $this->label_descriptions( $field['fields'] ) );
			}
		}
		return $found;
	}

	public function test_fields_do_not_declare_an_empty_label_description(): void {
		$boxes = apply_filters( 'rwmb_meta_boxes', array() );

		$this->assertNotEmpty( $boxes );

		$values = array();
		foreach ( $boxes as $box ) {
			$values = array_merge( $values, $this->label_descriptions( $box['fields'] ?? array() ) );
		}

		foreach ( $values as $value ) {
			// An absent key means the same as '', and a translated empty string would return the .po header.
			$this->assertNotSame( 'TRANSLATED', $value );
			$this->assertNotSame( '', $value, 'Leave label_description out instead of setting it to an empty string.' );
		}
	}
}
