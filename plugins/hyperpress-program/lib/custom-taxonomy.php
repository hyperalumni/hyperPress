<?php

namespace HYPER_Press_Program;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Program\hyperpress_register_program_taxonomy' ) ) :
	add_action( 'init', '\HYPER_Press_Program\hyperpress_register_program_taxonomy' );
	function hyperpress_register_program_taxonomy(): void {
		$labels = [
			'name'                       => esc_html__( 'Programs', 'hyperpress' ),
			'singular_name'              => esc_html__( 'Program', 'hyperpress' ),
			'menu_name'                  => esc_html__( 'Programs', 'hyperpress' ),
			'search_items'               => esc_html__( 'Search Programs', 'hyperpress' ),
			'popular_items'              => esc_html__( 'Popular Programs', 'hyperpress' ),
			'all_items'                  => esc_html__( 'All Programs', 'hyperpress' ),
			'parent_item'                => esc_html__( 'Parent Program', 'hyperpress' ),
			'parent_item_colon'          => esc_html__( 'Parent Program:', 'hyperpress' ),
			'edit_item'                  => esc_html__( 'Edit Program', 'hyperpress' ),
			'view_item'                  => esc_html__( 'View Program', 'hyperpress' ),
			'update_item'                => esc_html__( 'Update Program', 'hyperpress' ),
			'add_new_item'               => esc_html__( 'Add New Program', 'hyperpress' ),
			'new_item_name'              => esc_html__( 'New Program Name', 'hyperpress' ),
			'separate_items_with_commas' => esc_html__( 'Separate Programs with commas', 'hyperpress' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Programs', 'hyperpress' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Programs', 'hyperpress' ),
			'not_found'                  => esc_html__( 'No Programs found.', 'hyperpress' ),
			'no_terms'                   => esc_html__( 'No Programs', 'hyperpress' ),
			'filter_by_item'             => esc_html__( 'Filter by Program', 'hyperpress' ),
			'items_list_navigation'      => esc_html__( 'Programs list pagination', 'hyperpress' ),
			'items_list'                 => esc_html__( 'Programs list', 'hyperpress' ),
			'most_used'                  => esc_html__( 'Most Used', 'hyperpress' ),
			'back_to_items'              => esc_html__( '&larr; Go to Programs', 'hyperpress' ),
		];
		$args   = [
			'label'              => esc_html__( 'Programs', 'hyperpress' ),
			'description'        => esc_html__( 'Programs within FIRST', 'hyperpress' ),
			'labels'             => $labels,
			'public'             => true,
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
				'slug'         => 'programs'
			],
		];
		register_taxonomy( 'program', get_theme_mod( 'hyperpress_program_post_types', [] ), $args );
	}

endif;