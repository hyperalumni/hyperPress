<?php
namespace HyperPress\Newsletter;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'register_newsletter_meta_boxes' ) );
	}

	public static function register_newsletter_meta_boxes( $meta_boxes ) {
		// Newsletter Meta
		$meta_boxes[] = array(
			'title'    => __( 'Newsletters', 'hyperpress' ),
			'id'       => 'hyperpress_newsletter',
			'panel'    => '',
			'priority' => 135,
		);
		$meta_boxes[] = array(
			'id'         => 'hyperpress_newsletter_information',
			'title'      => 'Canva Newsletter',
			'post_types' => array( 'hyper_newsletter' ),
			'context'    => 'normal',
			'priority'   => 'high',
			'fields'     => array(
				array(
					'name'              => 'Canva URL',
					'id'                => 'hyperpress_newsletter_canva_url',
					'type'              => 'url',
					'placeholder'       => 'https://www.canva.com/design/DESIGNID/view',
					'label_description' => '',
					'desc'              => 'Share > Embed > Smart embed link',
					'required'          => true,
					'sanitize_callback' => array( self::class, 'canva_url' ),
				),
			),
		);

		return $meta_boxes;
	}

	public static function canva_url( $value ): string {
		if ( str_contains( $value, '?embed' ) ) {
			$value = str_replace( '?embed', '', $value );
		}

		return sanitize_url( $value );
	}
}
