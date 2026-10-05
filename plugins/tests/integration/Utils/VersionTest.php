<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Version;
use WP_UnitTestCase;

final class VersionTest extends WP_UnitTestCase {

	private function temp_plugin( string $header ): string {
		$file = sys_get_temp_dir() . '/hyperpress-version-' . wp_generate_password( 12, false ) . '.php';
		file_put_contents( $file, "<?php\n/*\n * Plugin Name: Temp\n{$header}\n */\n" );
		return $file;
	}

	public function test_it_returns_the_version_header_of_a_file(): void {
		$file     = dirname( __DIR__, 3 ) . '/hyperpress-utils/hyperpress-utils.php';
		$expected = get_file_data( $file, array( 'version' => 'Version' ) )['version'];

		$this->assertNotSame( '', $expected );
		$this->assertSame( $expected, Version::of( $file ) );
	}

	public function test_a_second_call_does_not_read_the_file_again(): void {
		$file = $this->temp_plugin( ' * Version: 7.8.9' );

		$this->assertSame( '7.8.9', Version::of( $file ) );
		unlink( $file );
		$this->assertSame( '7.8.9', Version::of( $file ) );
	}

	public function test_a_missing_file_returns_an_empty_string(): void {
		$this->assertSame( '', Version::of( sys_get_temp_dir() . '/hyperpress-does-not-exist.php' ) );
	}

	public function test_a_file_without_a_version_header_returns_an_empty_string(): void {
		$file = $this->temp_plugin( '' );

		$this->assertSame( '', Version::of( $file ) );
		unlink( $file );
	}
}
