<?php

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Controls\CategoryDropdownControl;
use HyperPress\Utils\Controls\PostTypeDropdownControl;

add_action( 'customize_register', 'hyperpress_customize_homepage' );

/**
 * Sanitize the homepage blog categories: existing category term ids only.
 *
 * The control is a multiple select, so the value is normally a list; a single id is accepted too.
 *
 * @param mixed $value The submitted value.
 * @return int|int[] Existing category ids (0 for a single id that does not exist).
 */
function hyperpress_sanitize_home_categories( $value ) {
	$existing = static function ( $id ): bool {
		$id = absint( $id );
		return $id > 0 && term_exists( $id, 'category' );
	};

	if ( is_array( $value ) ) {
		return array_values( array_map( 'absint', array_filter( $value, $existing ) ) );
	}

	return $existing( $value ) ? absint( $value ) : 0;
}

/**
 * Sanitize the homepage post types: registered, public post types only.
 *
 * @param mixed $value The submitted value.
 * @return string[]
 */
function hyperpress_sanitize_home_post_types( $value ): array {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$clean = array();
	foreach ( $value as $post_type ) {
		if ( ! is_string( $post_type ) || ! post_type_exists( $post_type ) ) {
			continue;
		}
		$object = get_post_type_object( $post_type );
		if ( $object && $object->public ) {
			$clean[] = $post_type;
		}
	}

	return array_values( array_unique( $clean ) );
}

function hyperpress_customize_homepage( $wp_customize ): void {
	$wp_customize->add_section(
		'hyperpress_front_page_posts',
		array(
			'title'    => __( 'Front Page Posts', 'hyperpress' ),
			'priority' => 132,
		)
	);

	$wp_customize->add_setting(
        'hyperpress_home_blog_categories',
        array(
			'type'              => 'theme_mod',
			'sanitize_callback' => 'hyperpress_sanitize_home_categories',
		)
        );
	// The control classes come from hyperpress-utils; without it the setting stays but has no control.
	if ( class_exists( CategoryDropdownControl::class ) ) {
		$wp_customize->add_control(
	        new CategoryDropdownControl(
	        $wp_customize,
	        'hyperpress_home_blog_categories',
	        array(
				'section'     => 'hyperpress_front_page_posts',
				'label'       => __( 'Homepage Blog Category', 'hyperpress' ),
				'description' => __( 'Select the category that the homepage will show posts from', 'hyperpress' ),
				'settings'    => 'hyperpress_home_blog_categories',
			)
	        )
	        );
	}

	$wp_customize->add_setting(
        'hyperpress_home_blog_post_types',
        array(
			'type'              => 'theme_mod',
			'default'           => array( 'post' ),
			'sanitize_callback' => 'hyperpress_sanitize_home_post_types',
		)
        );
	// The control classes come from hyperpress-utils; without it the setting stays but has no control.
	if ( class_exists( PostTypeDropdownControl::class ) ) {
		$wp_customize->add_control(
	        new PostTypeDropdownControl(
	        $wp_customize,
	        'hyperpress_home_blog_post_types',
	        array(
				'section'     => 'hyperpress_front_page_posts',
				'label'       => __( 'Homepage Blog Post Types', 'hyperpress' ),
				'description' => __( 'Select the Post Types that the homepage will display', 'hyperpress' ),
				'settings'    => 'hyperpress_home_blog_post_types',
			)
	        )
	        );
	}
}
