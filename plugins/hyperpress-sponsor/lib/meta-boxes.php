<?php

namespace HYPER_Press_Sponsor;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Sponsor\hyperpress_register_sponsor_meta_boxes' ) ) :
	function hyperpress_register_sponsor_meta_boxes( $meta_boxes ) {
		// Sponsor Meta
		$meta_boxes[] = [
			'title'    => __( 'Sponsors', 'hyperpress' ),
			'id'       => 'hyperpress_sponsor',
			'panel'    => '',
			'priority' => 135,
		];
		$meta_boxes[] = [
			'id'         => 'information',
			'title'      => 'Sponsor Information',
			'post_types' => [ 'sponsor' ],
			'context'    => 'normal',
			'priority'   => 'high',
			'fields'     => [
				[
					'name'           => 'Sponsorship Tier',
					'desc'           => 'Select the sponsorship tier for this sponsor in the latest Season of Sponsorship.',
					'id'             => 'tier',
					'type'           => 'taxonomy',
					'taxonomy'       => 'tier',
					'field_type'     => 'select',
					'add_new'        => false,
					'remove_default' => true,
					'required'       => false
				],
				[
					'name'              => 'Sponsor Website',
					'id'                => 'sponsor_website',
					'type'              => 'url',
					'placeholder'       => 'https://hyperonline.org',
					'label_description' => '',
					'required'          => false,
					'sanitize_callback' => 'url'
				],
				[
					'name'        => 'Sponsor Description',
					'id'          => 'sponsor_description',
					'type'        => 'textarea',
					'placeholder' => 'A brief description of the Sponsor',
					'required'    => false
				]
			]
		];

		return $meta_boxes;
	}

	add_filter( 'rwmb_meta_boxes', '\HYPER_Press_Sponsor\hyperpress_register_sponsor_meta_boxes' );
endif;