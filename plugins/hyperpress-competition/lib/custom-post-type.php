<?php

namespace HYPER_Press_Competition;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Competition\hyperpress_register_competition_post_type' ) ) :
	add_action( 'init', '\HYPER_Press_Competition\hyperpress_register_competition_post_type' );

	function hyperpress_register_competition_post_type(): void {
		$labels = [
			'name'                     => esc_html__( 'Competitions', 'hyperpress' ),
			'singular_name'            => esc_html__( 'Competition', 'hyperpress' ),
			'add_new'                  => esc_html__( 'Add New', 'hyperpress' ),
			'add_new_item'             => esc_html__( 'Add New Competition', 'hyperpress' ),
			'edit_item'                => esc_html__( 'Edit Competition', 'hyperpress' ),
			'new_item'                 => esc_html__( 'New Competition', 'hyperpress' ),
			'view_item'                => esc_html__( 'View Competition', 'hyperpress' ),
			'view_items'               => esc_html__( 'View Competitions', 'hyperpress' ),
			'search_items'             => esc_html__( 'Search Competitions', 'hyperpress' ),
			'not_found'                => esc_html__( 'No Competitions found.', 'hyperpress' ),
			'not_found_in_trash'       => esc_html__( 'No Competitions found in Trash.', 'hyperpress' ),
			'parent_item_colon'        => esc_html__( 'Parent Competition:', 'hyperpress' ),
			'all_items'                => esc_html__( 'All Competitions', 'hyperpress' ),
			'archives'                 => esc_html__( 'Competition Archives', 'hyperpress' ),
			'attributes'               => esc_html__( 'Competition Attributes', 'hyperpress' ),
			'insert_into_item'         => esc_html__( 'Insert into Competition', 'hyperpress' ),
			'uploaded_to_this_item'    => esc_html__( 'Uploaded to this Competition', 'hyperpress' ),
			'featured_image'           => esc_html__( 'Featured image', 'hyperpress' ),
			'set_featured_image'       => esc_html__( 'Set featured image', 'hyperpress' ),
			'remove_featured_image'    => esc_html__( 'Remove featured image', 'hyperpress' ),
			'use_featured_image'       => esc_html__( 'Use as featured image', 'hyperpress' ),
			'menu_name'                => esc_html__( 'Competitions', 'hyperpress' ),
			'filter_items_list'        => esc_html__( 'Filter Competitions list', 'hyperpress' ),
			'filter_by_date'           => esc_html__( 'Filter Competitions by date', 'hyperpress' ),
			'items_list_navigation'    => esc_html__( 'Competitions list navigation', 'hyperpress' ),
			'items_list'               => esc_html__( 'Competitions list', 'hyperpress' ),
			'item_published'           => esc_html__( 'Competition published.', 'hyperpress' ),
			'item_published_privately' => esc_html__( 'Competition published privately.', 'hyperpress' ),
			'item_reverted_to_draft'   => esc_html__( 'Competition reverted to draft.', 'hyperpress' ),
			'item_scheduled'           => esc_html__( 'Competition scheduled.', 'hyperpress' ),
			'item_updated'             => esc_html__( 'Competition updated.', 'hyperpress' ),
		];
		$args   = [
			'label'               => esc_html__( 'Competitions', 'hyperpress' ),
			'description'         => esc_html__( 'Competitions competed at by Team HYPER', 'hyperpress' ),
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
			'has_archive'         => 'competitions',
			'rest_base'           => '',
			'show_in_menu'        => true,
			'menu_position'       => 22,
			'menu_icon'           => 'dashicons-calendar',
			'capability_type'     => 'post',
			'supports'            => [ 'title', 'thumbnail', 'revisions' ],
			'taxonomies'          => [],
			'rewrite'             => [
				'with_front'   => false,
				'hierarchical' => true,
				'slug'         => 'competition',
			],
		];

		register_post_type( 'competition', $args );
	}
endif;