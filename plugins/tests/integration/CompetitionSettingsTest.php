<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class CompetitionSettingsTest extends WP_UnitTestCase {

	private function sanitizer_for( string $field_id ): callable {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			foreach ( $box['fields'] ?? array() as $field ) {
				if ( $field_id === ( $field['id'] ?? '' ) ) {
					$this->assertArrayHasKey( 'sanitize_callback', $field, "$field_id has an explicit sanitize_callback" );
					return $field['sanitize_callback'];
				}
			}
		}
		$this->fail( "$field_id not found" );
	}

	/** @return array<string,array{0:string}> */
	public static function url_fields(): array {
		return array(
			'ne first' => array( 'hyperpress_competition_ne_first_event_url' ),
			'first'    => array( 'hyperpress_competition_first_event_url' ),
			'stream'   => array( 'hyperpress_competition_stream_url' ),
		);
	}

	/** @dataProvider url_fields */
	public function test_url_fields_drop_hostile_schemes_and_keep_valid_urls( string $field_id ): void {
		$callback = $this->sanitizer_for( $field_id );

		$this->assertSame( '', $callback( 'javascript:alert(1)' ) );
		$this->assertSame( '', $callback( "data:text/html,<script>alert(1)</script>" ) );
		$this->assertSame( 'https://example.com/event?id=1', $callback( 'https://example.com/event?id=1' ) );
		$this->assertSame( '', $callback( '' ) );
	}

	public function test_robot_field_only_accepts_the_id_of_a_robot(): void {
		$callback = $this->sanitizer_for( 'hyperpress_competition_robot' );
		$robot    = self::factory()->post->create( array( 'post_type' => 'hyper_robot' ) );
		$other    = self::factory()->post->create( array( 'post_type' => 'post' ) );

		$this->assertSame( $robot, $callback( (string) $robot ) );
		$this->assertSame( 0, $callback( (string) $other ) );
		$this->assertSame( 0, $callback( '999999' ) );
		$this->assertSame( 0, $callback( 'abc' ) );
		$this->assertSame( 0, $callback( '' ) );
	}

	/** @return array<string,array{0:string}> */
	public static function text_fields(): array {
		return array(
			'host'    => array( 'hyperpress_competition_host_name' ),
			'venue'   => array( 'hyperpress_competition_venue_name' ),
			'address' => array( 'hyperpress_competition_venue_address' ),
		);
	}

	/** @dataProvider text_fields */
	public function test_text_fields_strip_markup( string $field_id ): void {
		$callback = $this->sanitizer_for( $field_id );

		$this->assertSame( 'Field House', $callback( '<script>alert(1)</script><b>Field House</b>' ) );
		$this->assertSame( '1 Main St', $callback( "  1 Main St\n" ) );
	}

	public function test_map_field_keeps_only_lat_lng_zoom(): void {
		$callback = $this->sanitizer_for( 'hyperpress_competition_venue_map' );

		$this->assertSame( '42.25228605221164,-71.00051344845554,15', $callback( '42.25228605221164,-71.00051344845554,15' ) );
		$this->assertSame( '42.25,-71.0', $callback( '42.25,-71.0' ), 'zoom is optional' );
		$this->assertSame( '42.25228605221164, -71.00051344845554,15', $callback( '42.25228605221164, -71.00051344845554,15' ), 'the registered default has a space' );
		$this->assertSame( '', $callback( '<script>alert(1)</script>' ) );
		$this->assertSame( '', $callback( '42.25;-71.0' ) );
		$this->assertSame( '', $callback( '42.25,-71.0,15,extra' ) );
		$this->assertSame( '', $callback( array( '42.25,-71.0' ) ) );
	}
}
