<?php
namespace HyperPress\Tests\Unit\Utils;

use HyperPress\Utils\Meta;
use PHPUnit\Framework\TestCase;

final class MetaTest extends TestCase {

	public function test_returns_empty_string_when_meta_box_is_not_loaded(): void {
		$this->assertFalse( function_exists( 'rwmb_meta' ), 'The unit bootstrap must not define rwmb_meta().' );
		$this->assertSame( '', Meta::get( 'hyperpress_anything' ) );
		$this->assertSame( '', Meta::get( 'hyperpress_anything', 42 ) );
	}
}
