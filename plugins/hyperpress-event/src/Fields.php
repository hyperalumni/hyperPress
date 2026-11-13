<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Meta Box fields: the event box and the Events settings box.
 */
final class Fields {

	public const START         = 'hyperpress_event_start';
	public const END           = 'hyperpress_event_end';
	public const VENUE_NAME    = 'hyperpress_event_venue_name';
	public const VENUE_ADDRESS = 'hyperpress_event_venue_address';
	public const SUBTITLE      = 'hyperpress_event_subtitle';
	public const BANNER_COLOR  = 'hyperpress_event_banner_color';
	public const PRODUCTS      = 'hyperpress_event_products';
	public const GALLERIES     = 'hyperpress_event_galleries';

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the event box and the Events settings box.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = self::event_box();
		$boxes[] = self::settings_box();

		return $boxes;
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function event_box(): array {
		$fields = array(
			array(
				'id'          => self::START,
				'name'        => __( 'Start', 'hyperpress' ),
				'desc'        => __( 'When the event starts, in the site timezone.', 'hyperpress' ),
				'type'        => 'datetime',
				'inline'      => false,
				'timestamp'   => false,
				'save_format' => 'Y-m-d H:i',
			),
			array(
				'id'          => self::END,
				'name'        => __( 'End', 'hyperpress' ),
				'desc'        => __( 'Optional. Without a valid end after the start, the event ends at the end of its start day.', 'hyperpress' ),
				'type'        => 'datetime',
				'inline'      => false,
				'timestamp'   => false,
				'save_format' => 'Y-m-d H:i',
			),
			array(
				'id'                => self::VENUE_NAME,
				'name'              => __( 'Venue name', 'hyperpress' ),
				'type'              => 'text',
				'sanitize_callback' => 'sanitize_text_field',
			),
			array(
				'id'                => self::VENUE_ADDRESS,
				'name'              => __( 'Venue address', 'hyperpress' ),
				'type'              => 'text',
				'sanitize_callback' => 'sanitize_text_field',
			),
			array(
				'id'                => self::SUBTITLE,
				'name'              => __( 'Subtitle', 'hyperpress' ),
				'desc'              => __( 'Shown under the title. Leave empty to use the fallback subtitle from the Events settings.', 'hyperpress' ),
				'type'              => 'text',
				'sanitize_callback' => 'sanitize_text_field',
			),
			array(
				'id'   => self::BANNER_COLOR,
				'name' => __( 'Banner colour', 'hyperpress' ),
				'desc' => __( 'Overrides the default banner colour from the Events settings.', 'hyperpress' ),
				'type' => 'color',
			),
		);

		if ( Integrations::woocommerce() ) {
			$fields[] = array(
				'id'          => self::PRODUCTS,
				'name'        => __( 'Products', 'hyperpress' ),
				'desc'        => __( 'Tickets and other products sold for this event.', 'hyperpress' ),
				'type'        => 'post',
				'post_type'   => 'product',
				'field_type'  => 'select_advanced',
				'multiple'    => true,
				'placeholder' => __( 'Select products', 'hyperpress' ),
			);
		}

		if ( Integrations::nextgen() ) {
			$fields[] = array(
				'id'          => self::GALLERIES,
				'name'        => __( 'Galleries', 'hyperpress' ),
				'desc'        => __( 'NextGEN galleries shown on this event.', 'hyperpress' ),
				'type'        => 'select_advanced',
				'multiple'    => true,
				'options'     => self::gallery_options(),
				'placeholder' => __( 'Select galleries', 'hyperpress' ),
			);
		}

		return array(
			'id'         => 'hyperpress_event_box',
			'title'      => __( 'Event', 'hyperpress' ),
			'post_types' => array( Plugin::POST_TYPE ),
			'context'    => 'normal',
			'priority'   => 'high',
			'fields'     => $fields,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function settings_box(): array {
		return array(
			'id'             => 'hyperpress_event_settings_box',
			'title'          => __( 'Events', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				array(
					'id'   => Settings::DEFAULT_BANNER_COLOR,
					'name' => __( 'Default banner colour', 'hyperpress' ),
					'desc' => __( 'Used for events that do not set their own banner colour.', 'hyperpress' ),
					'type' => 'color',
				),
				array(
					'id'                => Settings::FALLBACK_SUBTITLE,
					'name'              => __( 'Fallback subtitle', 'hyperpress' ),
					'desc'              => __( 'Shown under the title of events that have no subtitle.', 'hyperpress' ),
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'id'          => Settings::HUB_PAGE,
					'name'        => __( 'Events page', 'hyperpress' ),
					'desc'        => __( 'The page that lists events.', 'hyperpress' ),
					'type'        => 'post',
					'post_type'   => 'page',
					'field_type'  => 'select_advanced',
					'placeholder' => __( 'Select a page', 'hyperpress' ),
				),
				array(
					'id'   => Settings::FRONTPAGE,
					'name' => __( 'Show on the front page', 'hyperpress' ),
					'desc' => __( 'Show an event\'s countdown on the front page automatically while it is coming up.', 'hyperpress' ),
					'type' => 'checkbox',
				),
				array(
					'id'                => Settings::FRONTPAGE_LEAD_DAYS,
					'name'              => __( 'Front page lead time (days)', 'hyperpress' ),
					'desc'              => __( 'How many days before an event starts its countdown appears on the front page.', 'hyperpress' ),
					'type'              => 'number',
					'min'               => 0,
					'max'               => Settings::FRONTPAGE_LEAD_DAYS_MAX,
					'step'              => 1,
					'std'               => Settings::FRONTPAGE_LEAD_DAYS_DEFAULT,
					'sanitize_callback' => array( Settings::class, 'sanitize_lead_days' ),
				),
				array(
					'id'   => Settings::COUNTDOWN_PAST_ZERO,
					'name' => __( 'Count past zero', 'hyperpress' ),
					'desc' => __( 'Keep counting after the start time instead of stopping at zero.', 'hyperpress' ),
					'type' => 'checkbox',
				),
			),
		);
	}

	/**
	 * Gallery choices (ID => title) from Galleries::options(), or none until that class exists.
	 *
	 * @return array<int|string,string>
	 */
	private static function gallery_options(): array {
		$galleries = __NAMESPACE__ . '\\Galleries';
		if ( ! class_exists( $galleries ) || ! method_exists( $galleries, 'options' ) ) {
			return array();
		}
		$options = $galleries::options();

		return is_array( $options ) ? $options : array();
	}
}
