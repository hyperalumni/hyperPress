<?php
/**
 * Point NextGEN galleries and albums at the parent theme's `nggallery/` templates.
 *
 * NextGEN keeps the chosen template as an ABSOLUTE file path in three places, and this script
 * rewrites all of them from NextGEN's own legacy templates to
 * `get_template_directory() . '/nggallery/<name>.php'`:
 *
 *  - posts:     `displayed_gallery` posts. `post_content` and `post_content_filtered` hold
 *               `base64_encode( json_encode( $data ) )`; `display_settings->template` and
 *               `->gallery_display_template` are the paths.
 *  - albums:    `{prefix}ngg_album.display_type_settings` (primary key `id`).
 *  - galleries: `{prefix}ngg_gallery.display_type_settings` (primary key `gid`).
 *               Both hold `base64_encode( json )` (or legacy PHP `serialize()` text, which NextGEN
 *               still reads but never writes; it is rewritten as base64 JSON) of an object keyed by
 *               display type name, each value holding that display type's settings, including
 *               `template` and `gallery_display_template`.
 *
 * It edits the database: TAKE A BACKUP FIRST (`wp db export`). It is a dry run unless you say `apply`.
 * NextGEN has no cache API for the album and gallery tables, so FLUSH PAGE CACHES afterwards.
 *
 *   wp eval-file plugins/bin/nextgen-set-template.php [dry-run|apply] [posts|albums|galleries ...] [<template.php> ...]
 *
 * Templates default to `album-compact.php` only; sources default to all three. Positional arguments
 * reach the file as `$args`.
 *
 * @package hyperpress
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_CLI' ) && 'cli' !== PHP_SAPI ) {
	exit;
}

if ( ! function_exists( 'hyperpress_ngg_decode' ) ) {
	/**
	 * Decodes NextGEN's base64 JSON into objects (so `{}` and `[]` survive a re-encode).
	 *
	 * @param string $encoded The stored string.
	 * @return object|null The decoded top-level object, or null when not decodable or without a `display_settings` object.
	 */
	function hyperpress_ngg_decode( string $encoded ): ?object {
		if ( '' === $encoded ) {
			return null;
		}
		$json = base64_decode( $encoded, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- NextGEN stores its data this way.
		if ( false === $json || '' === $json ) {
			return null;
		}
		$data = json_decode( $json );
		if ( ! is_object( $data ) || ! isset( $data->display_settings ) || ! is_object( $data->display_settings ) ) {
			return null;
		}
		return $data;
	}
}

if ( ! function_exists( 'hyperpress_ngg_basename' ) ) {
	/**
	 * Basename that understands both `/` and `\` separators.
	 *
	 * @param string $path A file path.
	 * @return string The last path segment.
	 */
	function hyperpress_ngg_basename( string $path ): string {
		return basename( str_replace( '\\', '/', $path ) );
	}
}

if ( ! function_exists( 'hyperpress_ngg_remap_settings' ) ) {
	/**
	 * Remaps `template` and `gallery_display_template` on one settings object, in place.
	 *
	 * A value changes only when it is a non-empty string, its basename is in `$names`, it is not
	 * already the theme path, and the file exists in `$theme_dir`. Shared by posts, albums and galleries.
	 *
	 * @param object   $settings  The settings object (modified).
	 * @param string[] $names     Template file names to map, for example `album-compact.php`.
	 * @param string   $theme_dir The theme's `nggallery` directory.
	 * @return array<int, array{key: string, old: string, new: string}> The changes made.
	 */
	function hyperpress_ngg_remap_settings( object $settings, array $names, string $theme_dir ): array {
		$theme_dir = rtrim( $theme_dir, '/\\' );
		$names     = array_map( 'hyperpress_ngg_basename', $names );
		$changes   = array();

		foreach ( array( 'template', 'gallery_display_template' ) as $key ) {
			$old = $settings->$key ?? null;
			if ( ! is_string( $old ) || '' === $old ) {
				continue;
			}
			$name = hyperpress_ngg_basename( $old );
			if ( ! in_array( $name, $names, true ) ) {
				continue;
			}
			$new = $theme_dir . '/' . $name;
			if ( $old === $new || ! is_file( $new ) ) {
				continue;
			}
			$settings->$key = $new;
			$changes[]      = array(
				'key' => $key,
				'old' => $old,
				'new' => $new,
			);
		}

		return $changes;
	}
}

