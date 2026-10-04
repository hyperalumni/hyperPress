<?php

namespace HYPER_Press_Season;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

use HYPER_Press_Utils\HYPERpress_Dropdown_Post_Type_Control;
use WP_Customize_Code_Editor_Control;


if ( ! function_exists( '\HYPER_Press_Season\hyperpress_customize_season' ) ) :
	add_action( 'customize_register', '\HYPER_Press_Season\hyperpress_customize_season' );

	function hyperpress_customize_season( $wp_customize ): void {
		$wp_customize->add_setting( 'hyperpress_season_banner_subtitle',
			[
				'default'   => '',
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			] );
		$wp_customize->add_control( new WP_Customize_Code_Editor_Control( $wp_customize, 'season_banner_subtitle',
			[
				'label'    => __( 'Season Banner Subtitle', 'hyperpress' ),
				'settings' => 'hyperpress_season_banner_subtitle',
				'section'  => 'hyperpress_season',
			] ) );


		if ( class_exists( '\HYPER_Press_Utils\HYPERpress_Dropdown_Post_Type_Control' ) ) {
			$wp_customize->add_setting( 'hyperpress_season_post_types', [
				'type'              => 'theme_mod',
				'default'           => [ 'post' ],
				'sanitize_callback' => '',
			] );
			$wp_customize->add_control( new HYPERpress_Dropdown_Post_Type_Control( $wp_customize, 'season_post_types', [
				'section'     => 'hyperpress_season',
				'label'       => __( 'Seasons Post Types', 'hyperpress' ),
				'description' => __( 'Select the Post Types that will use the Season Taxonomy', 'hyperpress' ),
				'settings'    => 'hyperpress_season_post_types',
			] ) );
		}

		$wp_customize->add_setting( 'hyperpress_season_current', [
			'type'              => 'theme_mod',
			'default'           => '',
			'sanitize_callback' => '',
		] );
		$seasons = get_terms( [
			'taxonomy'   => 'season',
			'hide_empty' => false,
			'orderby'    => 'term_id',
			'order'      => 'ASC'
		] );
		$seasons = array_reduce( $seasons,
			function ( $carry, $season ) {
				$carry[ $season->slug ] = $season->name;

				return $carry;
			}, [] );
		$wp_customize->add_control( 'hyperpress_season_current', [
			'section'     => 'hyperpress_season',
			'label'       => __( 'Current Season', 'hyperpress' ),
			'description' => __( 'Select the current season for the site', 'hyperpress' ),
			'settings'    => 'hyperpress_season_current',
			'type'        => 'select',
			'choices'     => $seasons ?? []
		] );
	}
endif;

