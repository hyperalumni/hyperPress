<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

/**
 * Meta Box prints a field's `desc` as HTML. A description that names a tag as
 * plain text (`<strong>`) therefore opens a real, never-closed element and
 * styles everything after it. Tag names in help text must be entities
 * (`&lt;strong&gt;`).
 */
final class MetaBoxDescriptionsTest extends WP_UnitTestCase {

	public function test_every_field_description_has_balanced_markup(): void {
		$boxes   = apply_filters( 'rwmb_meta_boxes', array() );
		$checked = 0;

		$this->assertNotEmpty( $boxes );
		foreach ( $boxes as $box ) {
			foreach ( $this->walk( $box['fields'] ?? array() ) as $field ) {
				foreach ( array( 'desc', 'description' ) as $key ) {
					if ( empty( $field[ $key ] ) || ! is_string( $field[ $key ] ) ) {
						continue;
					}
					$desc = $field[ $key ];

					// force_balance_tags() appends a closing tag for every element
					// left open. It also normalises harmless markup (<br> becomes
					// <br />), so the strings are not compared directly: instead
					// the balanced copy must contain no more closing tags than the
					// original.
					$balanced = force_balance_tags( $desc );
					$this->assertSame(
						substr_count( $desc, '</' ),
						substr_count( $balanced, '</' ),
						sprintf(
							'Box %s, field %s: unbalanced HTML in the %s (write tag names as &lt;tag&gt; entities): %s',
							$box['id'] ?? '?',
							$field['id'] ?? '?',
							$key,
							$desc
						)
					);
					++$checked;
				}
			}
		}

		$this->assertGreaterThan( 20, $checked );
	}

	/**
	 * Fields including the nested fields of groups.
	 *
	 * @param array<int,array<string,mixed>> $fields Fields.
	 * @return array<int,array<string,mixed>>
	 */
	private function walk( array $fields ): array {
		$all = array();
		foreach ( $fields as $field ) {
			$all[] = $field;
			if ( ! empty( $field['fields'] ) && is_array( $field['fields'] ) ) {
				$all = array_merge( $all, $this->walk( $field['fields'] ) );
			}
		}

		return $all;
	}
}
