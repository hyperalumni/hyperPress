<?php
namespace HyperPress\Tests\Unit;

use HyperPress\Tests\Support\Convention;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * get_queried_object() returns an arbitrary taxonomy's term when a request carries two taxonomies, so a term is read
 * with RequestTerm::get(), which reads the taxonomy's own query var. Only code that does not read a term may call it.
 */
final class NoQueriedObjectTest extends TestCase {

	/**
	 * Files allowed to call get_queried_object(), each with the reason.
	 *
	 * @var array<string,string>
	 */
	private const ALLOWED = array(
		'hyperpress-utils/src/RequestTerm.php'  => 'the one place that documents why it is avoided',
		'hyperpress-label/src/Renderer.php'     => 'reads the queried post on a singular request, not a taxonomy term',
	);

	public function test_no_plugin_reads_a_term_through_get_queried_object(): void {
		$offenders = array();

		foreach ( Convention::PLUGINS as $plugin ) {
			$dir = Convention::root() . '/' . $plugin . '/src';
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ) ) as $file ) {
				if ( 'php' !== $file->getExtension() ) {
					continue;
				}
				$relative = str_replace( Convention::root() . '/', '', $file->getPathname() );
				if ( isset( self::ALLOWED[ $relative ] ) ) {
					continue;
				}
				foreach ( token_get_all( (string) file_get_contents( $file->getPathname() ) ) as $token ) {
					if ( is_array( $token ) && T_STRING === $token[0] && 'get_queried_object' === $token[1] ) {
						$offenders[] = $relative . ':' . $token[2];
					}
				}
			}
		}

		$this->assertSame( array(), $offenders, "Use RequestTerm::get() instead of get_queried_object():\n" . implode( "\n", $offenders ) );
	}
}
