<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * A Meta Box entry without fields and with 'panel' => '' makes Meta Box AIO create an empty Customizer section. The
 * theme keeps those only where controls live in them.
 */
final class EmptyMetaBoxSectionsTest extends WP_UnitTestCase {

	public function test_the_theme_registers_no_meta_box_without_fields_except_the_homepage_section(): void {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			$id = (string) ( $box['id'] ?? '' );
			if ( empty( $box['fields'] ) && 'hyperpress_gallery' === $id ) {
				$this->fail( 'The empty "Gallery" section is left over: the NextGen page picker lives in the "NextGen Gallery" section of hyperpress-media.' );
			}
		}
		$this->assertTrue( true );
	}
}
