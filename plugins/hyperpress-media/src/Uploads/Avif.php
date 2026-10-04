<?php
namespace HyperPress\Media\Uploads;

defined( 'ABSPATH' ) || exit;

final class Avif {

	public static function register(): void {
		add_filter( 'upload_mimes', array( self::class, 'upload_mimes' ) );
		add_filter( 'wp_check_filetype_and_ext', array( self::class, 'check_filetype_and_ext' ), 10, 3 );
	}

	public static function upload_mimes( $mimes ) {
		$mimes['avif'] = 'image/avif';

		return $mimes;
	}

	public static function check_filetype_and_ext( $results, $mime, $type ) {
		if ( $type == 'image' && $mime == 'image/avif' ) {
			$results['ext']             = 'avif';
			$results['type']            = 'image/avif';
			$results['proper_filename'] = true;
		}

		return $results;
	}
}
