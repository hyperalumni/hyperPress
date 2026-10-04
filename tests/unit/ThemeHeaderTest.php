<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class ThemeHeaderTest extends TestCase {

	private static function headers(): array {
		$source = (string) file_get_contents( dirname( __DIR__, 2 ) . '/style.css', false, null, 0, 4096 );
		$fields = array();
		foreach ( preg_split( '/\R/', $source ) as $line ) {
			if ( preg_match( '/^[\s*]*([A-Za-z][A-Za-z0-9 ]*?):\s*(.*?)\s*$/', $line, $m ) ) {
				$fields[ $m[1] ] = $m[2];
			}
		}
		return $fields;
	}

	public function test_required_fields(): void {
		$headers = self::headers();
		foreach ( array( 'Theme Name', 'Author', 'Description', 'Version', 'License', 'License URI', 'Text Domain', 'Domain Path' ) as $field ) {
			$this->assertNotEmpty( $headers[ $field ] ?? '', "style.css header '$field' missing or empty" );
		}
		$this->assertSame( '8.3', $headers['Requires PHP'] ?? null );
		$this->assertSame( '6.9', $headers['Requires at least'] ?? null );
		$this->assertSame( 'hyperpress', $headers['Text Domain'] ?? null );
	}

	public function test_no_github_updater_header(): void {
		$headers = self::headers();
		foreach ( array_keys( $headers ) as $field ) {
			$this->assertStringNotContainsString( 'GitHub', $field, "style.css header '$field' must be removed" );
		}
	}
}
