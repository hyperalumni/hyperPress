<?php

namespace HYPER_Press_Countdown;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Countdown\hyperpress_register_countdown_post_type' ) ) :
	add_action( 'init', '\HYPER_Press_Countdown\hyperpress_register_countdown_post_type' );

	function hyperpress_register_countdown_post_type(): void {
		$labels = [
			'name'                     => esc_html__( 'Countdowns', 'hyperpress' ),
			'singular_name'            => esc_html__( 'Countdown', 'hyperpress' ),
			'add_new'                  => esc_html__( 'Add New', 'hyperpress' ),
			'add_new_item'             => esc_html__( 'Add New Countdown', 'hyperpress' ),
			'edit_item'                => esc_html__( 'Edit Countdown', 'hyperpress' ),
			'new_item'                 => esc_html__( 'New Countdown', 'hyperpress' ),
			'view_item'                => esc_html__( 'View Countdown', 'hyperpress' ),
			'view_items'               => esc_html__( 'View Countdowns', 'hyperpress' ),
			'search_items'             => esc_html__( 'Search Countdowns', 'hyperpress' ),
			'not_found'                => esc_html__( 'No Countdowns found.', 'hyperpress' ),
			'not_found_in_trash'       => esc_html__( 'No Countdowns found in Trash.', 'hyperpress' ),
			'parent_item_colon'        => esc_html__( 'Parent Countdown:', 'hyperpress' ),
			'all_items'                => esc_html__( 'All Countdowns', 'hyperpress' ),
			'archives'                 => esc_html__( 'Countdown Archives', 'hyperpress' ),
			'attributes'               => esc_html__( 'Countdown Attributes', 'hyperpress' ),
			'insert_into_item'         => esc_html__( 'Insert into Countdown', 'hyperpress' ),
			'uploaded_to_this_item'    => esc_html__( 'Uploaded to this Countdown', 'hyperpress' ),
			'featured_image'           => esc_html__( 'Featured image', 'hyperpress' ),
			'set_featured_image'       => esc_html__( 'Set featured image', 'hyperpress' ),
			'remove_featured_image'    => esc_html__( 'Remove featured image', 'hyperpress' ),
			'use_featured_image'       => esc_html__( 'Use as featured image', 'hyperpress' ),
			'menu_name'                => esc_html__( 'Countdowns', 'hyperpress' ),
			'filter_items_list'        => esc_html__( 'Filter Countdowns list', 'hyperpress' ),
			'filter_by_date'           => esc_html__( 'Filter Countdowns by date', 'hyperpress' ),
			'items_list_navigation'    => esc_html__( 'Countdowns list navigation', 'hyperpress' ),
			'items_list'               => esc_html__( 'Countdowns list', 'hyperpress' ),
			'item_published'           => esc_html__( 'Countdown published.', 'hyperpress' ),
			'item_published_privately' => esc_html__( 'Countdown published privately.', 'hyperpress' ),
			'item_reverted_to_draft'   => esc_html__( 'Countdown reverted to draft.', 'hyperpress' ),
			'item_scheduled'           => esc_html__( 'Countdown scheduled.', 'hyperpress' ),
			'item_updated'             => esc_html__( 'Countdown updated.', 'hyperpress' ),
		];
		$args   = [
			'label'               => esc_html__( 'Count Downs', 'hyperpress' ),
			'description'         => esc_html__( 'Time Circle Count Downs for Display in the UI', 'hyperpress' ),
			'labels'              => $labels,
			'public'              => false,
			'hierarchical'        => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => false,
			'query_var'           => false,
			'can_export'          => true,
			'delete_with_user'    => false,
			'_slug_changed'       => true,
			'order'               => true,
			'status_column'       => true,
			'has_archive'         => false,
			'rest_base'           => '',
			'show_in_menu'        => true,
			'menu_position'       => 11,
			'menu_icon'           => 'dashicons-clock',
			'capability_type'     => 'page',
			'supports'            => [ 'title', 'slug' ],
			'taxonomies'          => [],
			'rewrite'             => [ 'with_front' => false ]

		];

		register_post_type( 'countdown', $args );
	}
endif;

