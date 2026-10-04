<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Capabilities {

	public const ROLES = array( 'administrator', 'editor' );

	/**
	 * Primitive capabilities of a post type capability object, without meta caps or `read`.
	 *
	 * @param object $cap The `cap` property of a WP_Post_Type.
	 * @return string[]
	 */
	public static function primitive_caps( object $cap ): array {
		$caps = (array) $cap;
		unset( $caps['edit_post'], $caps['read_post'], $caps['delete_post'], $caps['read'] );
		return array_values( array_unique( $caps ) );
	}

	/**
	 * Grant every primitive capability of the given post types to Administrator and Editor.
	 * Post types must already be registered. Unregistered keys are ignored.
	 *
	 * @param string[] $post_types Registered post type keys.
	 */
	public static function grant( array $post_types ): void {
		$caps = array();
		foreach ( $post_types as $post_type ) {
			$object = get_post_type_object( $post_type );
			if ( $object ) {
				$caps = array_merge( $caps, self::primitive_caps( $object->cap ) );
			}
		}
		foreach ( self::ROLES as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( array_unique( $caps ) as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Grant once per version. Call from `init` (after registration) and from activation.
	 *
	 * @param string   $option     Option that stores the last granted version, e.g. hyperpress_award_caps_version.
	 * @param string   $version    Current plugin version.
	 * @param string[] $post_types Post type keys owned by the calling plugin.
	 */
	public static function ensure( string $option, string $version, array $post_types ): void {
		if ( get_option( $option ) === $version ) {
			return;
		}
		self::grant( $post_types );
		update_option( $option, $version );
	}
}
