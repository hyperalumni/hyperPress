<?php
namespace HyperPress\Media\Uploads;

defined( 'ABSPATH' ) || exit;

final class Svg {

	public static function register(): void {
		add_filter( 'wp_check_filetype_and_ext', array( self::class, 'check_filetype_and_ext' ), 10, 4 );
		add_filter( 'upload_mimes', array( self::class, 'upload_mimes' ) );
	}

	public static function check_filetype_and_ext( $data, $file, $filename, $mimes ) {

		global $wp_version;
		if ( '4.7.1' !== $wp_version ) {
			return $data;
		}

		$filetype = wp_check_filetype( $filename, $mimes );

		return array(
			'ext'             => $filetype['ext'],
			'type'            => $filetype['type'],
			'proper_filename' => $data['proper_filename'],
		);
	}

	public static function upload_mimes( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}
}
