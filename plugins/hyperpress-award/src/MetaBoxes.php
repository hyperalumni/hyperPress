<?php
namespace HyperPress\Award;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Banner;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the Award settings box.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'id'             => 'hyperpress_award_box',
			'title'          => __( 'Award', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				array(
					'id'                => Settings::BANNER_SUBTITLE,
					'name'              => __( 'Banner subtitle', 'hyperpress' ),
					'desc'              => __( 'Shown under the title on the awards pages. Shortcodes such as [hyperpress_emphasis]text[/hyperpress_emphasis] work, and so do &lt;strong&gt;, &lt;em&gt; and &lt;br&gt; tags; other markup is removed when the banner is displayed.', 'hyperpress' ),
					'type'              => 'textarea',
					'raw'               => true,
					'std'               => Settings::BANNER_SUBTITLE_DEFAULT,
					'sanitize_callback' => array( Banner::class, 'sanitize_subtitle' ),
				),
			),
		);

		return $boxes;
	}
}
