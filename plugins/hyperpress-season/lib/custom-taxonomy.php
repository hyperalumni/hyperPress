<?php

namespace HYPER_Press_Season;
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Season\hyperpress_register_season_taxonomoy' ) ) :
	add_action( 'init', '\HYPER_Press_Season\hyperpress_register_season_taxonomoy' );

	function hyperpress_register_season_taxonomoy(): void {
		$labels = [
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
			'separate_items_with_commas' => esc_html__( 'Separate Seasons with commas', 'hyperpress' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Seasons', 'hyperpress' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Seasons', 'hyperpress' ),
			'not_found'                  => esc_html__( 'No Seasons found.', 'hyperpress' ),
			'no_terms'                   => esc_html__( 'No Seasons', 'hyperpress' ),
			'filter_by_item'             => esc_html__( 'Filter by Season', 'hyperpress' ),
			'items_list_navigation'      => esc_html__( 'Seasons list pagination', 'hyperpress' ),
			'items_list'                 => esc_html__( 'Seasons list', 'hyperpress' ),
			'most_used'                  => esc_html__( 'Most Used', 'hyperpress' ),
			'back_to_items'              => esc_html__( '&larr; Go to Seasons', 'hyperpress' ),
		];
		$args   = [
			'label'              => esc_html__( 'Seasons', 'hyperpress' ),
			'description'        => esc_html__( 'Build Seasons of Team HYPER', 'hyperpress' ),
			'labels'             => $labels,
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
				'slug'         => 'seasons'
			],
		];
		register_taxonomy( 'season', get_theme_mod( 'hyperpress_season_post_types', [] ), $args );
	}
endif;