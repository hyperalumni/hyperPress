<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class PluginsListTest extends TestCase {

	private static function source(): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/library/plugins.php' );
	}

	public function test_hyper_plugins_are_not_registered_with_tgm(): void {
		$this->assertDoesNotMatchRegularExpression( "/'slug'\s*=>\s*'hyperpress-/", self::source(), 'HYPER plugins declare their dependencies via Requires Plugins' );
	}

	public function test_no_api_key_is_committed(): void {
		$this->assertDoesNotMatchRegularExpression( '/api_key\s*=/i', self::source() );
		$this->assertDoesNotMatchRegularExpression( '/[?&](token|key|api_key|apikey)=[A-Za-z0-9]{16,}/i', self::source() );
	}
}
