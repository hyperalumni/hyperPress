<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Labels {

	/** @return array<string,string> */
	public static function post_type( string $singular, string $plural ): array {
		$s  = $singular;
		$p  = $plural;
		$ls = strtolower( $singular );
		$lp = strtolower( $plural );

		return array(
			'name'                  => $p,
			'singular_name'         => $s,
			'menu_name'             => $p,
			'name_admin_bar'        => $s,
			'add_new'               => __( 'Add New', 'hyperpress' ),
			/* translators: %s: singular post type name. */
			'add_new_item'          => sprintf( __( 'Add New %s', 'hyperpress' ), $s ),
			/* translators: %s: singular post type name. */
			'edit_item'             => sprintf( __( 'Edit %s', 'hyperpress' ), $s ),
			/* translators: %s: singular post type name. */
			'new_item'              => sprintf( __( 'New %s', 'hyperpress' ), $s ),
			/* translators: %s: singular post type name. */
			'view_item'             => sprintf( __( 'View %s', 'hyperpress' ), $s ),
			/* translators: %s: plural post type name. */
			'view_items'            => sprintf( __( 'View %s', 'hyperpress' ), $p ),
			/* translators: %s: plural post type name. */
			'all_items'             => sprintf( __( 'All %s', 'hyperpress' ), $p ),
			/* translators: %s: plural post type name. */
			'search_items'          => sprintf( __( 'Search %s', 'hyperpress' ), $p ),
			/* translators: %s: singular post type name. */
			'parent_item_colon'     => sprintf( __( 'Parent %s:', 'hyperpress' ), $s ),
			/* translators: %s: lowercase plural post type name. */
			'not_found'             => sprintf( __( 'No %s found', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase plural post type name. */
			'not_found_in_trash'    => sprintf( __( 'No %s found in Trash', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase singular post type name. */
			'featured_image'        => sprintf( __( 'Featured image for this %s', 'hyperpress' ), $ls ),
			/* translators: %s: lowercase singular post type name. */
			'set_featured_image'    => sprintf( __( 'Set featured image for this %s', 'hyperpress' ), $ls ),
			/* translators: %s: lowercase singular post type name. */
			'remove_featured_image' => sprintf( __( 'Remove featured image for this %s', 'hyperpress' ), $ls ),
			/* translators: %s: lowercase singular post type name. */
			'use_featured_image'    => sprintf( __( 'Use as featured image for this %s', 'hyperpress' ), $ls ),
			/* translators: %s: singular post type name. */
			'archives'              => sprintf( __( '%s archives', 'hyperpress' ), $s ),
			/* translators: %s: lowercase singular post type name. */
			'insert_into_item'      => sprintf( __( 'Insert into %s', 'hyperpress' ), $ls ),
			/* translators: %s: lowercase singular post type name. */
			'uploaded_to_this_item' => sprintf( __( 'Uploaded to this %s', 'hyperpress' ), $ls ),
			/* translators: %s: lowercase plural post type name. */
			'filter_items_list'     => sprintf( __( 'Filter %s list', 'hyperpress' ), $lp ),
			/* translators: %s: plural post type name. */
			'items_list_navigation' => sprintf( __( '%s list navigation', 'hyperpress' ), $p ),
			/* translators: %s: plural post type name. */
			'items_list'            => sprintf( __( '%s list', 'hyperpress' ), $p ),
			/* translators: %s: singular post type name. */
			'attributes'            => sprintf( __( '%s attributes', 'hyperpress' ), $s ),
		);
	}

	/** @return array<string,string> */
	public static function taxonomy( string $singular, string $plural ): array {
		$s  = $singular;
		$p  = $plural;
		$lp = strtolower( $plural );

		return array(
			'name'                       => $p,
			'singular_name'              => $s,
			'menu_name'                  => $p,
			/* translators: %s: plural taxonomy name. */
			'all_items'                  => sprintf( __( 'All %s', 'hyperpress' ), $p ),
			/* translators: %s: singular taxonomy name. */
			'edit_item'                  => sprintf( __( 'Edit %s', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'view_item'                  => sprintf( __( 'View %s', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'update_item'                => sprintf( __( 'Update %s', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'add_new_item'               => sprintf( __( 'Add New %s', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'new_item_name'              => sprintf( __( 'New %s Name', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'parent_item'                => sprintf( __( 'Parent %s', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'parent_item_colon'          => sprintf( __( 'Parent %s:', 'hyperpress' ), $s ),
			/* translators: %s: plural taxonomy name. */
			'search_items'               => sprintf( __( 'Search %s', 'hyperpress' ), $p ),
			/* translators: %s: plural taxonomy name. */
			'popular_items'              => sprintf( __( 'Popular %s', 'hyperpress' ), $p ),
			/* translators: %s: lowercase plural taxonomy name. */
			'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase plural taxonomy name. */
			'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase plural taxonomy name. */
			'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase plural taxonomy name. */
			'not_found'                  => sprintf( __( 'No %s found', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase plural taxonomy name. */
			'no_terms'                   => sprintf( __( 'No %s', 'hyperpress' ), $lp ),
			/* translators: %s: plural taxonomy name. */
			'items_list_navigation'      => sprintf( __( '%s list navigation', 'hyperpress' ), $p ),
			/* translators: %s: plural taxonomy name. */
			'items_list'                 => sprintf( __( '%s list', 'hyperpress' ), $p ),
			/* translators: %s: plural taxonomy name. */
			'back_to_items'              => sprintf( __( '&larr; Back to %s', 'hyperpress' ), $p ),
		);
	}
}
