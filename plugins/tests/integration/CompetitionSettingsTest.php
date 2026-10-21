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
			'ftc ma'   => array( 'hyperpress_competition_ftc_ma_event_url' ),
			'first'    => array( 'hyperpress_competition_first_event_url' ),
			'frc'      => array( 'hyperpress_competition_frc_events_url' ),
			'agenda'   => array( 'hyperpress_competition_agenda_url' ),
			'stream'   => array( 'hyperpress_competition_stream_url' ),
		);
	}

	/** @dataProvider url_fields */
	public function test_link_fields_drop_hostile_schemes_and_keep_valid_links( string $field_id ): void {
		$callback = $this->sanitizer_for( $field_id );

		$this->assertSame( array(), $callback( array( 'url' => 'javascript:alert(1)' ) ) );
		$this->assertSame( array(), $callback( array( 'url' => 'data:text/html,<script>alert(1)</script>' ) ) );
		$this->assertSame(
			array(
				'url'     => 'https://example.com/event?id=1',
				'title'   => 'Event page',
				'target'  => '_blank',
				'post_id' => 7,
			),
			$callback(
				array(
					'url'     => 'https://example.com/event?id=1',
					'title'   => 'Event page',
					'target'  => '_blank',
					'post_id' => '7',
				)
			)
		);
		$this->assertSame(
			array(
				'url'     => 'https://example.com/',
				'title'   => '',
				'target'  => '',
				'post_id' => 0,
			),
			$callback( array( 'url' => 'https://example.com/' ) ),
			'missing keys fall back to empty values'
		);
		$this->assertSame(
			array(
				'url'     => 'https://example.com/',
				'title'   => 'Go <b>now</b>alert(1)',
				'target'  => '',
				'post_id' => 0,
			),
			$callback(
				array(
					'url'    => 'https://example.com/',
					'title'  => 'Go <b>now</b><script>alert(1)</script>',
					'target' => '_top',
				)
			),
			'wp_kses_post drops the script tag (not its text) and a bad target is cleared'
		);
		$this->assertSame( array(), $callback( array( 'url' => '' ) ) );
		$this->assertSame( array(), $callback( array( 'title' => 'No url' ) ) );
		$this->assertSame( array(), $callback( array( 'url' => array( 'https://example.com/' ) ) ) );
		$this->assertSame( array(), $callback( 'https://example.com/' ), 'a bare string is not a link array' );
		$this->assertSame( array(), $callback( '' ) );
		$this->assertSame( array(), $callback( null ) );
	}

	/**
	 * Mirrors Meta Box's save for a field: the field's sanitize_callback runs first (RWMB_Field::process_value),
	 * then RWMB_Field::save deletes the meta when the value is empty and stores it otherwise. The Meta Box plugin
	 * is not loaded in this suite (see tests/bootstrap-integration.php), so the two steps are reproduced here.
	 *
	 * @param mixed $submitted Raw submitted value.
	 */
	private function save_field( int $post_id, string $field_id, $submitted ): void {
		$new = ( $this->sanitizer_for( $field_id ) )( $submitted );

		if ( array() === $new || '' === $new ) {
			delete_post_meta( $post_id, $field_id );
			return;
		}
		update_post_meta( $post_id, $field_id, $new );
	}

	public function test_link_fields_persist_the_submitted_link_and_clear_when_the_url_is_emptied(): void {
		$post_id  = self::factory()->post->create( array( 'post_type' => 'hyper_competition' ) );
		$field_id = 'hyperpress_competition_stream_url';

		$this->save_field(
			$post_id,
			$field_id,
			array(
				'url'     => 'https://example.com/stream',
				'title'   => 'Watch',
				'target'  => '_blank',
				'post_id' => '',
			)
		);
		$this->assertSame(
			array(
				'url'     => 'https://example.com/stream',
				'title'   => 'Watch',
				'target'  => '_blank',
				'post_id' => 0,
			),
			get_post_meta( $post_id, $field_id, true )
		);

		$this->save_field( $post_id, $field_id, array( 'url' => '', 'title' => '', 'target' => '', 'post_id' => '' ) );
		$this->assertSame( '', get_post_meta( $post_id, $field_id, true ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, $field_id ), 'an emptied link deletes the meta' );
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
