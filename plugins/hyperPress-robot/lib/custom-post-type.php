<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_register_robot_post_type' ) ) :
	function hyperpress_register_robot_post_type(): void {
		$labels = [
			'name'                     => esc_html__( 'Robots', 'hyperpress' ),
			'singular_name'            => esc_html__( 'Robot', 'hyperpress' ),
			'add_new'                  => esc_html__( 'Add New', 'hyperpress' ),
			'add_new_item'             => esc_html__( 'Add New Robot', 'hyperpress' ),
			'edit_item'                => esc_html__( 'Edit Robot', 'hyperpress' ),
			'new_item'                 => esc_html__( 'New Robot', 'hyperpress' ),
			'view_item'                => esc_html__( 'View Robot', 'hyperpress' ),
			'view_items'               => esc_html__( 'View Robots', 'hyperpress' ),
			'search_items'             => esc_html__( 'Search Robots', 'hyperpress' ),
			'not_found'                => esc_html__( 'No robots found.', 'hyperpress' ),
			'not_found_in_trash'       => esc_html__( 'No robots found in Trash.', 'hyperpress' ),
			'parent_item_colon'        => esc_html__( 'Parent Robot:', 'hyperpress' ),
			'all_items'                => esc_html__( 'All Robots', 'hyperpress' ),
			'archives'                 => esc_html__( 'Robot Archives', 'hyperpress' ),
			'attributes'               => esc_html__( 'Robot Attributes', 'hyperpress' ),
			'insert_into_item'         => esc_html__( 'Insert into robot', 'hyperpress' ),
			'uploaded_to_this_item'    => esc_html__( 'Uploaded to this robot', 'hyperpress' ),
			'featured_image'           => esc_html__( 'Featured image', 'hyperpress' ),
			'set_featured_image'       => esc_html__( 'Set featured image', 'hyperpress' ),
			'remove_featured_image'    => esc_html__( 'Remove featured image', 'hyperpress' ),
			'use_featured_image'       => esc_html__( 'Use as featured image', 'hyperpress' ),
			'menu_name'                => esc_html__( 'Robots', 'hyperpress' ),
			'filter_items_list'        => esc_html__( 'Filter Robots list', 'hyperpress' ),
			'filter_by_date'           => esc_html__( 'Filter Robots by date', 'hyperpress' ),
			'items_list_navigation'    => esc_html__( 'Robots list navigation', 'hyperpress' ),
			'items_list'               => esc_html__( 'Robots list', 'hyperpress' ),
			'item_published'           => esc_html__( 'Robot published.', 'hyperpress' ),
			'item_published_privately' => esc_html__( 'Robot published privately.', 'hyperpress' ),
			'item_reverted_to_draft'   => esc_html__( 'Robot reverted to draft.', 'hyperpress' ),
			'item_scheduled'           => esc_html__( 'Robot scheduled.', 'hyperpress' ),
			'item_updated'             => esc_html__( 'Robot updated.', 'hyperpress' ),
		];
		$args   = [
			'label'               => esc_html__( 'Robots', 'hyperpress' ),
			'labels'              => $labels,
			'description'         => 'Robots made by Team HYPER',
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
			'has_archive'         => 'robots',
			'rest_base'           => '',
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-superhero',
			'capability_type'     => 'post',
			'supports'            => [ 'title', 'editor', 'thumbnail' ],
			'taxonomies'          => [],
			'rewrite'             => [
				'with_front' => false,
				'slug'       => 'robot',
			],
		];

		register_post_type( 'robot', $args );
	}

	add_action( 'init', 'hyperpress_register_robot_post_type' );
endif;