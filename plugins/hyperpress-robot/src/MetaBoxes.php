<?php
namespace HyperPress\Robot;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the Robot settings box.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'id'             => 'hyperpress_robot_box',
			'title'          => __( 'Robot', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				Settings::archive_banner()->subtitle_field(
					__( 'Banner subtitle', 'hyperpress' ),
					__( 'Shown under the title on the robots pages. Shortcodes such as [hyperpress_emphasis]text[/hyperpress_emphasis] work, and so do &lt;strong&gt;, &lt;em&gt; and &lt;br&gt; tags; other markup is removed when the banner is displayed.', 'hyperpress' ),
					true
				),
				Settings::archive_banner()->image_field(
					__( 'Banner image', 'hyperpress' ),
					__( 'Shown as a full-width image under the banner on the robots pages.', 'hyperpress' )
				),
			),
		);

		return $boxes;
	}
}
