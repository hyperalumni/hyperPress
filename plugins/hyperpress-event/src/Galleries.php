<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Reads NextGEN Gallery's own tables: the gallery choices, which saved IDs still exist and how many images a gallery has.
 */
final class Galleries {

	/**
	 * Gallery choices, ID => title (the name when the title is empty), ordered by title.
	 *
	 * @return array<int,string>
	 */
	public static function options(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'ngg_gallery';

		$suppress = $wpdb->suppress_errors( true );
		$rows     = $wpdb->get_results( "SELECT gid, title, name FROM {$table} ORDER BY COALESCE( NULLIF( title, '' ), name )", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed table name from $wpdb->prefix; NextGEN has no API for a plain list.
		$wpdb->suppress_errors( $suppress );

		$options = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$title                     = (string) $row['title'];
			$options[ (int) $row['gid'] ] = '' !== $title ? $title : (string) $row['name'];
		}

		return $options;
	}

	/**
	 * The IDs that still exist, in the given order, without duplicates. Anything that is not a positive integer is dropped.
	 *
	 * @param array<mixed> $ids Saved gallery IDs.
	 * @return array<int>
	 */
	public static function existing( array $ids ): array {
		global $wpdb;
		$wanted = array();
		foreach ( $ids as $id ) {
			$id = is_scalar( $id ) ? (int) $id : 0;
			if ( $id > 0 && ! in_array( $id, $wanted, true ) ) {
				$wanted[] = $id;
			}
		}
		if ( array() === $wanted ) {
			return array();
		}

		$table        = $wpdb->prefix . 'ngg_gallery';
		$placeholders = implode( ', ', array_fill( 0, count( $wanted ), '%d' ) );
		$suppress     = $wpdb->suppress_errors( true );
		$found        = $wpdb->get_col( $wpdb->prepare( "SELECT gid FROM {$table} WHERE gid IN ({$placeholders})", $wanted ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from $wpdb->prefix, placeholders built from a count.
		$wpdb->suppress_errors( $suppress );

		$found = array_map( 'intval', is_array( $found ) ? $found : array() );

		return array_values( array_filter( $wanted, static fn( int $id ): bool => in_array( $id, $found, true ) ) );
	}

	/**
	 * Number of images in a gallery that are not excluded.
	 *
	 * @param int $id Gallery ID.
	 */
	public static function image_count( int $id ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'ngg_pictures';

		$suppress = $wpdb->suppress_errors( true );
		$count    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE galleryid = %d AND exclude = 0", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixed table name from $wpdb->prefix.
		$wpdb->suppress_errors( $suppress );

		return (int) $count;
	}
}
