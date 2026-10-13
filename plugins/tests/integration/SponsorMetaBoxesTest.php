<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SponsorMetaBoxesTest extends WP_UnitTestCase {

	public function test_no_box_registered_by_the_sponsor_plugin_has_empty_fields(): void {
		$found = 0;
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			if ( str_starts_with( (string) ( $box['id'] ?? '' ), 'hyperpress_sponsor' ) ) {
				++$found;
				$this->assertNotEmpty( $box['fields'] ?? array(), "{$box['id']} has fields" );
			}
		}
		$this->assertGreaterThan( 0, $found, 'the sponsor information box is registered' );
	}
}
