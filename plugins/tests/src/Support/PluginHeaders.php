<?php
namespace HyperPress\Tests\Support;

final class PluginHeaders {

	/**
	 * Parse the first docblock of a plugin file into field => value.
	 *
	 * @return array<string,string>
	 */
	public static function parse( string $file ): array {
		$source = (string) file_get_contents( $file, false, null, 0, 8192 );
		if ( ! preg_match( '/\/\*(.*?)\*\//s', $source, $block ) ) {
			return array();
		}
		$fields = array();
		foreach ( preg_split( '/\R/', $block[1] ) as $line ) {
			if ( preg_match( '/^[\s*]*([A-Za-z][A-Za-z0-9 ]*?):\s*(.*?)\s*$/', $line, $m ) ) {
				$fields[ $m[1] ] = $m[2];
			}
		}
		return $fields;
	}

	/** @return string[] */
	public static function requires_plugins( array $headers ): array {
		if ( empty( $headers['Requires Plugins'] ) ) {
			return array();
		}
		$list = array_map( 'trim', explode( ',', $headers['Requires Plugins'] ) );
		sort( $list );
		return array_values( array_filter( $list ) );
	}
}
