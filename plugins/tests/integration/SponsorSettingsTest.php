<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SponsorSettingsTest extends WP_UnitTestCase {

	private function sanitizer_for( string $field_id ): callable {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			foreach ( $box['fields'] ?? array() as $field ) {
				if ( $field_id === ( $field['id'] ?? '' ) ) {
					$this->assertArrayHasKey( 'sanitize_callback', $field, "$field_id has an explicit sanitize_callback" );
					$this->assertIsCallable( $field['sanitize_callback'], "$field_id sanitize_callback is a real function" );
					return $field['sanitize_callback'];
				}
			}
		}
		$this->fail( "$field_id not found" );
	}

	public function test_website_is_a_link_field(): void {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			foreach ( $box['fields'] ?? array() as $field ) {
				if ( 'hyperpress_sponsor_website' === ( $field['id'] ?? '' ) ) {
					$this->assertSame( 'link', $field['type'] );
					return;
				}
			}
		}
		$this->fail( 'hyperpress_sponsor_website not found' );
	}

	public function test_website_drops_hostile_schemes_and_keeps_https(): void {
		$callback = $this->sanitizer_for( 'hyperpress_sponsor_website' );

		$this->assertSame( array(), $callback( array( 'url' => 'javascript:alert(1)', 'title' => 'x' ) ) );
		$this->assertSame( array(), $callback( 'https://example.com/sponsor' ) );
		$this->assertSame( array(), $callback( array( 'url' => '' ) ) );
		$this->assertSame(
			array(
				'url'     => 'https://example.com/sponsor',
				'title'   => 'Our <b>sponsor</b>',
				'target'  => '_blank',
				'post_id' => 7,
			),
			$callback(
				array(
					'url'     => 'https://example.com/sponsor',
					'title'   => 'Our <b>sponsor</b><script></script>',
					'target'  => '_blank',
					'post_id' => '7',
				)
			)
		);
		$this->assertSame( '', $callback( array( 'url' => 'https://example.com/a', 'target' => 'evil' ) )['target'] );
	}

	public function test_description_strips_scripts_and_keeps_allowed_markup(): void {
		$callback = $this->sanitizer_for( 'hyperpress_sponsor_description' );

		$this->assertSame( 'x<b>ok</b>', $callback( '<script>x</script><b>ok</b>' ) );
	}
}
