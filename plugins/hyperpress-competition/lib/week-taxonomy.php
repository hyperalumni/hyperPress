<?php

namespace HYPER_Press_Competition;
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
if ( ! function_exists( '\HYPER_Press_Competition\hyperpress_register_week_taxonomoy' ) ) :
	add_action( 'init', '\HYPER_Press_Competition\hyperpress_register_week_taxonomoy' );

	function hyperpress_register_week_taxonomoy(): void {
		$labels = [
			'name'                       => esc_html__( 'Weeks', 'hyperpress' ),
			'singular_name'              => esc_html__( 'Week', 'hyperpress' ),
			'menu_name'                  => esc_html__( 'Weeks', 'hyperpress' ),
			'search_items'               => esc_html__( 'Search Weeks', 'hyperpress' ),
			'popular_items'              => esc_html__( 'Popular Weeks', 'hyperpress' ),
			'all_items'                  => esc_html__( 'All Weeks', 'hyperpress' ),
			'parent_item'                => esc_html__( 'Parent Week', 'hyperpress' ),
			'parent_item_colon'          => esc_html__( 'Parent Week:', 'hyperpress' ),
			'edit_item'                  => esc_html__( 'Edit Week', 'hyperpress' ),
			'view_item'                  => esc_html__( 'View Week', 'hyperpress' ),
			'update_item'                => esc_html__( 'Update Week', 'hyperpress' ),
			'add_new_item'               => esc_html__( 'Add New Week', 'hyperpress' ),
			'new_item_name'              => esc_html__( 'New Week Name', 'hyperpress' ),
			'separate_items_with_commas' => esc_html__( 'Separate Weeks with commas', 'hyperpress' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Weeks', 'hyperpress' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Weeks', 'hyperpress' ),
			'not_found'                  => esc_html__( 'No Weeks found.', 'hyperpress' ),
			'no_terms'                   => esc_html__( 'No Weeks', 'hyperpress' ),
			'filter_by_item'             => esc_html__( 'Filter by Week', 'hyperpress' ),
			'items_list_navigation'      => esc_html__( 'Weeks list pagination', 'hyperpress' ),
			'items_list'                 => esc_html__( 'Weeks list', 'hyperpress' ),
			'most_used'                  => esc_html__( 'Most Used', 'hyperpress' ),
			'back_to_items'              => esc_html__( '&larr; Go to Weeks', 'hyperpress' ),
		];
		$args   = [
			'label'              => esc_html__( 'Weeks', 'hyperpress' ),
			'description'        => esc_html__( 'FRC Competition Weeks', 'hyperpress' ),
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
			'query_var'          => true,
			'sort'               => false,
			'order'              => false,
			'capabilities'       => [
				'manage_terms' => 'manage_options',
				'edit_terms'   => 'manage_options',
				'delete_terms' => 'manage_options',
				'assign_terms' => 'edit_posts',
			],
			'rest_base'          => '',
			'rewrite'            => [
				'with_front'   => false,
				'hierarchical' => true,
				'slug'         => 'weeks',
			],
		];
		register_taxonomy( 'week', [ 'competition' ], $args );
	}
endif;