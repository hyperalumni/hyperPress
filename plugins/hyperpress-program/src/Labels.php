<?php
namespace HyperPress\Program;

use HyperPress\Label\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the program label with hyperpress-label, when that plugin is active.
 */
final class Labels {

	public static function register(): void {
		add_action( 'hyperpress_label_register', array( self::class, 'register_labels' ) );
	}

	/**
	 * Add the program label to the registry.
	 *
	 * @param Registry $registry Label registry.
	 */
	public static function register_labels( Registry $registry ): void {
		$registry->add(
			'program.programs',
			array(
				'group'              => 'Program',
				'title'              => 'Program',
				'callback'           => array( self::class, 'program_labels' ),
				'default_post_types' => \HyperPress\Utils\TaxonomyPostTypes::for_taxonomy( Plugin::TAXONOMY ),
				'priority'           => 35,
			)
		);
	}

	/**
	 * One label per program assigned to the current post, sorted by name.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function program_labels(): array {
		$terms = get_the_terms( get_the_ID(), Plugin::TAXONOMY );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return array();
		}
		usort(
			$terms,
			static fn ( $a, $b ) => strnatcasecmp( $a->name, $b->name )
		);

		$labels = array();
		foreach ( $terms as $term ) {
			$url      = get_term_link( $term );
			$labels[] = array(
				'label'   => $term->name,
				'title'   => 'Program',
				'classes' => array( 'alert' ),
				'url'     => is_wp_error( $url ) ? '' : $url,
				'rel'     => '',
			);
		}

		return $labels;
	}
}
