<?php
declare(strict_types=1);

namespace HyperPress\Uikit\Tests;

use HyperPress\Uikit\Assets;
use PHPUnit\Framework\TestCase;

final class AssetsTest extends TestCase {

	private const URI = 'https://example.test/wp-content/themes/hyperpress-uikit';

	private string $dir;

	protected function setUp(): void {
		$this->dir = sys_get_temp_dir() . '/hp-uikit-assets-' . bin2hex( random_bytes( 6 ) );
		mkdir( $this->dir, 0777, true );
	}

	protected function tearDown(): void {
		$this->removeDir( $this->dir );
	}

	private function removeDir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		foreach ( scandir( $dir ) as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . '/' . $item;
			is_dir( $path ) ? $this->removeDir( $path ) : unlink( $path );
		}
		rmdir( $dir );
	}

	private function writeManifest( array $manifest ): void {
		mkdir( $this->dir . '/.vite', 0777, true );
		file_put_contents( $this->dir . '/.vite/manifest.json', json_encode( $manifest ) );
	}

	private function writeMarker( string $contents ): void {
		file_put_contents( $this->dir . '/.vite-dev', $contents );
	}

	private function markerJson(): string {
		return json_encode(
			array(
				'browser'  => 'http://localhost:5173',
				'internal' => 'http://host.docker.internal:5173',
			)
		);
	}

	private function assets( ?callable $probe = null ): Assets {
		return new Assets( $this->dir, self::URI, $probe ?? static fn( string $url ): bool => false );
	}

	public function testEntryReturnsFileAndCssIncludingImportedChunks(): void {
		$this->writeManifest(
			array(
				'src/main.ts' => array(
					'file'    => 'assets/main-abc.js',
					'isEntry' => true,
					'css'     => array( 'assets/main-abc.css' ),
					'imports' => array( '_vendor.js', '_shared.js' ),
				),
				'_vendor.js'  => array(
					'file'    => 'assets/vendor-111.js',
					'css'     => array( 'assets/vendor-111.css', 'assets/shared-222.css' ),
					'imports' => array( '_shared.js' ),
				),
				'_shared.js'  => array(
					'file' => 'assets/shared-222.js',
					'css'  => array( 'assets/shared-222.css' ),
				),
			)
		);

		$this->assertSame(
			array(
				'file' => self::URI . '/assets/main-abc.js',
				'css'  => array(
					self::URI . '/assets/shared-222.css',
					self::URI . '/assets/vendor-111.css',
					self::URI . '/assets/main-abc.css',
				),
			),
			$this->assets()->entry( 'src/main.ts' )
		);
	}

	public function testEntryIsNullWhenManifestMissing(): void {
		$this->assertNull( $this->assets()->entry( 'src/main.ts' ) );
	}

	public function testEntryIsNullForInvalidManifestOrUnknownKey(): void {
		mkdir( $this->dir . '/.vite', 0777, true );
		file_put_contents( $this->dir . '/.vite/manifest.json', '{not json' );
		$this->assertNull( $this->assets()->entry( 'src/main.ts' ) );

		$this->writeManifestOverwrite( array( 'other.ts' => array( 'file' => 'assets/o.js' ) ) );
		$this->assertNull( $this->assets()->entry( 'src/main.ts' ) );
	}

	private function writeManifestOverwrite( array $manifest ): void {
		file_put_contents( $this->dir . '/.vite/manifest.json', json_encode( $manifest ) );
	}

	public function testNoticeWhenManifestMissing(): void {
		mkdir( $this->dir . '/vendor', 0777, true );
		file_put_contents( $this->dir . '/vendor/autoload.php', '<?php' );

		$notice = $this->assets()->notice();

		$this->assertIsString( $notice );
		$this->assertStringContainsString( '.vite/manifest.json', $notice );
		$this->assertStringNotContainsString( 'vendor/autoload.php', $notice );
	}

	public function testNoticeWhenVendorMissing(): void {
		$this->writeManifest( array( 'src/main.ts' => array( 'file' => 'assets/main.js' ) ) );

		$notice = $this->assets()->notice();

		$this->assertIsString( $notice );
		$this->assertStringContainsString( 'vendor/autoload.php', $notice );
		$this->assertStringNotContainsString( '.vite/manifest.json', $notice );
	}

	public function testNoticeIsNullWhenEverythingPresent(): void {
		$this->writeManifest( array( 'src/main.ts' => array( 'file' => 'assets/main.js' ) ) );
		mkdir( $this->dir . '/vendor', 0777, true );
		file_put_contents( $this->dir . '/vendor/autoload.php', '<?php' );

		$this->assertNull( $this->assets()->notice() );
	}

	public function testNoticeIsNullInDevMode(): void {
		$this->writeMarker( $this->markerJson() );

		$this->assertNull( $this->assets( static fn( string $url ): bool => true )->notice() );
	}

	public function testIsDevTrueWhenMarkerAndProbeSucceeds(): void {
		$this->writeMarker( $this->markerJson() );
		$probed = array();
		$assets = $this->assets(
			static function ( string $url ) use ( &$probed ): bool {
				$probed[] = $url;
				return true;
			}
		);

		$this->assertTrue( $assets->isDev() );
		$this->assertSame( array( 'http://host.docker.internal:5173' ), $probed );
	}

	public function testIsDevFalseWhenMarkerButProbeFails(): void {
		$this->writeMarker( $this->markerJson() );

		$this->assertFalse( $this->assets( static fn( string $url ): bool => false )->isDev() );
	}

	public function testIsDevFalseWhenNoMarker(): void {
		$this->assertFalse( $this->assets( static fn( string $url ): bool => true )->isDev() );
	}

	public function testIsDevFalseWhenMarkerIsInvalidJson(): void {
		$this->writeMarker( '{not json' );
		$assets = $this->assets( static fn( string $url ): bool => true );

		$this->assertFalse( $assets->isDev() );
		$this->assertNull( $assets->devServer() );
	}

	public function testMarkerWithMissingKeyOrNonStringValuesIsIgnored(): void {
		$this->writeMarker( json_encode( array( 'browser' => 'http://localhost:5173' ) ) );
		$assets = $this->assets( static fn( string $url ): bool => true );
		$this->assertFalse( $assets->isDev() );
		$this->assertNull( $assets->devServer() );

		$this->writeMarker(
			json_encode(
				array(
					'browser'  => 5173,
					'internal' => array( 'x' ),
				)
			)
		);
		$this->assertFalse( $assets->isDev() );
		$this->assertNull( $assets->devServer() );
	}

	public function testDevServerReturnsBothUrls(): void {
		$this->writeMarker(
			json_encode(
				array(
					'browser'  => 'http://localhost:5199',
					'internal' => 'http://host.docker.internal:5199',
				)
			)
		);

		$this->assertSame(
			array(
				'browser'  => 'http://localhost:5199',
				'internal' => 'http://host.docker.internal:5199',
			),
			$this->assets()->devServer()
		);
	}

	/**
	 * The VITE_DEV constant forces dev mode without probing.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testIsDevTrueWhenConstantDefined(): void {
		define( 'VITE_DEV', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- The theme reads this public, unprefixed constant from wp-config.php.

		$this->assertTrue( $this->assets( static fn( string $url ): bool => false )->isDev() );
	}

	/**
	 * Without a marker file the VITE_DEV constant yields the default dev server URLs.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testDevServerFallsBackToDefaultsWhenConstantDefined(): void {
		define( 'VITE_DEV', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- The theme reads this public, unprefixed constant from wp-config.php.

		$this->assertSame(
			array(
				'browser'  => 'http://localhost:5173',
				'internal' => 'http://host.docker.internal:5173',
			),
			$this->assets()->devServer()
		);
	}

	public function testProbeUrlAppendsTheViteClientPath(): void {
		$this->assertSame( 'http://host.docker.internal:5173/@vite/client', Assets::probeUrl( 'http://host.docker.internal:5173' ) );
	}

	public function testProbeUrlIgnoresATrailingSlash(): void {
		$this->assertSame( 'http://host.docker.internal:5173/@vite/client', Assets::probeUrl( 'http://host.docker.internal:5173/' ) );
	}

	public function testIsDevProbesAtMostOncePerInstance(): void {
		$this->writeMarker( $this->markerJson() );
		$calls  = 0;
		$assets = $this->assets(
			static function ( string $url ) use ( &$calls ): bool {
				++$calls;
				return true;
			}
		);

		$this->assertTrue( $assets->isDev() );
		$this->assertTrue( $assets->isDev() );
		$this->assertNull( $assets->notice() );
		$this->assertNotNull( $assets->devServer() );
		$this->assertSame( 1, $calls );
	}
}
