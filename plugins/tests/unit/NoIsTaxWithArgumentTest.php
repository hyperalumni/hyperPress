<?php
namespace HyperPress\Tests\Unit;

use HyperPress\Tests\Support\Convention;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * is_tax( $taxonomy ) compares the taxonomy with get_queried_object()->taxonomy, so with two taxonomies in the request
 * it can be false for one that is queried. Production code uses is_tax() (no argument) plus RequestTerm::get().
 */
final class NoIsTaxWithArgumentTest extends TestCase {

	public function test_no_plugin_calls_is_tax_with_an_argument(): void {
		$offenders = array();

		foreach ( Convention::PLUGINS as $plugin ) {
			foreach ( array( 'src', 'templates' ) as $sub ) {
				$dir = Convention::root() . '/' . $plugin . '/' . $sub;
				if ( ! is_dir( $dir ) ) {
					continue;
				}
				foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ) ) as $file ) {
					if ( 'php' !== $file->getExtension() ) {
						continue;
					}
					$relative = str_replace( Convention::root() . '/', '', $file->getPathname() );
					$tokens   = token_get_all( (string) file_get_contents( $file->getPathname() ) );
					$count    = count( $tokens );
					for ( $i = 0; $i < $count; $i++ ) {
						$token = $tokens[ $i ];
						if ( ! is_array( $token ) || T_STRING !== $token[0] || 'is_tax' !== $token[1] ) {
							continue;
						}
						$next = $this->next_significant( $tokens, $i + 1 );
						if ( null === $next || '(' !== $tokens[ $next ] ) {
							continue;
						}
						$after = $this->next_significant( $tokens, $next + 1 );
						if ( null !== $after && ')' !== $tokens[ $after ] ) {
							$offenders[] = $relative . ':' . $token[2];
						}
					}
				}
			}
		}

		$this->assertSame(
			array(),
			$offenders,
			"Use is_tax() with no argument plus RequestTerm::get( \$taxonomy ) instead of is_tax( \$taxonomy ):\n" . implode( "\n", $offenders )
		);
	}

	/**
	 * Index of the next token that is not whitespace or a comment.
	 *
	 * @param array<int,mixed> $tokens Tokens from token_get_all().
	 */
	private function next_significant( array $tokens, int $from ): ?int {
		$count = count( $tokens );
		for ( $i = $from; $i < $count; $i++ ) {
			if ( is_array( $tokens[ $i ] ) && in_array( $tokens[ $i ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}

			return $i;
		}

		return null;
	}
}
