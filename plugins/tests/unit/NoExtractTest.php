<?php
namespace HyperPress\Tests\Unit;

use HyperPress\Tests\Support\Convention;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * extract() turns shortcode attributes into local variables, which hides where values come from and can overwrite
 * existing variables. Attributes are read from the array instead.
 */
final class NoExtractTest extends TestCase {

	public function test_no_plugin_calls_extract(): void {
		$offenders = array();

		foreach ( Convention::PLUGINS as $plugin ) {
			$dir = Convention::root() . '/' . $plugin;
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ) ) as $file ) {
				if ( 'php' !== $file->getExtension() ) {
					continue;
				}
				foreach ( token_get_all( (string) file_get_contents( $file->getPathname() ) ) as $i => $token ) {
					if ( is_array( $token ) && T_STRING === $token[0] && 'extract' === $token[1] ) {
						$offenders[] = str_replace( Convention::root() . '/', '', $file->getPathname() ) . ':' . $token[2];
					}
				}
			}
		}

		$this->assertSame( array(), $offenders, "extract() is still called:\n" . implode( "\n", $offenders ) );
	}
}
