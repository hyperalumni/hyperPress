<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Validation and field definition for the editor-set archive hero image.
 */
final class ArchiveImage {

	/**
	 * The attachment ID when $value is an int or numeric string naming an image attachment, else 0.
	 *
	 * @param mixed $value Raw stored value.
	 */
	public static function valid_id( $value ): int {
		if ( is_string( $value ) && ctype_digit( $value ) ) {
			$value = (int) $value;
		}
		if ( ! is_int( $value ) || $value <= 0 ) {
			return 0;
		}
		return wp_attachment_is_image( $value ) ? $value : 0;
	}

	/**
	 * Sanitize a submitted value: absint of a scalar, 0 for anything else (including an empty single_image).
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize( $value ): int {
		return is_scalar( $value ) ? absint( $value ) : 0;
	}

	/**
	 * A meta-box-aio single_image field for an archive hero image.
	 *
	 * @param string $id   Field ID.
	 * @param string $name Field label.
	 * @param string $desc Field description.
	 */
	public static function field( string $id, string $name, string $desc ): array {
		return array(
			'id'                => $id,
			'name'              => $name,
			'type'              => 'single_image',
			'desc'              => $desc,
			'sanitize_callback' => array( self::class, 'sanitize' ),
		);
	}

	/**
	 * A term's valid image, else the valid fallback, else 0.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $meta_key Term meta key holding the attachment ID.
	 * @param int    $fallback Fallback attachment ID.
	 */
	public static function term_image_id( int $term_id, string $meta_key, int $fallback ): int {
		$id = self::valid_id( get_term_meta( $term_id, $meta_key, true ) );
		return $id > 0 ? $id : self::valid_id( $fallback );
	}
}
