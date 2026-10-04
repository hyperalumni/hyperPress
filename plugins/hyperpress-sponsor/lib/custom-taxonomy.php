<?php

namespace HYPER_Press_Sponsor;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
if ( ! function_exists( '\HYPER_Press_Sponsor\hyperpress_register_sponsor_tier_taxonomoy' ) ) :
	add_action( 'init', '\HYPER_Press_Sponsor\hyperpress_register_sponsor_tier_taxonomoy' );
	function hyperpress_register_sponsor_tier_taxonomoy(): void {
		$labels = [
			'name'                       => esc_html__( 'Tiers', 'hyperpress' ),
			'singular_name'              => esc_html__( 'Tier', 'hyperpress' ),
			'menu_name'                  => esc_html__( 'Tiers', 'hyperpress' ),
			'search_items'               => esc_html__( 'Search Tiers', 'hyperpress' ),
			'popular_items'              => esc_html__( 'Popular Tiers', 'hyperpress' ),
			'all_items'                  => esc_html__( 'All Tiers', 'hyperpress' ),
			'parent_item'                => esc_html__( 'Parent Tier', 'hyperpress' ),
			'parent_item_colon'          => esc_html__( 'Parent Tier:', 'hyperpress' ),
			'edit_item'                  => esc_html__( 'Edit Tier', 'hyperpress' ),
			'view_item'                  => esc_html__( 'View Tier', 'hyperpress' ),
			'update_item'                => esc_html__( 'Update Tier', 'hyperpress' ),
			'add_new_item'               => esc_html__( 'Add New Tier', 'hyperpress' ),
			'new_item_name'              => esc_html__( 'New Tier Name', 'hyperpress' ),
			'separate_items_with_commas' => esc_html__( 'Separate Tiers with commas', 'hyperpress' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Tiers', 'hyperpress' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Tiers', 'hyperpress' ),
			'not_found'                  => esc_html__( 'No Tiers found.', 'hyperpress' ),
			'no_terms'                   => esc_html__( 'No Tiers', 'hyperpress' ),
			'filter_by_item'             => esc_html__( 'Filter by Tier', 'hyperpress' ),
			'items_list_navigation'      => esc_html__( 'Tiers list pagination', 'hyperpress' ),
			'items_list'                 => esc_html__( 'Tiers list', 'hyperpress' ),
			'most_used'                  => esc_html__( 'Most Used', 'hyperpress' ),
			'back_to_items'              => esc_html__( '&larr; Go to Tiers', 'hyperpress' ),
		];
		$args   = [
			'label'              => esc_html__( 'Tiers', 'hyperpress' ),
			'description'        => esc_html__( 'Levels of Sponsorship', 'hyperpress' ),
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'hierarchical'       => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => false,
			'show_admin_column'  => true,
			'query_var'          => true,
			'sort'               => false,
			'capabilities'       => [
				'manage_terms' => 'manage_options',
				'edit_terms'   => 'manage_options',
				'delete_terms' => 'manage_options',
				'assign_terms' => 'edit_posts',
			],
			'meta_box_cb'        => false,
			'rest_base'          => '',
		];
		register_taxonomy( 'tier', [ 'sponsor' ], $args );
	}
endif;