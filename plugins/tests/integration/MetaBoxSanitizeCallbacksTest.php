<?php
namespace HyperPress\Tests\Integration;

use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use WP_UnitTestCase;

/**
 * Meta Box's RWMB_Sanitizer::sanitize calls a field's sanitize_callback with
 * four arguments: ( $value, $field, $old_value, $object_id ). A built-in with
 * extra optional parameters (such as esc_url_raw( $url, $protocols )) would
 * receive the field array in one of them and throw a TypeError.
 */
final class MetaBoxSanitizeCallbacksTest extends WP_UnitTestCase {

	public function test_every_field_sanitize_callback_tolerates_the_arguments_meta_box_passes(): void {
		$boxes   = apply_filters( 'rwmb_meta_boxes', array() );
		$checked = 0;

		$this->assertNotEmpty( $boxes );
		foreach ( $boxes as $box ) {
			foreach ( $this->walk( $box['fields'] ?? array() ) as $field ) {
				if ( empty( $field['sanitize_callback'] ) || ! is_callable( $field['sanitize_callback'] ) ) {
					continue;
				}
				$value = $this->value_for( $field['sanitize_callback'] );
				// Meta Box normalises every field before sanitizing it, which adds
				// array settings (attributes, js_options, ...). Meta Box is not
				// installed in the test environment, so add representative ones;
				// without nested arrays esc_url_raw( $url, $field ) would not fail.
				$passed = $field + array(
					'attributes' => array(),
					'js_options' => array(),
					'query_args' => array(),
				);
				try {
					call_user_func( $field['sanitize_callback'], $value, $passed, '', 'object-id' );
				} catch ( Throwable $e ) {
					$this->fail( sprintf( 'Box %s, field %s: %s', $box['id'] ?? '?', $field['id'] ?? '?', $e->getMessage() ) );
				}
				++$checked;
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

	/**
	 * A representative value: an array when the callback's first parameter is
	 * declared as one (a strict-typed sanitizer), otherwise a string.
	 *
	 * @param callable $callback Sanitize callback.
	 * @return mixed
	 */
	private function value_for( $callback ) {
		if ( is_array( $callback ) ) {
			$reflection = new ReflectionMethod( $callback[0], $callback[1] );
		} else {
			$reflection = new ReflectionFunction( $callback );
		}
		$first = $reflection->getParameters()[0] ?? null;
		$type  = $first ? $first->getType() : null;
		if ( $type instanceof ReflectionNamedType && 'array' === $type->getName() ) {
			return array();
		}

		return 'https://example.test/path';
	}
}
