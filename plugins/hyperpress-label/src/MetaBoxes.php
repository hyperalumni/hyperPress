<?php
/**
 * The Meta Box fields of the Labels settings page.
 *
 * @package HyperPress\Label
 */

namespace HyperPress\Label;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the Labels settings box: per post type a "show" checkbox and a label list.
 */
final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the Labels settings box: per post type a "show" checkbox and a label list, then the save marker.
	 *
	 * Labels are collected when Meta Box registers its boxes (init, priority 20 as far as we know):
	 * this is the first `Registry::all()` call. Register `hyperpress_label_register` listeners
	 * before init priority 20, normally on `plugins_loaded`. The post types listed here are
	 * remembered so the save normalizer only touches types that had fields.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$options = array();
		foreach ( Registry::instance()->all() as $id => $label ) {
			$options[ $id ] = $label['group'] . ': ' . $label['title'];
		}

		$post_types = Settings::post_types();
		Settings::remember_listed( $post_types );

		$fields = array();
		foreach ( $post_types as $post_type => $plural ) {
			$fields[] = array(
				'id'                => Resolver::enabled_key( $post_type ),
				/* translators: %s: plural post type name. */
				'name'              => sprintf( __( 'Show labels on %s', 'hyperpress' ), $plural ),
				'type'              => 'checkbox',
				'std'               => Resolver::default_enabled( $post_type ),
				'sanitize_callback' => 'rest_sanitize_boolean',
			);
			$fields[] = array(
				'id'                => Resolver::labels_key( $post_type ),
				/* translators: %s: plural post type name. */
				'name'              => sprintf( __( 'Labels on %s', 'hyperpress' ), $plural ),
				'desc'              => __( 'Labels selected here appear on this post type. Select none to show no labels.', 'hyperpress' ),
				'type'              => 'select_advanced',
				'multiple'          => true,
				'options'           => $options,
				'std'               => Resolver::default_labels( $post_type ),
				'sanitize_callback' => array( Settings::class, 'sanitize_labels' ),
			);
		}
		$fields[] = array(
			'id'   => Settings::MARKER,
			'type' => 'hidden',
			'std'  => '1',
		);

		$boxes[] = array(
			'id'             => 'hyperpress_label_box',
			'title'          => __( 'Labels', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => $fields,
		);

		return $boxes;
	}
}
