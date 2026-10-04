<?php

namespace HYPER_Press_Competition;
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Competition\hyperpress_register_district_taxonomoy' ) ) :
	add_action( 'init', '\HYPER_Press_Competition\hyperpress_register_district_taxonomoy' );

	function hyperpress_register_district_taxonomoy(): void {
		$labels = [
			'name'                       => esc_html__( 'Districts', 'hyperpress' ),
			'singular_name'              => esc_html__( 'District', 'hyperpress' ),
			'menu_name'                  => esc_html__( 'Districts', 'hyperpress' ),
			'search_items'               => esc_html__( 'Search Districts', 'hyperpress' ),
			'popular_items'              => esc_html__( 'Popular Districts', 'hyperpress' ),
			'all_items'                  => esc_html__( 'All Districts', 'hyperpress' ),
			'parent_item'                => esc_html__( 'Parent District', 'hyperpress' ),
			'parent_item_colon'          => esc_html__( 'Parent District:', 'hyperpress' ),
			'edit_item'                  => esc_html__( 'Edit District', 'hyperpress' ),
			'view_item'                  => esc_html__( 'View District', 'hyperpress' ),
			'update_item'                => esc_html__( 'Update District', 'hyperpress' ),
			'add_new_item'               => esc_html__( 'Add New District', 'hyperpress' ),
			'new_item_name'              => esc_html__( 'New District Name', 'hyperpress' ),
			'separate_items_with_commas' => esc_html__( 'Separate Districts with commas', 'hyperpress' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Districts', 'hyperpress' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Districts', 'hyperpress' ),
			'not_found'                  => esc_html__( 'No Districts found.', 'hyperpress' ),
			'no_terms'                   => esc_html__( 'No Districts', 'hyperpress' ),
			'filter_by_item'             => esc_html__( 'Filter by District', 'hyperpress' ),
			'items_list_navigation'      => esc_html__( 'Districts list pagination', 'hyperpress' ),
			'items_list'                 => esc_html__( 'Districts list', 'hyperpress' ),
			'most_used'                  => esc_html__( 'Most Used', 'hyperpress' ),
			'back_to_items'              => esc_html__( '&larr; Go to Districts', 'hyperpress' ),
		];
		$args   = [
			'label'              => esc_html__( 'Districts', 'hyperpress' ),
			'description'        => esc_html__( 'Subdivisions within FRC', 'hyperpress' ),
			'labels'             => $labels,
			'public'             => false,
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
				'slug'         => 'districts',
			],
		];
		register_taxonomy( 'district', [ 'competition' ], $args );
	}
endif;