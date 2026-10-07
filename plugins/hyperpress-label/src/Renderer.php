<?php
/**
 * Renders labels on the `hyperpress_labels` action.
 *
 * @package HyperPress\Label
 */

namespace HyperPress\Label;

defined( 'ABSPATH' ) || exit;

/**
 * Collects the labels for the current post type and prints them.
 */
final class Renderer {

	/**
	 * Hook the renderer to `hyperpress_labels`.
	 */
	public static function register(): void {
		add_action( 'hyperpress_labels', array( self::class, 'render' ) );
	}

	/**
	 * The current post type, or an empty string when there is none.
	 */
	private static function current_type(): string {
		$type = get_post_type();
		if ( false === $type && is_singular() ) {
			$object = get_queried_object();
			$type   = $object instanceof \WP_Post ? $object->post_type : false;
		}
		return is_string( $type ) ? $type : '';
	}

	/**
	 * The guarded, filtered and normalized label list for the current request.
	 *
	 * @return array<int,array{label:string,title:string,classes:array<int,string>,url:string,rel:string}>
	 */
	public static function labels(): array {
		$type = self::current_type();
		if ( '' === $type ) {
			return array();
		}
		if ( ! is_singular( $type ) && ! is_post_type_archive( $type ) && ! ( is_main_query() && get_post_type() === $type ) ) {
			return array();
		}

		$labels = array();
		foreach ( Registry::instance()->all() as $id => $definition ) {
			if ( ! Resolver::is_enabled( $type, (string) $id ) ) {
				continue;
			}
			$result = call_user_func( $definition['callback'] );
			if ( ! is_array( $result ) ) {
				continue;
			}
			foreach ( $result as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$labels[] = wp_parse_args(
					$entry,
					array(
						'label'   => '',
						'title'   => '',
						'classes' => array(),
						'url'     => '',
						'rel'     => '',
					)
				);
			}
		}

		/**
		 * Filters the labels shown for the current post.
		 *
		 * @param array $labels Normalized label arrays.
		 */
		$filtered = apply_filters( 'hyperpress_labels_content', $labels );
		return is_array( $filtered ) ? $filtered : array();
	}

	/**
	 * Print the labels using the theme override or the plugin template.
	 */
	public static function render(): void {
		$labels = self::labels();
		if ( empty( $labels ) ) {
			return;
		}
		$template = locate_template( 'hyperpress-label/labels.php' );
		if ( '' === $template ) {
			$template = dirname( __DIR__ ) . '/templates/labels.php';
		}
		include $template;
	}
}
