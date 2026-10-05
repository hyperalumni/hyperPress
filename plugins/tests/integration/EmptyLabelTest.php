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

	public function test_empty_label_descriptions_are_not_run_through_translation(): void {
		$boxes = apply_filters( 'rwmb_meta_boxes', array() );

		$this->assertNotEmpty( $boxes );

		$values = array();
		foreach ( $boxes as $box ) {
			$values = array_merge( $values, $this->label_descriptions( $box['fields'] ?? array() ) );
		}

		$this->assertNotEmpty( $values, 'Expected at least one label_description to check.' );
		foreach ( $values as $value ) {
			$this->assertNotSame( 'TRANSLATED', $value );
			$this->assertSame( '', $value );
		}
	}
}
