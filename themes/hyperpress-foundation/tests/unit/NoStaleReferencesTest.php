<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class NoStaleReferencesTest extends TestCase {

	/** @return array<string,string> relative path => contents, theme PHP only. */
	private static function sources(): array {
		$root   = dirname( __DIR__, 2 );
		$result = array();
		$iter   = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iter as $file ) {
			$path = $file->getPathname();
			if ( 'php' !== $file->getExtension() || preg_match( '#/(vendor|node_modules|dist|packaged|tests|docs)/#', $path ) ) {
				continue;
			}
			$result[ substr( $path, strlen( $root ) + 1 ) ] = (string) file_get_contents( $path );
		}
		return $result;
	}

	/** @dataProvider removed_symbols */
	public function test_removed_symbols_are_gone( string $pattern, string $why ): void {
		$hits = array();
		foreach ( self::sources() as $path => $code ) {
			if ( preg_match( $pattern, $code ) ) {
				$hits[] = $path;
			}
		}
		$this->assertSame( array(), $hits, "$why. Still referenced in: " . implode( ', ', $hits ) );
	}

	public static function removed_symbols(): array {
		return array(
			'old utils namespace'     => array( '/HYPER_Press_Utils/', 'Utils namespace is now HyperPress\\Utils' ),
			'old control classes'     => array( '/HYPERpress_Dropdown_/', 'Controls are HyperPress\\Utils\\Controls\\*' ),
			'theme breadcrumb helper' => array( '/hyperpress_breadcrumbs_custom_post_type/', 'Builder moved to HyperPress\\Utils\\Breadcrumbs' ),
			'old season namespace'    => array( '/HYPER_Press_Season/', 'get_sorted_seasons is now \\HyperPress\\Season\\SortedSeasons::get' ),
			'old countdown key'       => array( '/[\'"]countdown[\'"]\s*\]/', "Countdown post type key is now 'hyper_countdown'" ),
		);
	}
}
