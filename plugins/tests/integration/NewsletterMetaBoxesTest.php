<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class NewsletterMetaBoxesTest extends WP_UnitTestCase {

	/** @return array<int,array<string,mixed>> */
	private function newsletter_boxes(): array {
		$boxes = array();
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			if ( in_array( 'hyper_newsletter', (array) ( $box['post_types'] ?? array() ), true ) ) {
				$boxes[] = $box;
			}
		}
		return $boxes;
	}

	public function test_newsletter_boxes_exist(): void {
		$this->assertNotEmpty( $this->newsletter_boxes() );
	}

	public function test_every_newsletter_box_has_fields_and_a_valid_priority(): void {
		foreach ( $this->newsletter_boxes() as $box ) {
			$this->assertNotEmpty( $box['fields'] ?? array(), "{$box['id']} has fields" );
			if ( isset( $box['priority'] ) ) {
				$this->assertContains( $box['priority'], array( 'high', 'core', 'default', 'low' ), "{$box['id']} priority" );
			}
		}
	}

	public function test_no_box_registered_by_the_plugin_has_empty_fields(): void {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			if ( str_starts_with( (string) ( $box['id'] ?? '' ), 'hyperpress_newsletter' ) ) {
				$this->assertNotEmpty( $box['fields'] ?? array(), "{$box['id']} has fields" );
			}
		}
	}
}
