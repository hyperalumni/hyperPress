<?php
namespace HyperPress\Contact;

use HyperPress\Utils\GoogleMapsKey;

defined( 'ABSPATH' ) || exit;

/**
 * Meta Box fields for the Contact settings page.
 */
final class MetaBoxes {

	private const DEFAULT_MAP = '42.2518999,-71.0007911,17';

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the address and map boxes.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'id'             => 'hyperpress_contact_addresses_box',
			'title'          => __( 'Addresses', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				array(
					'id'                => Settings::ADDRESSES,
					'type'              => 'group',
					'clone'             => true,
					'sort_clone'        => true,
					'collapsible'       => true,
					'group_title'       => '{heading}',
					'add_button'        => __( 'Add address', 'hyperpress' ),
					'sanitize_callback' => array( Addresses::class, 'sanitize' ),
					'fields'            => array(
						array(
							'id'       => 'heading',
							'name'     => __( 'Heading', 'hyperpress' ),
							'type'     => 'text',
							'required' => true,
						),
						array(
							'id'   => 'slug',
							'name' => __( 'Id', 'hyperpress' ),
							'type' => 'text',
							'desc' => __( 'The id used in [hyperpress_address id="..."]. Generated from the heading when left empty.', 'hyperpress' ),
						),
						array(
							'id'   => 'name',
							'name' => __( 'Name', 'hyperpress' ),
							'type' => 'text',
						),
						array(
							'id'   => 'extra',
							'name' => __( 'Extra line', 'hyperpress' ),
							'type' => 'text',
						),
						array(
							'id'   => 'street',
							'name' => __( 'Street', 'hyperpress' ),
							'type' => 'text',
						),
						array(
							'id'   => 'city',
							'name' => __( 'City', 'hyperpress' ),
							'type' => 'text',
						),
						array(
							'id'   => 'state',
							'name' => __( 'State', 'hyperpress' ),
							'type' => 'text',
						),
						array(
							'id'   => 'zip',
							'name' => __( 'ZIP', 'hyperpress' ),
							'type' => 'text',
						),
						array(
							'id'   => 'phone',
							'name' => __( 'Phone', 'hyperpress' ),
							'type' => 'text',
						),
					),
				),
			),
		);

		$boxes[] = array(
			'id'             => 'hyperpress_contact_map_box',
			'title'          => __( 'Map', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				array(
					'id'   => Settings::MAP_ADDRESS,
					'name' => __( 'Map address', 'hyperpress' ),
					'type' => 'text',
					'desc' => __( 'Type an address to move the pin. The Geocoding API must be enabled for the Google Maps key.', 'hyperpress' ),
				),
				array(
					'id'            => Settings::MAP,
					'name'          => __( 'Map', 'hyperpress' ),
					'type'          => 'map',
					'address_field' => Settings::MAP_ADDRESS,
					'api_key'       => GoogleMapsKey::get(),
					'std'           => self::DEFAULT_MAP,
				),
			),
		);

		return $boxes;
	}
}
