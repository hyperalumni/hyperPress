<?php

namespace HYPER_Press_Newsletter;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Newsletter\hyperpress_register_newsletter_meta_boxes' ) ) :
	add_filter( 'rwmb_meta_boxes', '\HYPER_Press_Newsletter\hyperpress_register_newsletter_meta_boxes' );

	function hyperpress_register_newsletter_meta_boxes( $meta_boxes ) {
		// Newsletter Meta
		$meta_boxes[] = [
			'title'    => __( 'Newsletters', 'hyperpress' ),
			'id'       => 'hyperpress_newsletter',
			'panel'    => '',
			'priority' => 135
		];
		$meta_boxes[] = [
			'id'         => 'information',
			'title'      => 'Canva Newsletter',
			'post_types' => [ 'newsletter' ],
			'context'    => 'normal',
			'priority'   => 'high',
			'fields'     => [
				[
					'name'              => 'Canva URL',
					'id'                => 'canva_newsletter_url',
					'type'              => 'url',
					'placeholder'       => 'https://www.canva.com/design/DESIGNID/view',
					'label_description' => '',
					'desc'              => 'Share > Embed > Smart embed link',
					'required'          => true,
					'sanitize_callback' => '\HYPER_Press_Newsletter\hyperpress_canva_url'
				]
			]
		];

		return $meta_boxes;
	}
endif;

if ( ! function_exists( '\HYPER_Press_Newsletter\hyperpress_canva_url' ) ) :
	function hyperpress_canva_url( $value ): string {
		if ( str_contains( $value, '?embed' ) ) {
			$value = str_replace( '?embed', '', $value );
		}

		return sanitize_url( $value );
	}

endif;