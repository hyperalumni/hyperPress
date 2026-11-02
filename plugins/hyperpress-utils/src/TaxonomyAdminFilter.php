<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * "All …" filter dropdowns for a taxonomy in the wp-admin post list tables.
 *
 * A dropdown is printed only on post types attached to the taxonomy and only when the taxonomy has terms. Core does
 * the filtering: the select is named after the taxonomy's query var, which WordPress turns into a tax query.
 */
final class TaxonomyAdminFilter {

	/**
	 * Taxonomy => callable returning the "All …" label. The label is a callable so it is translated when the page
	 * renders, not while plugins boot (WordPress 6.7+ flags a text domain loaded that early).
	 *
	 * @var array<string,callable>
	 */
	private static array $labels = array();

	/**
	 * Add a filter dropdown for a taxonomy. Calling it again for the same taxonomy replaces the label.
	 *
	 * @param string   $taxonomy  Taxonomy key.
	 * @param callable $all_label Returns the translated label of the "all" option, e.g. "All programs".
	 */
	public static function register( string $taxonomy, callable $all_label ): void {
		self::$labels[ $taxonomy ] = $all_label;

		if ( false === has_action( 'restrict_manage_posts', array( self::class, 'on_restrict_manage_posts' ) ) ) {
			add_action( 'restrict_manage_posts', array( self::class, 'on_restrict_manage_posts' ) );
		}
	}

	/**
	 * `restrict_manage_posts` handler: print the dropdown of every registered taxonomy for this post type.
	 *
	 * @param mixed $post_type Post type of the list table.
	 */
	public static function on_restrict_manage_posts( $post_type = '' ): void {
		if ( ! is_string( $post_type ) ) {
			return;
		}
		foreach ( self::$labels as $taxonomy => $all_label ) {
			self::render( (string) $taxonomy, (string) $all_label(), $post_type );
		}
	}

	/**
	 * Print one dropdown, or nothing when the taxonomy is not usable on this post type.
	 *
	 * @param string $taxonomy  Taxonomy key.
	 * @param string $all_label Label of the "all" option.
	 * @param string $post_type Post type of the list table.
	 */
	public static function render( string $taxonomy, string $all_label, string $post_type ): void {
		$object = get_taxonomy( $taxonomy );
		if ( ! $object || ! is_string( $object->query_var ) || '' === $object->query_var || ! is_object_in_taxonomy( $post_type, $taxonomy ) ) {
			return;
		}
		$count = wp_count_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $count ) || (int) $count < 1 ) {
			return;
		}
		$selected = '';
		if ( isset( $_GET[ $object->query_var ] ) && is_string( $_GET[ $object->query_var ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected = sanitize_title( wp_unslash( $_GET[ $object->query_var ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		wp_dropdown_categories(
			array(
				'taxonomy'        => $taxonomy,
				'name'            => $object->query_var,
				'show_option_all' => $all_label,
				'hierarchical'    => true,
				'hide_empty'      => false,
				'orderby'         => 'name',
				'value_field'     => 'slug',
				'selected'        => $selected,
			)
		);
	}
}
