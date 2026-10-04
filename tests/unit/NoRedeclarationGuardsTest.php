<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class NoRedeclarationGuardsTest extends TestCase {

	public function test_theme_functions_are_not_wrapped_in_redeclaration_guards(): void {
		$root = dirname( __DIR__, 2 );
		$hits = array();
		$iter = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root . '/library', \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iter as $file ) {
			$path = $file->getPathname();
			if ( 'php' !== $file->getExtension() || false !== strpos( $path, 'class-tgm-plugin-activation' ) ) {
				continue;
			}
			// `if ( ! function_exists( 'x' ) ) :` whose block defines function x (other statements may sit in between).
			$pattern = '/if\s*\(\s*!\s*function_exists\(\s*[\'"](\w+)[\'"]\s*\)\s*\)\s*(?::|\{)(?:(?!function_exists)[\s\S])*?\bfunction\s+\1\s*\(/';
			if ( preg_match_all( $pattern, (string) file_get_contents( $path ), $m ) ) {
				foreach ( $m[1] as $name ) {
					$hits[] = substr( $path, strlen( $root ) + 1 ) . ": $name";
				}
			}
		}
		sort( $hits );
		$this->assertSame( array(), $hits, "Redeclaration guards remain:\n" . implode( "\n", $hits ) );
	}
}
