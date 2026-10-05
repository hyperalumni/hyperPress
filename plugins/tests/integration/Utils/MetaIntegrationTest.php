<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Meta;
use WP_UnitTestCase;

final class MetaIntegrationTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb'] );
		parent::tear_down();
	}

	public function test_returns_the_stored_value(): void {
		$GLOBALS['hyperpress_test_rwmb'] = array( 'hyperpress_meta_key' => 'stored' );

		$this->assertSame( 'stored', Meta::get( 'hyperpress_meta_key' ) );
		$this->assertSame( 'stored', Meta::get( 'hyperpress_meta_key', 7 ) );
	}

	public function test_returns_empty_string_for_an_unset_key(): void {
		$this->assertSame( '', Meta::get( 'hyperpress_meta_missing' ) );
	}
}
