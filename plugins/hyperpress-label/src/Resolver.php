<?php
/**
 * Resolves which labels show for a post type.
 *
 * @package HyperPress\Label
 */

namespace HyperPress\Label;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the per-post-type settings from the Labels settings page and falls back to registry defaults.
 */
final class Resolver {

	/**
	 * Option key holding the on/off switch for a post type.
	 *
	 * @param string $post_type Post type name.
	 */
	public static function enabled_key( string $post_type ): string {
		return 'hyperpress_label_' . $post_type . '_enabled';
	}

	/**
	 * Option key holding the chosen label IDs for a post type.
	 *
	 * @param string $post_type Post type name.
	 */
	public static function labels_key( string $post_type ): string {
		return 'hyperpress_label_' . $post_type . '_labels';
	}

	/**
	 * Whether labels are on by default: any registered label lists the type as a default.
	 *
	 * @param string $post_type Post type name.
	 */
	public static function default_enabled( string $post_type ): bool {
		return array() !== self::default_labels( $post_type );
	}

	/**
	 * IDs of labels that default to this post type, in display order.
	 *
	 * @param string $post_type Post type name.
	 * @return string[]
	 */
	public static function default_labels( string $post_type ): array {
		$registry = Registry::instance();
		$ids      = array();
		foreach ( $registry->ids() as $id ) {
			if ( in_array( $post_type, $registry->default_post_types( $id ), true ) ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/**
	 * Whether a label shows on a post type.
	 *
	 * Saved IDs that are no longer registered are ignored.
	 *
	 * @param string $post_type Post type name.
	 * @param string $label_id  Label ID.
	 */
	public static function is_enabled( string $post_type, string $label_id ): bool {
		if ( ! in_array( $label_id, Registry::instance()->ids(), true ) ) {
			return false;
		}

		$saved   = Settings::saved();
		$enabled = array_key_exists( self::enabled_key( $post_type ), $saved )
			? (bool) $saved[ self::enabled_key( $post_type ) ]
			: self::default_enabled( $post_type );
		if ( ! $enabled ) {
			return false;
		}

		$labels = $saved[ self::labels_key( $post_type ) ] ?? null;
		$ids    = is_array( $labels )
			? array_values( array_map( 'strval', array_filter( $labels, 'is_scalar' ) ) )
			: self::default_labels( $post_type );

		return in_array( $label_id, $ids, true );
	}
}
