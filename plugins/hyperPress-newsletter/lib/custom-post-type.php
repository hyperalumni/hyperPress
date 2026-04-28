<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_register_newsletter_post_type' ) ) :
	function hyperpress_register_newsletter_post_type(): void {
		$labels = [
			'name'                     => esc_html__( 'Newsletters', 'hyperpress' ),
			'singular_name'            => esc_html__( 'Newsletter', 'hyperpress' ),
			'add_new'                  => esc_html__( 'Add New', 'hyperpress' ),
			'add_new_item'             => esc_html__( 'Add New Newsletter', 'hyperpress' ),
			'edit_item'                => esc_html__( 'Edit Newsletter', 'hyperpress' ),
			'new_item'                 => esc_html__( 'New Newsletter', 'hyperpress' ),
			'view_item'                => esc_html__( 'View Newsletter', 'hyperpress' ),
			'view_items'               => esc_html__( 'View Newsletters', 'hyperpress' ),
			'search_items'             => esc_html__( 'Search Newsletters', 'hyperpress' ),
			'not_found'                => esc_html__( 'No Newsletters found.', 'hyperpress' ),
			'not_found_in_trash'       => esc_html__( 'No Newsletters found in Trash.', 'hyperpress' ),
			'parent_item_colon'        => esc_html__( 'Parent Newsletter:', 'hyperpress' ),
			'all_items'                => esc_html__( 'All Newsletters', 'hyperpress' ),
			'archives'                 => esc_html__( 'Newsletter Archives', 'hyperpress' ),
			'attributes'               => esc_html__( 'Newsletter Attributes', 'hyperpress' ),
			'insert_into_item'         => esc_html__( 'Insert into Newsletter', 'hyperpress' ),
			'uploaded_to_this_item'    => esc_html__( 'Uploaded to this Newsletter', 'hyperpress' ),
			'featured_image'           => esc_html__( 'Featured image', 'hyperpress' ),
			'set_featured_image'       => esc_html__( 'Set featured image', 'hyperpress' ),
			'remove_featured_image'    => esc_html__( 'Remove featured image', 'hyperpress' ),
			'use_featured_image'       => esc_html__( 'Use as featured image', 'hyperpress' ),
			'menu_name'                => esc_html__( 'Newsletters', 'hyperpress' ),
			'filter_items_list'        => esc_html__( 'Filter Newsletters list', 'hyperpress' ),
			'filter_by_date'           => esc_html__( 'Filter Newsletters by date', 'hyperpress' ),
			'items_list_navigation'    => esc_html__( 'Newsletters list navigation', 'hyperpress' ),
			'items_list'               => esc_html__( 'Newsletters list', 'hyperpress' ),
			'item_published'           => esc_html__( 'Newsletter published.', 'hyperpress' ),
			'item_published_privately' => esc_html__( 'Newsletter published privately.', 'hyperpress' ),
			'item_reverted_to_draft'   => esc_html__( 'Newsletter reverted to draft.', 'hyperpress' ),
			'item_scheduled'           => esc_html__( 'Newsletter scheduled.', 'hyperpress' ),
			'item_updated'             => esc_html__( 'Newsletter updated.', 'hyperpress' ),
		];
		$args   = [
			'label'               => esc_html__( 'Newsletters', 'hyperpress' ),
			'labels'              => $labels,
			'description'         => 'Newsletters from Team HYPER Students',
			'public'              => true,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => false,
			'_slug_changed'       => true,
			'order'               => false,
			'status_column'       => true,
			'has_archive'         => 'newsletters',
			'rest_base'           => '',
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-media-text',
			'capability_type'     => 'post',
			'supports'            => [ 'title', 'author', 'editor' ],
			'taxonomies'          => [],
			'rewrite'             => [ 'with_front' => false, 'slug' => 'newsletter' ],
		];

		register_post_type( 'newsletter', $args );
	}

	add_action( 'init', 'hyperpress_register_newsletter_post_type' );
endif;