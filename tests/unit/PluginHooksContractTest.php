<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class PluginHooksContractTest extends TestCase {

	private static function theme_source(): string {
		$root   = dirname( __DIR__, 2 );
		$source = '';
		$iter   = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iter as $file ) {
			$path = $file->getPathname();
			if ( 'php' !== $file->getExtension() || preg_match( '#/(vendor|node_modules|dist|packaged|tests)/#', $path ) ) {
				continue;
			}
			$source .= file_get_contents( $path ) . "\n";
		}
		return $source;
	}

	/** @dataProvider fired_filters */
	public function test_the_theme_fires_the_plugin_facing_filters( string $filter ): void {
		$this->assertMatchesRegularExpression( '/apply_filters\(\s*[\'"]' . preg_quote( $filter, '/' ) . '[\'"]/', self::theme_source(), "The theme no longer fires '$filter'" );
	}

	public static function fired_filters(): array {
		return array(
			'banner'           => array( 'hyperpress_banner_content' ),
			'breadcrumbs'      => array( 'hyperpress_breadcrumbs_content' ),
			'content template' => array( 'content_template' ),
			'labels'           => array( 'hyperpress_labels_content' ),
		);
	}
}
