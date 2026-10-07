<?php
namespace HyperPress\Competition;

use HyperPress\Label\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the district label with hyperpress-label, when that plugin is active.
 */
final class Labels {

	public static function register(): void {
		add_action( 'hyperpress_label_register', array( self::class, 'register_labels' ) );
	}

	/**
	 * Add the district label to the registry.
	 *
	 * @param Registry $registry Label registry.
	 */
	public static function register_labels( Registry $registry ): void {
		$registry->add(
			'district.district',
			array(
				'group'              => 'District',
				'title'              => 'District',
				'callback'           => array( self::class, 'district_label' ),
				'default_post_types' => \HyperPress\Utils\TaxonomyPostTypes::for_taxonomy( Plugin::DISTRICT ),
				'priority'           => 36,
			)
		);
	}

	/**
	 * Label for the district of the current post; empty when it has none.
	 *
	 * The taxonomy is not public, so the label never links.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function district_label(): array {
		$terms = get_the_terms( get_the_ID(), Plugin::DISTRICT );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return array();
		}
		$term = reset( $terms );

		return array(
			array(
				'label'   => $term->name,
				'title'   => 'District',
				'classes' => array( 'secondary' ),
				'url'     => '',
				'rel'     => '',
			),
		);
	}
}
