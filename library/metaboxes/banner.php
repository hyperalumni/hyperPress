<?php

if ( ! function_exists( 'hyperpress_banner_metabox' ) ) :
	function hyperpress_banner_metabox( $meta_boxes ) {
		$prefix = 'hyperpress_banner_';

		$meta_boxes[] = [
			'title'      => __( 'Banner', 'hyperpress' ),
			'id'         => 'banner',
			'post_types' => [ 'post', 'page' ],
			'fields'     => [
				[
					'type'           => 'color',
					'id'             => $prefix . 'background_color',
					'name'           => __( 'Background Color', 'hyperpress' ),
					'desc'           => __( 'Defaults to Parent background', 'hyperpress' ),
					'hide_from_rest' => true,
				],
				[
					'type'       => 'wysiwyg',
					'id'         => $prefix . 'subtitle',
					'name'       => __( 'Banner Subtitle', 'hyperpress' ),
					'desc'       => __( 'Defaults to Homepage subtitle', 'hyperpress' ),
					'raw'        => true,
					'options'    => [
						'media_buttons'    => false,
						'default_editor'   => 'html',
						'drag_drop_upload' => false,
						'textarea_rows'    => 1,
					],
					'limit'      => 100,
					'limit_type' => 'word',
				],
			],
		];

		return $meta_boxes;
	}

	add_filter( 'rwmb_meta_boxes', 'hyperpress_banner_metabox' );
endif;