if ( ! function_exists( 'hyperpress_ngg_remap_encoded' ) ) {
	/**
	 * Remaps template paths inside one encoded displayed-gallery string.
	 *
	 * @param string   $encoded   The stored `base64( json )` string.
	 * @param string[] $names     Template file names to map, for example `album-compact.php`.
	 * @param string   $theme_dir The theme's `nggallery` directory.
	 * @return array{encoded: string, changes: array<int, array{key: string, old: string, new: string}>}|null
	 *         The new encoded string and the changes, or null when nothing changes or it is undecodable.
	 */
	function hyperpress_ngg_remap_encoded( string $encoded, array $names, string $theme_dir ): ?array {
		$data = hyperpress_ngg_decode( $encoded );
		if ( null === $data ) {
			return null;
		}

		$changes = hyperpress_ngg_remap_settings( $data->display_settings, $names, $theme_dir );
		if ( array() === $changes ) {
			return null;
		}

		$json = hyperpress_ngg_encode_json( $data );
		if ( false === $json ) {
			return null;
		}

		return array(
			'encoded' => base64_encode( $json ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- NextGEN stores its data this way.
			'changes' => $changes,
		);
	}
}

if ( ! function_exists( 'hyperpress_ngg_encode_json' ) ) {
	/**
	 * Plain `json_encode` with default flags, matching NextGEN's own output (`\/` escaping included).
	 *
	 * @param mixed $data Value to encode.
	 * @return string|false The JSON, or false on failure.
	 */
	function hyperpress_ngg_encode_json( $data ) {
		return json_encode( $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Must match NextGEN's encoding, not wp_json_encode().
	}
}

if ( ! function_exists( 'hyperpress_ngg_decode_type_settings' ) ) {
	/**
	 * Decodes a `display_type_settings` column value (base64 JSON, or legacy PHP `serialize()` text).
	 *
	 * JSON is decoded to objects so `{}` survives. Legacy input is read without classes and refused
	 * when it looks like it holds objects; its top level and its display-type values become objects
	 * (what NextGEN's JSON form is), anything deeper is left as the arrays PHP produced.
	 *
	 * @param string|null $value The stored column value.
	 * @return array{data: object|array<mixed>, legacy: bool}|null The decoded value, or null when empty or not decodable.
	 */
	function hyperpress_ngg_decode_type_settings( ?string $value ): ?array {
		if ( null === $value || '' === trim( $value ) ) {
			return null;
		}

		if ( 'a:' === substr( $value, 0, 2 ) ) {
			if ( 1 === preg_match( '/(O|C):\+?[0-9]+:/', $value ) ) {
				return null;
			}
			$array = @unserialize( $value, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Reads NextGEN's legacy column format, no classes allowed; failure is handled below.
			if ( ! is_array( $array ) ) {
				return null;
			}
			if ( array() === $array || array_is_list( $array ) ) {
				return array(
					'data'   => $array,
					'legacy' => true,
				);
			}
			foreach ( $array as $type => $settings ) {
				if ( is_array( $settings ) ) {
					$array[ $type ] = (object) $settings;
				}
			}
			return array(
				'data'   => (object) $array,
				'legacy' => true,
			);
		}

		$json = base64_decode( $value, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- NextGEN stores its data this way.
		if ( false === $json || '' === $json ) {
			return null;
		}
		$data = json_decode( $json );
		if ( ! is_object( $data ) && ! is_array( $data ) ) {
			return null;
		}
		return array(
			'data'   => $data,
			'legacy' => false,
		);
	}
}

if ( ! function_exists( 'hyperpress_ngg_remap_type_settings' ) ) {
	/**
	 * Remaps template paths inside a `display_type_settings` value (an object of display-type objects).
	 *
	 * @param string|null $value     The stored column value.
	 * @param string[]    $names     Template file names to map, for example `album-compact.php`.
	 * @param string      $theme_dir The theme's `nggallery` directory.
	 * @return array{encoded: string, legacy: bool, changes: array<int, array{type: string, key: string, old: string, new: string}>}|null
	 *         The new base64 JSON, whether the input was legacy serialized, and the changes; null when nothing changes or it is undecodable.
	 */
	function hyperpress_ngg_remap_type_settings( ?string $value, array $names, string $theme_dir ): ?array {
		$decoded = hyperpress_ngg_decode_type_settings( $value );
		if ( null === $decoded || ! is_object( $decoded['data'] ) ) {
			return null;
		}

		$changes = array();
		foreach ( $decoded['data'] as $type => $settings ) {
			if ( ! is_object( $settings ) ) {
				continue;
			}
			foreach ( hyperpress_ngg_remap_settings( $settings, $names, $theme_dir ) as $change ) {
				$changes[] = array( 'type' => (string) $type ) + $change;
			}
		}
		if ( array() === $changes ) {
			return null;
		}

		$json = hyperpress_ngg_encode_json( $decoded['data'] );
		if ( false === $json ) {
			return null;
		}

		return array(
			'encoded' => base64_encode( $json ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- NextGEN stores its data this way.
			'legacy'  => $decoded['legacy'],
			'changes' => $changes,
		);
	}
}

if ( ! function_exists( 'hyperpress_ngg_parse_args' ) ) {
	/**
	 * Parses the positional arguments: mode first, then template file names and source targets.
	 *
	 * @param string[] $args Positional arguments.
	 * @return array{mode: string, names: string[], targets: string[], error: string|null}
	 */
	function hyperpress_ngg_parse_args( array $args ): array {
		$all     = array( 'posts', 'albums', 'galleries' );
		$mode    = array_shift( $args ) ?? 'dry-run';
		$names   = array();
		$targets = array();
		$error   = null;

		if ( ! in_array( $mode, array( 'dry-run', 'apply' ), true ) ) {
			$error = sprintf( 'Unknown mode "%s".', $mode );
			$args  = array();
		}

		foreach ( $args as $arg ) {
			if ( '.php' === substr( $arg, -4 ) ) {
				$names[] = $arg;
			} elseif ( in_array( $arg, $all, true ) ) {
				$targets[] = $arg;
			} else {
				$error = sprintf( 'Unknown argument "%s".', $arg );
				break;
			}
		}

		return array(
			'mode'    => $mode,
			'names'   => array() === $names ? array( 'album-compact.php' ) : array_values( array_unique( $names ) ),
			'targets' => array() === $targets ? $all : array_values( array_unique( $targets ) ),
			'error'   => $error,
		);
	}
}

if ( ! function_exists( 'hyperpress_ngg_usage' ) ) {
	/**
	 * Usage text.
	 *
	 * @return string
	 */
	function hyperpress_ngg_usage(): string {
		return <<<'TXT'
Usage: wp eval-file plugins/bin/nextgen-set-template.php [dry-run|apply] [posts|albums|galleries ...] [<template.php> ...]

  dry-run   (default) list what would change; writes nothing.
  apply     write the changes. Take a backup first: wp db export before-ngg-template.sql

Sources (default: all three):
  posts      displayed_gallery posts (post_content, post_content_filtered)
  albums     {prefix}ngg_album.display_type_settings
  galleries  {prefix}ngg_gallery.display_type_settings

Templates default to album-compact.php. Name others (gallery.php, album-extend.php) to map them too.
They must exist in the parent theme's nggallery/ directory.
Flush page caches after applying: NextGEN has no cache API for the album and gallery tables.
TXT;
	}
}

if ( ! function_exists( 'hyperpress_ngg_new_stats' ) ) {
	/**
	 * Fresh counters for one source.
	 *
	 * @return array{scanned: int, changed: int, fields: int, skipped: int, failed: int}
	 */
	function hyperpress_ngg_new_stats(): array {
		return array(
			'scanned' => 0,
			'changed' => 0,
			'fields'  => 0,
			'skipped' => 0,
			'failed'  => 0,
		);
	}
}

if ( ! function_exists( 'hyperpress_ngg_run_posts' ) ) {
	/**
	 * Scans and (when applying) updates the displayed_gallery posts.
	 *
	 * @param bool     $apply Whether to write.
	 * @param string[] $names Template file names to map.
	 * @param string   $dir   The theme's `nggallery` directory.
	 * @return array{scanned: int, changed: int, fields: int, skipped: int, failed: int}
	 */
	function hyperpress_ngg_run_posts( bool $apply, array $names, string $dir ): array {
		global $wpdb;

		$stats   = hyperpress_ngg_new_stats();
		$columns = array( 'post_content', 'post_content_filtered' );
		$rows    = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_content, post_content_filtered FROM {$wpdb->posts} WHERE post_type = %s ORDER BY ID", 'displayed_gallery' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off maintenance script; the table name comes from $wpdb.

		foreach ( (array) $rows as $row ) {
			++$stats['scanned'];
			$update = array();
			$olds   = array();
			foreach ( $columns as $column ) {
				$encoded = (string) $row->$column;
				$result  = hyperpress_ngg_remap_encoded( $encoded, $names, $dir );
				if ( null === $result ) {
					if ( '' !== $encoded && null === hyperpress_ngg_decode( $encoded ) ) {
						++$stats['skipped'];
						WP_CLI::warning( sprintf( 'post ID %d %s: skipped, not decodable.', $row->ID, $column ) );
					}
					continue;
				}
				foreach ( $result['changes'] as $change ) {
					WP_CLI::log( sprintf( 'post ID %d %s %s: %s -> %s', $row->ID, $column, $change['key'], $change['old'], $change['new'] ) );
					++$stats['fields'];
				}
				$update[ $column ] = $result['encoded'];
				$olds[ $column ]   = $encoded;
			}

			if ( array() === $update ) {
				continue;
			}
			++$stats['changed'];

			if ( ! $apply ) {
				continue;
			}

			// Direct update on purpose: wp_update_post() would run kses, revisions and hooks on NextGEN's data.
			$written = $wpdb->update( $wpdb->posts, $update, array( 'ID' => $row->ID ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- See above.
			if ( false === $written ) {
				++$stats['failed'];
				WP_CLI::warning( sprintf( 'post ID %d: database update failed: %s', $row->ID, $wpdb->last_error ) );
				continue;
			}
			clean_post_cache( (int) $row->ID );
			foreach ( $olds as $column => $old ) {
				WP_CLI::log( sprintf( 'OLD post ID %d %s: %s', $row->ID, $column, $old ) );
				WP_CLI::log( 'REVERT: ' . $wpdb->prepare( "UPDATE {$wpdb->posts} SET {$column} = %s WHERE ID = %d;", $old, $row->ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $column comes from a fixed list.
			}
		}

		return $stats;
	}
}

if ( ! function_exists( 'hyperpress_ngg_run_table' ) ) {
	/**
	 * Scans and (when applying) updates `display_type_settings` in one NextGEN table.
	 *
	 * @param string   $label Row label for output: `album` or `gallery`.
	 * @param string   $table Table name without the prefix: `ngg_album` or `ngg_gallery`.
	 * @param string   $pk    Primary key column: `id` or `gid`.
	 * @param bool     $apply Whether to write.
	 * @param string[] $names Template file names to map.
	 * @param string   $dir   The theme's `nggallery` directory.
	 * @return array{scanned: int, changed: int, fields: int, skipped: int, failed: int}|null Null when the table does not exist.
	 */
	function hyperpress_ngg_run_table( string $label, string $table, string $pk, bool $apply, array $names, string $dir ): ?array {
		global $wpdb;

		$full = $wpdb->prefix . $table;
		if ( $full !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $full ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- One-off maintenance script; checks NextGEN's table exists.
			WP_CLI::log( sprintf( 'Table %s does not exist; skipping.', $full ) );
			return null;
		}

		$stats = hyperpress_ngg_new_stats();
		$rows  = $wpdb->get_results( "SELECT {$pk}, display_type_settings FROM {$full} ORDER BY {$pk}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- One-off maintenance script; table and column names are fixed or come from $wpdb.

		foreach ( (array) $rows as $row ) {
			++$stats['scanned'];
			$id  = (int) $row->$pk;
			$old = $row->display_type_settings;
			if ( null === $old || '' === trim( (string) $old ) ) {
				continue;
			}

			$result = hyperpress_ngg_remap_type_settings( (string) $old, $names, $dir );
			if ( null === $result ) {
				if ( null === hyperpress_ngg_decode_type_settings( (string) $old ) ) {
					++$stats['skipped'];
					WP_CLI::warning( sprintf( '%s ID %d display_type_settings: skipped, not decodable.', $label, $id ) );
				}
				continue;
			}

			foreach ( $result['changes'] as $change ) {
				WP_CLI::log(
					sprintf(
						'%s ID %d display_type_settings[%s].%s: %s -> %s%s',
						$label,
						$id,
						$change['type'],
						$change['key'],
						$change['old'],
						$change['new'],
						$result['legacy'] ? ' (was legacy serialized)' : ''
					)
				);
				++$stats['fields'];
			}
			++$stats['changed'];

			if ( ! $apply ) {
				continue;
			}

			$written = $wpdb->update( $full, array( 'display_type_settings' => $result['encoded'] ), array( $pk => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off maintenance script; NextGEN has no cache API for this table.
			if ( false === $written ) {
				++$stats['failed'];
				WP_CLI::warning( sprintf( '%s ID %d: database update failed: %s', $label, $id, $wpdb->last_error ) );
				continue;
			}
			WP_CLI::log( sprintf( 'OLD %s ID %d: %s', $label, $id, $old ) );
			WP_CLI::log( 'REVERT: ' . $wpdb->prepare( "UPDATE {$full} SET display_type_settings = %s WHERE {$pk} = %d;", $old, $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table and column names are fixed or come from $wpdb.
		}

		return $stats;
	}
}

if ( ! function_exists( 'hyperpress_ngg_run' ) ) {
	/**
	 * WP-CLI runner.
	 *
	 * @param string[] $args Positional arguments: mode, then template file names and sources.
	 * @return void
	 */
	function hyperpress_ngg_run( array $args ): void {
		$parsed = hyperpress_ngg_parse_args( $args );
		if ( null !== $parsed['error'] ) {
			WP_CLI::log( hyperpress_ngg_usage() );
			WP_CLI::error( $parsed['error'] );
		}
		$apply   = 'apply' === $parsed['mode'];
		$names   = $parsed['names'];
		$targets = $parsed['targets'];
		$dir     = get_template_directory() . '/nggallery';

		$missing = array();
		foreach ( $names as $name ) {
			if ( hyperpress_ngg_basename( $name ) !== $name || ! is_file( $dir . '/' . $name ) ) {
				$missing[] = $name;
			}
		}
		if ( array() !== $missing ) {
			WP_CLI::error( sprintf( 'Not found in %s: %s', $dir, implode( ', ', $missing ) ) );
		}

		WP_CLI::log( sprintf( '%s. Sources: %s. Templates: %s. Theme directory: %s', $apply ? 'APPLY mode' : 'DRY RUN (nothing is written)', implode( ', ', $targets ), implode( ', ', $names ), $dir ) );
		if ( $apply ) {
			WP_CLI::warning( 'Writing to the database. You should have run `wp db export` first. Keep this output: it holds the old values.' );
		}

		$results = array();
		if ( in_array( 'posts', $targets, true ) ) {
			$results['Posts'] = hyperpress_ngg_run_posts( $apply, $names, $dir );
		}
		if ( in_array( 'albums', $targets, true ) ) {
			$results['Albums'] = hyperpress_ngg_run_table( 'album', 'ngg_album', 'id', $apply, $names, $dir );
		}
		if ( in_array( 'galleries', $targets, true ) ) {
			$results['Galleries'] = hyperpress_ngg_run_table( 'gallery', 'ngg_gallery', 'gid', $apply, $names, $dir );
		}

		$failed = 0;
		foreach ( $results as $source => $stats ) {
			if ( null === $stats ) {
				WP_CLI::log( sprintf( '%s: table missing, not scanned.', $source ) );
				continue;
			}
			$failed += $stats['failed'];
			WP_CLI::log(
				sprintf(
					'%s scanned: %d. Changed: %d. Fields changed: %d. Skipped undecodable: %d.%s',
					$source,
					$stats['scanned'],
					$stats['changed'],
					$stats['fields'],
					$stats['skipped'],
					$apply ? sprintf( ' Failed updates: %d.', $stats['failed'] ) : ''
				)
			);
		}

		if ( $apply && $failed > 0 ) {
			WP_CLI::error( 'Some updates failed; see the warnings above.' );
		}
		if ( $apply ) {
			WP_CLI::success( 'Done. Flush page caches now. To revert, run the REVERT statements printed above (wp db query), or import the backup.' );
		} else {
			WP_CLI::success( 'Dry run complete; nothing was written. Re-run with `apply` to write.' );
		}
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	hyperpress_ngg_run( isset( $args ) && is_array( $args ) ? array_values( $args ) : array() );
}
