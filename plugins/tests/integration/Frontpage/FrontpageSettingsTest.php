<?php
namespace HyperPress\Tests\Integration\Frontpage;

use WP_UnitTestCase;

final class FrontpageSettingsTest extends WP_UnitTestCase {

	public function test_the_unsaved_option_returns_the_defaults(): void {
		delete_option( 'hyperpress_frontpage' );

		$option = get_option( 'hyperpress_frontpage' );

		$this->assertIsArray( $option );
		$this->assertSame( array( 'post' ), $option['hyperpress_frontpage_post_types'] );
		$this->assertStringContainsString( 'Team HYPER', $option['hyperpress_frontpage_banner_subtitle'] );
	}

	public function test_an_explicit_default_wins(): void {
		delete_option( 'hyperpress_frontpage' );

		$this->assertSame( 'mine', get_option( 'hyperpress_frontpage', 'mine' ) );
		$this->assertSame( array(), get_option( 'hyperpress_frontpage', array() ) );
	}

	public function test_a_saved_option_is_returned_as_saved(): void {
		update_option( 'hyperpress_frontpage', array( 'hyperpress_frontpage_post_types' => array( 'page' ) ) );

		$this->assertSame(
			array( 'hyperpress_frontpage_post_types' => array( 'page' ) ),
			get_option( 'hyperpress_frontpage' )
		);
		delete_option( 'hyperpress_frontpage' );
	}
}
