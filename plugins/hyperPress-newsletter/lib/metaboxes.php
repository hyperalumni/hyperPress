<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_register_newsletter_meta_boxes' ) ) :
	function hyperpress_register_newsletter_meta_boxes( $meta_boxes ) {
		// Newsletter Meta
		$meta_boxes[] = array(
			'id'         => 'information',
			'title'      => 'Canva Newsletter',
			'post_types' => array( 'newsletter' ),
			'context'    => 'normal',
			'priority'   => 'high',
			'fields'     => array(
				array(
					'name'              => 'Canva URL',
					'id'                => 'canva_newsletter_url',
					'type'              => 'url',
					'placeholder'       => 'https://www.canva.com/design/',
					'label_description' => 'Share > Embed > Smart embed link',
					'desc'              => 'no trailing "?embed"',
					'required'          => true,
					'hide_from_front'   => true,
					'hide_from_rest'    => true
				),
			)
		);

		return $meta_boxes;
	}

	add_filter( 'rwmb_meta_boxes', 'hyperpress_register_newsletter_meta_boxes' );
endif;