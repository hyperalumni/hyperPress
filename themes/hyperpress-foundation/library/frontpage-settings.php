<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

/**
 * Read one value from the Front Page settings (the `hyperpress_frontpage` option, saved by the hyperpress-frontpage plugin).
 *
 * The theme must work with that plugin inactive, so this reads the plain option and takes the full field id
 * (for example `hyperpress_frontpage_banner_title`) instead of using the plugin's constants.
 *
 * @param string $field   Full field id, a key of the option array.
 * @param mixed  $fallback Returned when the option is not an array or the key is missing.
 * @return mixed
 */
function hyperpress_frontpage_setting( string $field, $fallback = '' ) {
	$settings = get_option( 'hyperpress_frontpage' );
	if ( ! is_array( $settings ) || ! array_key_exists( $field, $settings ) ) {
		return $fallback;
	}
	return $settings[ $field ];
}
