<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'register_sponsor_meta_boxes' ) );
	}

	public static function register_sponsor_meta_boxes( $meta_boxes ) {
		// Sponsor Meta.
		$meta_boxes[] = array(
			'title'    => __( 'Sponsors', 'hyperpress' ),
			'id'       => 'hyperpress_sponsor',
			'panel'    => '',
			'priority' => 135,
		);
		$meta_boxes[] = array(
			'id'         => 'hyperpress_sponsor_information',
			'title'      => 'Sponsor Information',
			'post_types' => array( Plugin::POST_TYPE ),
			'context'    => 'normal',
			'priority'   => 'high',
			'fields'     => array(
				array(
					'name'           => 'Sponsorship Tier',
					'desc'           => 'Select the sponsorship tier for this sponsor in the latest Season of Sponsorship.',
					'id'             => 'hyperpress_sponsor_tier',
					'type'           => 'taxonomy',
					'taxonomy'       => Plugin::TIER,
					'field_type'     => 'select',
					'add_new'        => false,
					'remove_default' => true,
					'required'       => false,
				),
				array(
					'name'              => 'Sponsor Website',
					'id'                => 'hyperpress_sponsor_website',
					'type'              => 'url',
					'placeholder'       => 'https://hyperonline.org',
					'label_description' => '',
					'required'          => false,
					'sanitize_callback' => 'url',
				),
				array(
					'name'        => 'Sponsor Description',
					'id'          => 'hyperpress_sponsor_description',
					'type'        => 'textarea',
					'placeholder' => 'A brief description of the Sponsor',
					'required'    => false,
				),
			),
		);

		return $meta_boxes;
	}
}
