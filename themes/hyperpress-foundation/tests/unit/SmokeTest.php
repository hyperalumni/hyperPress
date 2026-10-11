<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase {
	public function test_runs_on_php_85(): void {
		$this->assertGreaterThanOrEqual( 80500, PHP_VERSION_ID );
	}
}
