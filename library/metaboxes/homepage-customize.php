<?php

if ( ! function_exists( 'hyperpress_homepage_customize_metabox' ) ) :
	function hyperpress_homepage_customize_metabox( $meta_boxes ) {
		$prefix = 'hyperpress_homepage_customize_';

		$meta_boxes[] = [
			'title'  => __( 'Homepage Banner & Countdown', 'hyperpress' ),
			'id'     => 'homepage_banner_countdown',
			'panel'  => '',
			'fields' => [
				[
					'type'        => 'post',
					'id'          => $prefix . 'countdowns',
					'name'        => __( 'Countdowns', 'hyperpress' ),
					'post_type'   => [ 'countdown' ],
					'multiple'    => true,
					'parent'      => false,
					'field_type'  => 'select_advanced',
					'placeholder' => __( 'Select a countdown', 'hyperpress' ),
				],
				[
					'type'           => 'color',
					'id'             => $prefix . 'banner_background_color',
					'name'           => __( 'Background Color', 'hyperpress' ),
					'hide_from_rest' => true,
				],
				[
					'type'  => 'text',
					'id'    => $prefix . 'banner_title',
					'name'  => __( 'Banner Title', 'hyperpress' ),
					'limit' => 20,
				],
				[
					'type'       => 'textarea',
					'id'         => $prefix . 'banner_subtitle',
					'std'        => __( 'Dedicated to improving &amp; expanding the abilities of <span class="emphasis">Team HYPER</span>', 'hyperpress' ),
					'name'       => __( 'Banner Subtitle', 'hyperpress' ),
					'raw'        => true,
					'options'    => [],
					'limit'      => 100,
					'limit_type' => 'word',
				],
			],
		];

		return $meta_boxes;
	}

	add_filter( 'rwmb_meta_boxes', 'hyperpress_homepage_customize_metabox' );
endif;
