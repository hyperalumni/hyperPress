<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class NoFoundationPressPrefixTest extends TestCase {

	/**
	 * Identifiers deliberately kept. Empty: every foundationpress_ function and
	 * FoundationPress_ class definition was renamed. The kept items are hook names
	 * (do_action tags) and text-domain strings, which are not definitions.
	 */
	private const KEEP = array();

	public function test_no_foundationpress_prefixed_functions_or_classes(): void {
		$root = dirname( __DIR__, 2 );
		$hits = array();
		$iter = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iter as $file ) {
			$path = $file->getPathname();
			if ( 'php' !== $file->getExtension() || preg_match( '#/(vendor|node_modules|dist|packaged|tests|docs)/#', $path ) ) {
				continue;
			}
			// Case-insensitive: the legacy classes are spelled Foundationpress_X.
			if ( preg_match_all( '/\b(?:function|class)\s+(foundationpress_\w*)/i', (string) file_get_contents( $path ), $m ) ) {
				foreach ( $m[1] as $name ) {
					if ( ! in_array( $name, self::KEEP, true ) ) {
						$hits[] = substr( $path, strlen( $root ) + 1 ) . ": $name";
					}
				}
			}
		}
		sort( $hits );
		$this->assertSame( array(), $hits, "Rename to hyperpress_ / HyperPress_Theme_:\n" . implode( "\n", $hits ) );
	}
}
