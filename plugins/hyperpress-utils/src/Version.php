<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Version {

	/**
	 * Version header by main plugin file, read once per request.
	 *
	 * @var array<string, string>
	 */
	private static array $cache = array();

	/**
	 * The `Version` header of a plugin main file, or '' when the file or the header is missing.
	 *
	 * @param string $plugin_file Absolute path of the plugin main file.
	 */
	public static function of( string $plugin_file ): string {
		if ( ! isset( self::$cache[ $plugin_file ] ) ) {
			self::$cache[ $plugin_file ] = is_readable( $plugin_file )
				? get_file_data( $plugin_file, array( 'version' => 'Version' ) )['version']
				: '';
		}
		return self::$cache[ $plugin_file ];
	}
}
