<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The package task must write packaged/hyperPress-<version>-<date>.zip with every entry inside a
 * hyperPress/ folder (WordPress installs to that folder) and keep dev-only files out of it.
 */
final class PackageConfigTest extends TestCase {

	private static function read( string $file ): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/' . $file );
	}

	public function test_zip_title_uses_slug_version_and_date_only(): void {
		$gulpfile = self::read( 'gulpfile.mjs' );
		$this->assertMatchesRegularExpression( "/THEME_SLUG\s*=\s*'hyperPress'/", $gulpfile );
		$this->assertStringContainsString( 'THEME_SLUG}-${pkg.version}-${', $gulpfile, 'title is slug-version-date' );
		$this->assertStringContainsString( "'yyyy-mm-dd'", $gulpfile );
		$this->assertStringNotContainsString( 'pkg.name', $gulpfile, 'the scoped npm name (@hyper/press) would create a subdirectory' );
		$this->assertDoesNotMatchRegularExpression( "/dateFormat\([^)]*'[^']*(HH|MM|hh|ss)/", $gulpfile, 'no time of day in the title' );
	}

	public function test_zip_entries_are_wrapped_in_the_theme_folder(): void {
		$gulpfile = self::read( 'gulpfile.mjs' );
		$this->assertStringContainsString( "from 'gulp-rename'", $gulpfile );
		$this->assertStringContainsString( 'join(THEME_SLUG, file.dirname)', $gulpfile );
		$this->assertLessThan(
			strpos( $gulpfile, '.pipe(zip(' ),
			strpos( $gulpfile, '.pipe(rename(' ),
			'entries must be renamed before they are zipped'
		);
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
