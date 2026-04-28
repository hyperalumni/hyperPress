<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_register_sponsor_post_type' ) ) :
	function hyperpress_register_sponsor_post_type(): void {
		$labels = [
			'name'                     => esc_html__( 'Sponsors', 'hyperpress' ),
			'singular_name'            => esc_html__( 'Sponsor', 'hyperpress' ),
			'add_new'                  => esc_html__( 'Add New', 'hyperpress' ),
			'add_new_item'             => esc_html__( 'Add New Sponsor', 'hyperpress' ),
			'edit_item'                => esc_html__( 'Edit Sponsor', 'hyperpress' ),
			'new_item'                 => esc_html__( 'New Sponsor', 'hyperpress' ),
			'view_item'                => esc_html__( 'View Sponsor', 'hyperpress' ),
			'view_items'               => esc_html__( 'View Sponsors', 'hyperpress' ),
			'search_items'             => esc_html__( 'Search Sponsors', 'hyperpress' ),
			'not_found'                => esc_html__( 'No sponsors found.', 'hyperpress' ),
			'not_found_in_trash'       => esc_html__( 'No sponsors found in Trash.', 'hyperpress' ),
			'parent_item_colon'        => esc_html__( 'Parent Sponsor:', 'hyperpress' ),
			'all_items'                => esc_html__( 'All Sponsors', 'hyperpress' ),
			'archives'                 => esc_html__( 'Sponsor Archives', 'hyperpress' ),
			'attributes'               => esc_html__( 'Sponsor Attributes', 'hyperpress' ),
			'insert_into_item'         => esc_html__( 'Insert into sponsor', 'hyperpress' ),
			'uploaded_to_this_item'    => esc_html__( 'Uploaded to this sponsor', 'hyperpress' ),
			'featured_image'           => esc_html__( 'Featured image', 'hyperpress' ),
			'set_featured_image'       => esc_html__( 'Set featured image', 'hyperpress' ),
			'remove_featured_image'    => esc_html__( 'Remove featured image', 'hyperpress' ),
			'use_featured_image'       => esc_html__( 'Use as featured image', 'hyperpress' ),
			'menu_name'                => esc_html__( 'Sponsors', 'hyperpress' ),
			'filter_items_list'        => esc_html__( 'Filter Sponsors list', 'hyperpress' ),
			'filter_by_date'           => esc_html__( 'Filter Sponsors by date', 'hyperpress' ),
			'items_list_navigation'    => esc_html__( 'Sponsors list navigation', 'hyperpress' ),
			'items_list'               => esc_html__( 'Sponsors list', 'hyperpress' ),
			'item_published'           => esc_html__( 'Sponsor published.', 'hyperpress' ),
			'item_published_privately' => esc_html__( 'Sponsor published privately.', 'hyperpress' ),
			'item_reverted_to_draft'   => esc_html__( 'Sponsor reverted to draft.', 'hyperpress' ),
			'item_scheduled'           => esc_html__( 'Sponsor scheduled.', 'hyperpress' ),
			'item_updated'             => esc_html__( 'Sponsor updated.', 'hyperpress' ),
		];
		$args   = [
			'label'               => esc_html__( 'Sponsors', 'hyperpress' ),
			'description'         => esc_html__( 'Sponsors of Team HYPER', 'hyperpress' ),
			'labels'              => $labels,
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
			'has_archive'         => 'sponsors',
			'rest_base'           => '',
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-businessman',
			'capability_type'     => 'post',
			'supports'            => [ 'title', 'thumbnail' ],
			'taxonomies'          => [],
			'rewrite'             => [
				'with_front' => false,
				'slug' => 'sponsor',
			],
		];
		register_post_type( 'sponsor', $args );
	}

	add_action( 'init', 'hyperpress_register_sponsor_post_type' );
endif;
