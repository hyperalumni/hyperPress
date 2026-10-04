<?php

namespace HYPER_Press_Award;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Award\hyperpress_register_award_post_type' ) ) :
	add_action( 'init', '\HYPER_Press_Award\hyperpress_register_award_post_type' );

	function hyperpress_register_award_post_type(): void {
		$labels = [
			'name'                     => esc_html__( 'Awards', 'hyperpress' ),
			'singular_name'            => esc_html__( 'Award', 'hyperpress' ),
			'add_new'                  => esc_html__( 'Add New', 'hyperpress' ),
			'add_new_item'             => esc_html__( 'Add New Award', 'hyperpress' ),
			'edit_item'                => esc_html__( 'Edit Award', 'hyperpress' ),
			'new_item'                 => esc_html__( 'New Award', 'hyperpress' ),
			'view_item'                => esc_html__( 'View Award', 'hyperpress' ),
			'view_items'               => esc_html__( 'View Awards', 'hyperpress' ),
			'search_items'             => esc_html__( 'Search Awards', 'hyperpress' ),
			'not_found'                => esc_html__( 'No Awards found.', 'hyperpress' ),
			'not_found_in_trash'       => esc_html__( 'No Awards found in Trash.', 'hyperpress' ),
			'parent_item_colon'        => esc_html__( 'Parent Award:', 'hyperpress' ),
			'all_items'                => esc_html__( 'All Awards', 'hyperpress' ),
			'archives'                 => esc_html__( 'Award Archives', 'hyperpress' ),
			'attributes'               => esc_html__( 'Award Attributes', 'hyperpress' ),
			'insert_into_item'         => esc_html__( 'Insert into Award', 'hyperpress' ),
			'uploaded_to_this_item'    => esc_html__( 'Uploaded to this Award', 'hyperpress' ),
			'featured_image'           => esc_html__( 'Featured image', 'hyperpress' ),
			'set_featured_image'       => esc_html__( 'Set featured image', 'hyperpress' ),
			'remove_featured_image'    => esc_html__( 'Remove featured image', 'hyperpress' ),
			'use_featured_image'       => esc_html__( 'Use as featured image', 'hyperpress' ),
			'menu_name'                => esc_html__( 'Awards', 'hyperpress' ),
			'filter_items_list'        => esc_html__( 'Filter Awards list', 'hyperpress' ),
			'filter_by_date'           => esc_html__( 'Filter Awards by date', 'hyperpress' ),
			'items_list_navigation'    => esc_html__( 'Awards list navigation', 'hyperpress' ),
			'items_list'               => esc_html__( 'Awards list', 'hyperpress' ),
			'item_published'           => esc_html__( 'Award published.', 'hyperpress' ),
			'item_published_privately' => esc_html__( 'Award published privately.', 'hyperpress' ),
			'item_reverted_to_draft'   => esc_html__( 'Award reverted to draft.', 'hyperpress' ),
			'item_scheduled'           => esc_html__( 'Award scheduled.', 'hyperpress' ),
			'item_updated'             => esc_html__( 'Award updated.', 'hyperpress' ),
		];
		$args   = [
			'label'               => esc_html__( 'Awards', 'hyperpress' ),
			'description'         => esc_html__( 'Awards won by Team HYPER', 'hyperpress' ),
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
			'has_archive'         => 'awards',
			'rest_base'           => '',
			'show_in_menu'        => true,
			'menu_position'       => 24,
			'menu_icon'           => 'dashicons-awards',
			'capability_type'     => 'post',
			'supports'            => [ 'title', 'editor', 'thumbnail', 'revisions' ],
			'taxonomies'          => [],
			'rewrite'             => [
				'with_front' => false,
				'slug'       => 'award',
			],
		];

		register_post_type( 'award', $args );
	}
endif;