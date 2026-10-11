<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

final class AdminFilter {

	public static function register(): void {
		add_action( 'restrict_manage_posts', array( self::class, 'render' ) );
	}

	public static function render( $post_type = '' ): void {
		$taxonomy = get_taxonomy( Plugin::TAXONOMY );
		if ( ! $taxonomy || ! $taxonomy->query_var || ! is_string( $post_type ) || ! is_object_in_taxonomy( $post_type, Plugin::TAXONOMY ) ) {
			return;
		}
		$count = wp_count_terms(
			array(
				'taxonomy'   => Plugin::TAXONOMY,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $count ) || (int) $count < 1 ) {
			return;
		}
		$selected = isset( $_GET[ $taxonomy->query_var ] ) ? sanitize_title( wp_unslash( $_GET[ $taxonomy->query_var ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		wp_dropdown_categories(
			array(
				'taxonomy'        => Plugin::TAXONOMY,
				'name'            => $taxonomy->query_var,
				'show_option_all' => __( 'All seasons', 'hyperpress' ),
				'hierarchical'    => true,
				'hide_empty'      => false,
				'value_field'     => 'slug',
				'selected'        => $selected,
			)
		);
	}
}
