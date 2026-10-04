<?php
namespace HyperPress\Tests\Unit;

use HyperPress\Tests\Support\Convention;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class NoLegacyKeysTest extends TestCase {

	private const CONTEXT = '/(register_post_type|register_taxonomy|is_singular|is_post_type_archive|is_tax|get_post_type|get_terms|wp_get_object_terms|get_the_terms|post_types|\'post_type\'|\'taxonomy\'|object_type|WP_Query)/';

	public function test_no_hard_coded_legacy_keys(): void {
		$old   = array_map( 'preg_quote', array_keys( Convention::OLD_TO_NEW ) );
		$quote = '/[\'"](' . implode( '|', $old ) . ')[\'"]/';

		$offenders = array();
		foreach ( Convention::PLUGINS as $plugin ) {
			if ( ! is_dir( Convention::root() . '/' . $plugin ) ) {
				continue; // Plugins not yet extracted from the theme (e.g. hyperpress-media) have no directory.
			}
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( Convention::root() . '/' . $plugin, RecursiveDirectoryIterator::SKIP_DOTS ) );
			foreach ( $iterator as $file ) {
				if ( 'php' !== $file->getExtension() || str_contains( $file->getPathname(), '/vendor/' ) ) {
					continue;
				}
				foreach ( file( $file->getPathname() ) as $n => $line ) {
					if ( preg_match( self::CONTEXT, $line ) && preg_match( $quote, $line ) ) {
						$offenders[] = str_replace( Convention::root() . '/', '', $file->getPathname() ) . ':' . ( $n + 1 ) . '  ' . trim( $line );
					}
				}
			}
		}

		$this->assertSame( array(), $offenders, "Legacy keys still hard-coded:\n" . implode( "\n", $offenders ) );
	}
}
