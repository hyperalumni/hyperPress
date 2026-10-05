<?php
namespace HyperPress\Newsletter;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	/**
	 * HTML5 pattern (matched against the whole value) for the Canva URL field.
	 */
	public const CANVA_URL_PATTERN = 'https://([a-z0-9\\-]+\\.)*canva\\.com(/[^\\s"\'<>]*)?';

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'register_newsletter_meta_boxes' ) );
	}

	public static function register_newsletter_meta_boxes( $meta_boxes ) {
		// Newsletter Meta.
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
					'desc'              => 'Share > Embed > Smart embed link',
					'required'          => true,
					// Browser-side check (Meta Box validation); canva_url() is the authority on save.
					'pattern'           => self::CANVA_URL_PATTERN,
					'title'             => 'Enter an https link on canva.com, for example https://www.canva.com/design/DESIGNID/view',
					'sanitize_callback' => array( self::class, 'canva_url' ),
				),
			),
		);

		return $meta_boxes;
	}

	/**
	 * Sanitiser for the Canva URL field: keeps only https URLs on canva.com or a subdomain, drops a trailing `?embed`,
	 * and clears anything else.
	 *
	 * @param mixed $value Submitted value.
	 */
	public static function canva_url( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );
		if ( str_ends_with( $value, '?embed' ) ) {
			$value = substr( $value, 0, -strlen( '?embed' ) );
		}

		return self::is_canva_url( $value ) ? $value : '';
	}

	/**
	 * True for an https URL on canva.com or one of its subdomains that needs no escaping to be used as an attribute.
	 *
	 * @param mixed $url Candidate URL.
	 */
	public static function is_canva_url( $url ): bool {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}

		$parts = wp_parse_url( $url );
		$host  = strtolower( $parts['host'] ?? '' );

		return 'https' === ( $parts['scheme'] ?? '' )
			&& ( 'canva.com' === $host || str_ends_with( $host, '.canva.com' ) )
			&& esc_url_raw( $url ) === $url;
	}
}
