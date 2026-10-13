<?php
namespace HyperPress\Media\ImageEditor;

defined( 'ABSPATH' ) || exit;

final class AvifViaGd {

	private const IMAGICK  = 'WP_Image_Editor_Imagick';
	private const REPLACED = 'HyperPress\Media\ImageEditor\ImagickWithoutAvif';

	public static function register(): void {
		add_filter( 'wp_image_editors', array( self::class, 'editors' ), 20 );
	}

	/**
	 * Swap the Imagick editor for one that declines AVIF, so WordPress writes
	 * AVIF with GD (ImageMagick 6 lists AVIF but writes empty files).
	 *
	 * The swap is applied only when the filter `hyperpress_media_avif_via_gd`,
	 * which defaults to `function_exists( 'imageavif' )`, is true. Return false
	 * from it to keep the stock editor list.
	 *
	 * @param array $editors Image editor class names.
	 * @return array
	 */
	public static function editors( array $editors ): array {
		/**
		 * Whether to write AVIF with GD instead of Imagick.
		 *
		 * @param bool $enabled Defaults to whether GD can write AVIF.
		 */
		if ( ! apply_filters( 'hyperpress_media_avif_via_gd', function_exists( 'imageavif' ) ) ) {
			return $editors;
		}

		if ( ! in_array( self::IMAGICK, $editors, true ) ) {
			return $editors;
		}

		// The subclass needs its parent, so load it only now.
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';
		require_once __DIR__ . '/ImagickWithoutAvif.php';

		return array_map(
			static fn( $editor ) => self::IMAGICK === $editor ? self::REPLACED : $editor,
			$editors
		);
	}
}
