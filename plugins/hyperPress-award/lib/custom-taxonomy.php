<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_register_award_taxonomoy' ) ) :
	function hyperpress_register_award_taxonomoy(): void {
		$args = [
			'label'              => esc_html__( 'Seasons', 'hyperpress' ),
			'labels'             => [
				'name'                       => esc_html__( 'Seasons', 'hyperpress' ),
				'singular_name'              => esc_html__( 'Season', 'hyperpress' ),
				'menu_name'                  => esc_html__( 'Seasons', 'hyperpress' ),
				'search_items'               => esc_html__( 'Search Seasons', 'hyperpress' ),
				'popular_items'              => esc_html__( 'Popular Seasons', 'hyperpress' ),
				'all_items'                  => esc_html__( 'All Seasons', 'hyperpress' ),
				'parent_item'                => esc_html__( 'Parent Season', 'hyperpress' ),
				'parent_item_colon'          => esc_html__( 'Parent Season:', 'hyperpress' ),
				'edit_item'                  => esc_html__( 'Edit Season', 'hyperpress' ),
				'view_item'                  => esc_html__( 'View Season', 'hyperpress' ),
				'update_item'                => esc_html__( 'Update Season', 'hyperpress' ),
				'add_new_item'               => esc_html__( 'Add New Season', 'hyperpress' ),
				'new_item_name'              => esc_html__( 'New Season Name', 'hyperpress' ),
				'separate_items_with_commas' => esc_html__( 'Separate seasons with commas', 'hyperpress' ),
				'add_or_remove_items'        => esc_html__( 'Add or remove seasons', 'hyperpress' ),
				'choose_from_most_used'      => esc_html__( 'Choose most used seasons', 'hyperpress' ),
				'not_found'                  => esc_html__( 'No seasons found.', 'hyperpress' ),
				'no_terms'                   => esc_html__( 'No seasons', 'hyperpress' ),
				'filter_by_item'             => esc_html__( 'Filter by season', 'hyperpress' ),
				'items_list_navigation'      => esc_html__( 'Seasons list pagination', 'hyperpress' ),
				'items_list'                 => esc_html__( 'Seasons list', 'hyperpress' ),
				'most_used'                  => esc_html__( 'Most Used', 'hyperpress' ),
				'back_to_items'              => esc_html__( '&larr; Go to Seasons', 'hyperpress' ),
			],
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
			'query_var'          => true,
			'sort'               => false,
			'order'              => true,
			'capabilities'       => [
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			],
			'rest_base'          => '',
			'rewrite'            => [
				'with_front'   => false,
				'hierarchical' => false,
				'slug'         => 'awards'
			],
		];
		register_taxonomy( 'award_season', 'award', $args );
	}

	add_action( 'init', 'hyperpress_register_award_taxonomoy' );
endif;