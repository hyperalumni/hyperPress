<?php
namespace HyperPress\Tests\Unit;

use HyperPress\Tests\Support\Convention;
use HyperPress\Tests\Support\PluginHeaders;
use PHPUnit\Framework\TestCase;

final class PluginHeadersTest extends TestCase {

	/** @return array<string,array{string}> */
	public static function plugins(): array {
		$cases = array();
		foreach ( Convention::PLUGINS as $plugin ) {
			$cases[ $plugin ] = array( $plugin );
		}
		return $cases;
	}

	/** @dataProvider plugins */
	public function test_main_file_is_named_after_folder( string $plugin ): void {
		$this->assertFileExists( Convention::main_file( $plugin ), "$plugin/$plugin.php must exist" );
	}

	/** @dataProvider plugins */
	public function test_required_fields_are_present_and_non_empty( string $plugin ): void {
		$headers = PluginHeaders::parse( Convention::main_file( $plugin ) );
		foreach ( array( 'Plugin Name', 'Description', 'Author', 'License', 'License URI', 'Text Domain', 'Domain Path' ) as $field ) {
			$this->assertNotEmpty( $headers[ $field ] ?? '', "$plugin: header '$field' missing or empty" );
		}
	}

	/** @dataProvider plugins */
	public function test_no_github_updater_header( string $plugin ): void {
		$headers = PluginHeaders::parse( Convention::main_file( $plugin ) );
		$this->assertArrayNotHasKey( 'GitHub Plugin URI', $headers, "$plugin: GitHub Plugin URI must be removed" );
	}

	/** @dataProvider plugins */
	public function test_php_and_wordpress_floors( string $plugin ): void {
		$headers = PluginHeaders::parse( Convention::main_file( $plugin ) );
		$this->assertSame( Convention::REQUIRES_PHP, $headers['Requires PHP'] ?? null, "$plugin: Requires PHP" );
		$this->assertSame( Convention::REQUIRES_AT_LEAST, $headers['Requires at least'] ?? null, "$plugin: Requires at least" );
	}

	/** @dataProvider plugins */
	public function test_requires_plugins_matches_table( string $plugin ): void {
		$headers  = PluginHeaders::parse( Convention::main_file( $plugin ) );
		$expected = Convention::REQUIRES_PLUGINS[ $plugin ];
		sort( $expected );
		$this->assertSame( $expected, PluginHeaders::requires_plugins( $headers ), "$plugin: Requires Plugins" );
	}

	public function test_requires_table_covers_every_plugin(): void {
		$this->assertEqualsCanonicalizing( Convention::PLUGINS, array_keys( Convention::REQUIRES_PLUGINS ) );
		$this->assertEqualsCanonicalizing( Convention::PLUGINS, Convention::LOAD_ORDER );
	}
}
