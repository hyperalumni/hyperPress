<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Banner;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'register_sponsor_meta_boxes' ) );
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the Sponsor settings box.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'id'             => 'hyperpress_sponsor_box',
			'title'          => __( 'Sponsor', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				array(
					'id'                => Settings::BANNER_SUBTITLE,
					'name'              => __( 'Sponsor Banner Subtitle', 'hyperpress' ),
					'desc'              => __( 'Shown under the title on sponsor pages. Shortcodes such as [hyperpress_emphasis]text[/hyperpress_emphasis] work, and so do &lt;strong&gt;, &lt;em&gt; and &lt;br&gt; tags; other markup is removed when the banner is displayed.', 'hyperpress' ),
					'type'              => 'textarea',
					'raw'               => true,
					'sanitize_callback' => array( Banner::class, 'sanitize_subtitle' ),
				),
			),
		);

		return $boxes;
	}

	public static function register_sponsor_meta_boxes( $meta_boxes ) {
		// Sponsor Meta.
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
					'type'              => 'link',
					'desc'              => __( 'Optional link text; defaults to “Visit our sponsor”.', 'hyperpress' ),
					'required'          => false,
					'sanitize_callback' => array( self::class, 'website_link' ),
				),
				array(
					'name'              => 'Sponsor Description',
					'desc'              => __( 'Add &lt;!--more--&gt; on its own line to end the excerpt shown in sponsor lists; the full text shows on the sponsor\'s page.', 'hyperpress' ),
					'id'                => 'hyperpress_sponsor_description',
					'type'              => 'textarea',
					'placeholder'       => 'A brief description of the Sponsor',
					'required'          => false,
					'sanitize_callback' => 'wp_kses_post',
				),
			),
		);

		return $meta_boxes;
	}

	/**
	 * Sanitizer for the sponsor website. A Meta Box `link` field submits an array
	 * (url, title, target, post_id). Returns that array sanitized, or array() when there is
	 * no safe URL, which makes Meta Box delete the meta.
	 *
	 * @param mixed $value Submitted value.
	 * @return array{url?:string,title?:string,target?:string,post_id?:int}
	 */
	public static function website_link( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$url = isset( $value['url'] ) && is_string( $value['url'] ) ? esc_url_raw( $value['url'] ) : '';
		if ( '' === $url ) {
			return array();
		}

		return array(
			'url'     => $url,
			'title'   => isset( $value['title'] ) && is_string( $value['title'] ) ? wp_kses_post( $value['title'] ) : '',
			'target'  => isset( $value['target'] ) && '_blank' === $value['target'] ? '_blank' : '',
			'post_id' => isset( $value['post_id'] ) && is_scalar( $value['post_id'] ) ? absint( $value['post_id'] ) : 0,
		);
	}
}
