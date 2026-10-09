<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The package task must write packaged/hyperPress.zip (WordPress names the theme folder after the zip)
 * and keep dev-only files out of it.
 */
final class PackageConfigTest extends TestCase {

	private static function read( string $file ): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/' . $file );
	}

	public function test_zip_name_is_fixed_and_not_derived_from_package_name(): void {
		$gulpfile = self::read( 'gulpfile.mjs' );
		$this->assertStringNotContainsString( 'pkg.name', $gulpfile, 'the scoped npm name (@hyper/press) would create a subdirectory' );
		$this->assertMatchesRegularExpression( "/THEME_SLUG\s*=\s*'hyperPress'/", $gulpfile );
		$this->assertStringContainsString( "THEME_SLUG + '.zip'", $gulpfile );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function excluded_paths(): array {
		$paths = array(
			'vendor/**',
			'tests/**',
			'docs/**',
			'gulpfile.mjs',
			'phpunit.xml.dist',
			'pnpm-lock.yaml',
			'pnpm-workspace.yaml',
			'AGENTS.md',
			'Dockerfile',
			'compose.yaml',
			'config-default.yml',
		);
		$cases = array();
		foreach ( $paths as $path ) {
			$cases[ $path ] = array( $path );
		}
		return $cases;
	}

	/**
	 * @dataProvider excluded_paths
	 */
	public function test_package_paths_exclude_dev_files( string $path ): void {
		$this->assertStringContainsString( '    - "!' . $path . '"', self::read( 'config-default.yml' ) );
	}
}
