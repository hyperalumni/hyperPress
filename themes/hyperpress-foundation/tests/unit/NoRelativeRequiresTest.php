<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class NoRelativeRequiresTest extends TestCase {

	private const SKIPPED_DIRECTORIES = array( '.git', '.idea', '.superpowers', 'vendor', 'node_modules', 'dist', 'packaged', 'tests', 'docs' );

	public function test_theme_files_are_not_loaded_through_relative_string_paths(): void {
		$root = dirname( __DIR__, 2 );
		$hits = array();

		$filter = new \RecursiveCallbackFilterIterator(
			new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ),
			static function ( \SplFileInfo $file ) use ( $root ): bool {
				$relative = substr( $file->getPathname(), strlen( $root ) + 1 );
				if ( $file->isDir() ) {
					return ! in_array( $relative, self::SKIPPED_DIRECTORIES, true );
				}
				return 'php' === $file->getExtension() && 'library/class-tgm-plugin-activation.php' !== $relative;
			}
		);

		foreach ( new \RecursiveIteratorIterator( $filter ) as $file ) {
			$relative = substr( $file->getPathname(), strlen( $root ) + 1 );
			$tokens   = token_get_all( (string) file_get_contents( $file->getPathname() ) );
			$count    = count( $tokens );
			for ( $i = 0; $i < $count; $i++ ) {
				if ( ! is_array( $tokens[ $i ] ) || ! in_array( $tokens[ $i ][0], array( T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE ), true ) ) {
					continue;
				}
				// First significant token after the keyword, skipping whitespace and an opening parenthesis.
				for ( $j = $i + 1; $j < $count; $j++ ) {
					if ( is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
						continue;
					}
					if ( '(' === $tokens[ $j ] ) {
						continue;
					}
					break;
				}
				if ( $j < $count && is_array( $tokens[ $j ] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[ $j ][0] ) {
					$path = trim( $tokens[ $j ][1], '\'"' );
					if ( 0 !== strpos( $path, '/' ) ) {
						$hits[] = $relative . ':' . $tokens[ $j ][2] . ' ' . $tokens[ $i ][1] . ' ' . $tokens[ $j ][1];
					}
				}
			}
		}

		sort( $hits );
		$this->assertSame( array(), $hits, "Relative include paths remain:\n" . implode( "\n", $hits ) );
	}
}
