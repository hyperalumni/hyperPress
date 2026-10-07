<?php
namespace HyperPress\Competition;

use HyperPress\Label\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the district and week labels with hyperpress-label, when that plugin is active.
 */
final class Labels {

	public static function register(): void {
		add_action( 'hyperpress_label_register', array( self::class, 'register_labels' ) );
	}

	/**
	 * Add the district and week labels to the registry.
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
		$registry->add(
			'week.week',
			array(
				'group'              => 'Week',
				'title'              => 'Week',
				'callback'           => array( self::class, 'week_label' ),
				'default_post_types' => \HyperPress\Utils\TaxonomyPostTypes::for_taxonomy( Plugin::WEEK ),
				'priority'           => 37,
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
		return self::first_term_label( Plugin::DISTRICT, 'District', 'secondary' );
	}

	/**
	 * Label for the competition week of the current post; empty when it has none.
	 *
	 * The taxonomy is not public, so the label never links.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function week_label(): array {
		return self::first_term_label( Plugin::WEEK, 'Week', 'alert' );
	}

	/**
	 * Single unlinked label from the first term of a taxonomy on the current post.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $title    Label title.
	 * @param string $css_class Label colour class.
	 * @return array<int,array<string,mixed>>
	 */
	private static function first_term_label( string $taxonomy, string $title, string $css_class ): array {
		$terms = get_the_terms( get_the_ID(), $taxonomy );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return array();
		}
		$term = reset( $terms );

		return array(
			array(
				'label'   => $term->name,
				'title'   => $title,
				'classes' => array( $css_class ),
				'url'     => '',
				'rel'     => '',
			),
		);
	}
}
