<?php

if ( ! function_exists( 'foundationpress_gutenberg_support' ) ) :
	function foundationpress_gutenberg_support() {

    // Add foundation color palette to the editor
    add_theme_support( 'editor-color-palette', array(
        array(
            'name' => __( 'Primary Color', 'foundationpress' ),
            'slug' => 'primary',
            'color' => '#0B67B2',
        ),
        array(
            'name' => __( 'Secondary Color', 'foundationpress' ),
            'slug' => 'secondary',
            'color' => '#c0c0c0',
        ),
        array(
            'name' => __( 'Success Color', 'foundationpress' ),
            'slug' => 'success',
            'color' => '#049655',
        ),
        array(
            'name' => __( 'Warning color', 'foundationpress' ),
            'slug' => 'warning',
            'color' => '#F15623',
        ),
        array(
            'name' => __( 'Alert color', 'foundationpress' ),
            'slug' => 'alert',
            'color' => '#C52026',
        )
    ) );

	}

	add_action( 'after_setup_theme', 'foundationpress_gutenberg_support' );
endif;
