<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

/**
 * Bundled default branding files, keyed by role.
 *
 * @return array<string, array{file: string, mime: string}>
 */
function hyperpress_default_branding_files(): array {
	$dir = get_template_directory() . '/library/branding-assets/';

	return array(
		'logo' => array(
			'file' => $dir . 'logo.svg',
			'mime' => 'image/svg+xml',
		),
		'icon' => array(
			'file' => $dir . 'icon.png',
			'mime' => 'image/png',
		),
	);
}

/**
 * Version of the bundled branding files. Bump it when a bundled file changes.
 */
function hyperpress_default_branding_version(): string {
	return '1';
}

/**
 * The stored attachment ID for a default, only if it is still a valid tagged attachment.
 *
 * @param string $key Either 'logo' or 'icon'.
 */
function hyperpress_default_branding_id( string $key ): int {
	$ids = get_option( 'hyperpress_branding_ids', array() );
	$id  = is_array( $ids ) && isset( $ids[ $key ] ) ? (int) $ids[ $key ] : 0;

	if ( $id <= 0 || 'attachment' !== get_post_type( $id ) ) {
		return 0;
	}

	if ( get_post_meta( $id, '_hyperpress_default', true ) !== $key ) {
		return 0;
	}

	return $id;
}

/**
 * Reuse the tagged attachment for a default or create it from the bundled file.
 *
 * @param string $key Either 'logo' or 'icon'.
 * @return int Attachment ID, or 0 on failure.
 */
function hyperpress_seed_default_branding_asset( string $key ): int {
	$files = hyperpress_default_branding_files();
	if ( ! isset( $files[ $key ] ) ) {
		return 0;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'meta_key'       => '_hyperpress_default', // phpcs:ignore WordPress.DB.SlowDBQuery -- tiny lookup, runs rarely.
			'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery -- tiny lookup, runs rarely.
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
	}

	$source = $files[ $key ]['file'];
	if ( ! is_readable( $source ) ) {
		return 0;
	}

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return 0;
	}

	$filename = wp_unique_filename( $uploads['path'], basename( $source ) );
	$target   = trailingslashit( $uploads['path'] ) . $filename;

	// phpcs:ignore WordPress.WP.AlternativeFunctions -- copying a bundled theme file.
	if ( ! copy( $source, $target ) ) {
		return 0;
	}

	$id = wp_insert_attachment(
		array(
			'post_mime_type' => $files[ $key ]['mime'],
			'post_title'     => 'hyperpress-default-' . $key,
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$target
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}

	update_post_meta( $id, '_hyperpress_default', $key );

	if ( 'image/png' === $files[ $key ]['mime'] ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $target ) );
	}

	return (int) $id;
}

/**
 * Seed both default attachments under a lock.
 *
 * @return bool True when both were seeded (or already existed).
 */
function hyperpress_seed_default_branding(): bool {
	$now = time();

	// Best-effort lock only: a simultaneous first admin hit can at worst create duplicate default attachments.
	// The value is "timestamp:token"; the token lets us release only a lock we still own.
	$lock = $now . ':' . wp_generate_uuid4();

	if ( ! add_option( 'hyperpress_branding_lock', $lock, '', false ) ) {
		$locked_at = (int) get_option( 'hyperpress_branding_lock' );
		if ( ( $now - $locked_at ) < 300 ) {
			return false;
		}
		// Stale lock: take it over.
		update_option( 'hyperpress_branding_lock', $lock, false );
	}

	try {
		$ids = array(
			'logo' => hyperpress_seed_default_branding_asset( 'logo' ),
			'icon' => hyperpress_seed_default_branding_asset( 'icon' ),
		);

		update_option( 'hyperpress_branding_ids', $ids, true );

		$success = $ids['logo'] > 0 && $ids['icon'] > 0;
		if ( $success ) {
			update_option( 'hyperpress_branding_version', hyperpress_default_branding_version(), true );
		}

		return $success;
	} finally {
		if ( get_option( 'hyperpress_branding_lock' ) === $lock ) {
			delete_option( 'hyperpress_branding_lock' );
		}
	}
}

/**
 * Seed the defaults unless they are current and still present.
 */
function hyperpress_maybe_seed_default_branding(): void {
	if (
		get_option( 'hyperpress_branding_version' ) === hyperpress_default_branding_version()
		&& hyperpress_default_branding_id( 'logo' ) > 0
		&& hyperpress_default_branding_id( 'icon' ) > 0
	) {
		return;
	}

	hyperpress_seed_default_branding();
}

/**
 * Fall back to the default logo attachment when no custom logo is set.
 *
 * @param mixed $value The stored custom_logo theme mod.
 * @return mixed
 */
function hyperpress_default_logo_fallback( $value ) {
	if ( $value ) {
		return $value;
	}

	$id = hyperpress_default_branding_id( 'logo' );

	return $id > 0 ? $id : $value;
}

/**
 * Fall back to the default icon attachment when no site icon is set.
 *
 * @param mixed $value The stored site_icon option.
 * @return mixed
 */
function hyperpress_default_icon_fallback( $value ) {
	if ( $value ) {
		return $value;
	}

	$id = hyperpress_default_branding_id( 'icon' );

	return $id > 0 ? $id : $value;
}

/**
 * Output the bundled logo when no logo attachment exists (before the defaults are seeded).
 *
 * @param string $html The custom logo markup.
 */
function hyperpress_custom_logo_fallback( string $html ): string {
	if ( '' !== $html ) {
		return $html;
	}

	return sprintf(
		'<a href="%1$s" class="custom-logo-link" rel="home"><img class="custom-logo" src="%2$s" alt="%3$s"></a>',
		esc_url( home_url( '/' ) ),
		esc_url( get_template_directory_uri() . '/library/branding-assets/logo.svg' ),
		esc_attr( get_bloginfo( 'name' ) )
	);
}

/**
 * Print an SVG favicon link while the bundled default site icon is in effect.
 */
function hyperpress_default_icon_svg_link(): void {
	$default_id = hyperpress_default_branding_id( 'icon' );

	if ( $default_id <= 0 || (int) get_option( 'site_icon' ) !== $default_id ) {
		return;
	}

	printf(
		'<link rel="icon" type="image/svg+xml" href="%s">' . "\n",
		esc_url( get_theme_file_uri( 'library/branding-assets/icon.svg' ) )
	);
}

add_action( 'admin_init', 'hyperpress_maybe_seed_default_branding' );
add_action( 'after_switch_theme', 'hyperpress_maybe_seed_default_branding' );
add_filter( 'theme_mod_custom_logo', 'hyperpress_default_logo_fallback' );
// Core returns early via default_option_site_icon, skipping option_site_icon, when the option row is absent.
add_filter( 'option_site_icon', 'hyperpress_default_icon_fallback' );
add_filter( 'default_option_site_icon', 'hyperpress_default_icon_fallback' );
add_filter( 'get_custom_logo', 'hyperpress_custom_logo_fallback' );
add_action( 'wp_head', 'hyperpress_default_icon_svg_link', 20 );
add_action( 'login_head', 'hyperpress_default_icon_svg_link', 20 );
