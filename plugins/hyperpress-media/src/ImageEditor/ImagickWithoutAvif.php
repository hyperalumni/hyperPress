<?php
namespace HyperPress\Media\ImageEditor;

defined( 'ABSPATH' ) || exit;

/**
 * Imagick editor that declines AVIF.
 *
 * Loaded lazily by AvifViaGd::editors(), after WP_Image_Editor_Imagick exists.
 */
class ImagickWithoutAvif extends \WP_Image_Editor_Imagick {

	/**
	 * Everything but AVIF is answered by the parent. ImageMagick 6 lists AVIF
	 * as supported yet writes empty files, so the editor declines it and
	 * WordPress uses GD (or the default format) instead.
	 *
	 * @param string $mime_type Mime type to check.
	 * @return bool
	 */
	public static function supports_mime_type( $mime_type ) {
		if ( 'image/avif' === $mime_type ) {
			return false;
		}

		return parent::supports_mime_type( $mime_type );
	}
}
