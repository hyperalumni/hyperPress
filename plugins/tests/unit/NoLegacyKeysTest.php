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
				$original = file( $file->getPathname() );
				$lines    = explode( "\n", self::strip_comments( implode( '', $original ) ) );
				foreach ( $lines as $n => $line ) {
					if ( ! preg_match( $quote, $line ) ) {
						continue;
					}
					if ( preg_match( self::CONTEXT, $line ) || $this->call_opened_above( $lines, $n ) ) {
						$offenders[] = str_replace( Convention::root() . '/', '', $file->getPathname() ) . ':' . ( $n + 1 ) . '  ' . trim( $original[ $n ] ?? $line );
					}
				}
			}
		}

		$this->assertSame( array(), $offenders, "Legacy keys still hard-coded:\n" . implode( "\n", $offenders ) );
	}

	/**
	 * Replace comment tokens with the same number of newlines so line numbers are preserved.
	 */
	private static function strip_comments( string $code ): string {
		$out = '';
		foreach ( token_get_all( $code ) as $token ) {
			if ( is_array( $token ) ) {
				$out .= in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true )
					? str_repeat( "\n", substr_count( $token[1], "\n" ) )
					: $token[1];
			} else {
				$out .= $token;
			}
		}
		return $out;
	}

	/**
	 * True when one of the previous two lines has a CONTEXT word and opens a call that it does not close.
	 *
	 * @param string[] $lines Comment-stripped source lines.
	 */
	private function call_opened_above( array $lines, int $n ): bool {
		for ( $i = max( 0, $n - 2 ); $i < $n; $i++ ) {
			if ( preg_match( self::CONTEXT, $lines[ $i ] ) && str_contains( $lines[ $i ], '(' ) && ! str_contains( $lines[ $i ], ')' ) ) {
				return true;
			}
		}
		return false;
	}
}
