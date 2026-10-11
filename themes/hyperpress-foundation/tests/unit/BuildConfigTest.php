<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The tracked default config must build on its own: every webpack entry has to exist,
 * otherwise webpackBuild fails with "File not found with singular glob".
 */
final class BuildConfigTest extends TestCase {

	public function test_every_webpack_entry_in_the_default_config_exists(): void {
		$root  = dirname( __DIR__, 2 );
		$lines = preg_split( '/\R/', (string) file_get_contents( $root . '/config-default.yml' ) );
		$this->assertIsArray( $lines );

		$entries  = array();
		$in_block = false;
		foreach ( $lines as $line ) {
			if ( ! $in_block ) {
				$in_block = (bool) preg_match( '/^\s+entries:\s*$/', $line );
				continue;
			}
			if ( ! preg_match( '/^\s+-\s+"([^"]+)"/', $line, $match ) ) {
				break;
			}
			$entries[] = $match[1];
		}

		$this->assertNotEmpty( $entries, 'PATHS.entries must list at least one webpack entry' );
		foreach ( $entries as $entry ) {
			$this->assertNotEmpty( glob( $root . '/' . $entry ), "webpack entry does not exist: {$entry}" );
		}
	}
}
