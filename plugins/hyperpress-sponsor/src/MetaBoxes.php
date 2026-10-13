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
					'desc'              => __( 'Shown under the title on sponsor pages. Shortcodes such as [hyperpress_emphasis]text[/hyperpress_emphasis] work, and so do <strong>, <em> and <br> tags; other markup is removed when the banner is displayed.', 'hyperpress' ),
					'type'              => 'textarea',
					'raw'               => true,
					'std'               => Settings::BANNER_SUBTITLE_DEFAULT,
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
					'type'              => 'url',
					'placeholder'       => 'https://hyperonline.org',
					'required'          => false,
					'sanitize_callback' => 'esc_url_raw',
				),
				array(
					'name'              => 'Sponsor Description',
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
}
