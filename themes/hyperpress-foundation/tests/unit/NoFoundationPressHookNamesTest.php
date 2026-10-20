<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class NoFoundationPressHookNamesTest extends TestCase {

	public function test_no_hook_call_uses_a_foundationpress_name(): void {
		$root = dirname( __DIR__, 2 );
		$hits = array();
		$iter = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iter as $file ) {
			$path = $file->getPathname();
			if ( 'php' !== $file->getExtension() || preg_match( '#/(vendor|node_modules|dist|packaged|tests)/#', $path ) ) {
				continue;
			}
			if ( preg_match_all( '/\b(?:do_action|apply_filters|add_action|add_filter|has_action|has_filter)\s*\(\s*[\'"](foundationpress_\w*)/i', (string) file_get_contents( $path ), $m ) ) {
				foreach ( $m[1] as $name ) {
					$hits[] = substr( $path, strlen( $root ) + 1 ) . ": $name";
				}
			}
		}
		sort( $hits );
		$this->assertSame( array(), $hits, "Use the hyperpress_ prefix for hook names:\n" . implode( "\n", $hits ) );
	}
}
