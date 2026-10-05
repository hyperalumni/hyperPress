<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class AssetPathTest extends WP_UnitTestCase {

	/** @var string[] */
	private array $dirs = array();

	public function tear_down(): void {
		foreach ( $this->dirs as $dir ) {
			foreach ( glob( $dir . '/css/*' ) ?: array() as $file ) {
				unlink( $file );
			}
			@rmdir( $dir . '/css' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- test cleanup
			@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- test cleanup
		}
		$this->dirs = array();
		parent::tear_down();
	}

	/** Writes a css/rev-manifest.json under a fresh temp base dir; null skips the file. */
	private function base_dir_with_manifest( ?string $contents ): string {
		$base = dirname( __DIR__, 2 ) . '/.superpowers/tmp-assets-' . uniqid( '', true );
		mkdir( $base . '/css', 0777, true );
		$this->dirs[] = $base;
		if ( null !== $contents ) {
			file_put_contents( $base . '/css/rev-manifest.json', $contents );
		}
		return $base;
	}

	public function test_valid_manifest_maps_the_name(): void {
		$base = $this->base_dir_with_manifest( '{"app.css":"app-abc123.css"}' );

		$this->assertSame( 'app-abc123.css', hyperpress_asset_path( 'app.css', $base ) );
		$this->assertSame( 'other.css', hyperpress_asset_path( 'other.css', $base ) );
	}

	/** @return array<string,array{string}> */
	public static function invalid_manifests(): array {
		return array(
			'empty file'   => array( '' ),
			'json null'    => array( 'null' ),
			'json string'  => array( '"string"' ),
			'invalid json' => array( '{not json' ),
		);
	}

	/** @dataProvider invalid_manifests */
	public function test_invalid_manifest_falls_back_to_the_plain_filename( string $contents ): void {
		$base = $this->base_dir_with_manifest( $contents );

		$this->assertSame( 'app.css', hyperpress_asset_path( 'app.css', $base ) );
	}

	public function test_missing_manifest_falls_back_to_the_plain_filename(): void {
		$base = $this->base_dir_with_manifest( null );

		$this->assertSame( 'app.css', hyperpress_asset_path( 'app.css', $base ) );
	}

	public function test_manifest_is_read_once_per_request(): void {
		$base = $this->base_dir_with_manifest( '{"app.css":"app-abc123.css"}' );
		$this->assertSame( 'app-abc123.css', hyperpress_asset_path( 'app.css', $base ) );

		unlink( $base . '/css/rev-manifest.json' );

		$this->assertSame( 'app-abc123.css', hyperpress_asset_path( 'app.css', $base ), 'served from the static cache' );
	}

	public function test_default_call_without_a_manifest_dir_still_works(): void {
		$this->assertIsString( hyperpress_asset_path( 'app.css' ) );
	}
}
