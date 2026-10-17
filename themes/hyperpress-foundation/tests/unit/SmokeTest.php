<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase {
	public function test_runs_on_a_supported_php(): void {
		$this->assertGreaterThanOrEqual( 80300, PHP_VERSION_ID, 'The suite needs PHP 8.3 or newer.' );
	}
}
