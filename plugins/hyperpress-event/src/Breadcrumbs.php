<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumb trails for a single event and for the series archive.
 *
 * Single event: `Events > <first series> > <event title>`. Series archive: `Events > <series name>`. "Events" links to the
 * hub page (omitted without one: events have no post type archive), the series crumb links to the series archive and the
 * last crumb has no url. The Home crumb comes from the theme. Other requests pass through unchanged.
 */
final class Breadcrumbs {

	public static function register(): void {
		add_filter( 'hyperpress_breadcrumbs_content', array( self::class, 'filter' ) );
	}

	public static function filter( $items ) {
		if ( is_singular( Plugin::POST_TYPE ) ) {
			$post = get_queried_object();
			if ( ! $post instanceof \WP_Post ) {
				return $items;
			}

			$items = self::with_hub( (array) $items );

			$terms = wp_get_object_terms(
				$post->ID,
				Plugin::SERIES,
				array(
					'orderby' => 'term_id',
					'order'   => 'ASC',
					'number'  => 1,
				)
			);
			$term  = is_array( $terms ) && $terms ? reset( $terms ) : null;
			if ( $term instanceof \WP_Term ) {
				$link    = get_term_link( $term, Plugin::SERIES );
				$items[] = self::crumb( $term->name, is_string( $link ) ? $link : '', 'series' );
			}

			$items[] = self::crumb( get_the_title( $post ), '', 'event' );

			return $items;
		}

		if ( is_tax( Plugin::SERIES ) ) {
			$term = get_queried_object();
			if ( ! $term instanceof \WP_Term ) {
				return $items;
			}

			$items   = self::with_hub( (array) $items );
			$items[] = self::crumb( $term->name, '', 'series' );

			return $items;
		}

		return $items;
	}

	/**
	 * Appends the "Events" crumb when a hub page is set.
	 */
	private static function with_hub( array $items ): array {
		$hub = Settings::hub_page_id();
		if ( 0 === $hub ) {
			return $items;
		}

		$object = get_post_type_object( Plugin::POST_TYPE );
		$label  = $object ? $object->labels->name : __( 'Events', 'hyperpress' );
		$url    = get_permalink( $hub );

		$items[] = self::crumb( $label, is_string( $url ) ? $url : '', 'hub' );

		return $items;
	}

	private static function crumb( string $title, string $url, string $kind ): array {
		$crumb = array(
			'title'   => $title,
			'classes' => array(
				'li'    => array( 'item-event-' . $kind ),
				'bread' => array( 'bread-event-' . $kind ),
			),
		);
		if ( '' !== $url ) {
			$crumb['url'] = $url;
		}

		return $crumb;
	}
}
