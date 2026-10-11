<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use InvalidArgumentException;

final class TaxonomyRegistrar {

	public const KEY_PATTERN = '/^hyper_[a-z0-9_]{1,26}$/D'; // 32 chars max including "hyper_".

	private const DEFAULTS = array(
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_nav_menus' => true,
		'show_in_rest'      => false,
		'query_var'         => true,
		'capabilities'      => array(
			'manage_terms' => 'manage_options',
			'edit_terms'   => 'manage_options',
			'delete_terms' => 'manage_options',
			'assign_terms' => 'edit_posts',
		),
	);

	/**
	 * Register a taxonomy from a definition. `object_types` may be a list of post types, or the
	 * name of a theme mod holding the list (default: attached to nothing).
	 *
	 * @param array $definition See build().
	 */
	public static function register( array $definition ): void {
		$built = self::build( $definition );
		register_taxonomy(
			$built['key'],
			self::resolve_object_types( $definition['object_types'] ?? null, 'get_theme_mod' ),
			$built['args']
		);
	}

	/**
	 * Resolve the object types list from a list, a theme mod name, or nothing.
	 *
	 * @param array|string|null $object_types List, theme mod name, or null.
	 * @param callable          $get_mod      Callback: function( string $name, $default ): mixed.
	 * @return string[]
	 */
	public static function resolve_object_types( $object_types, callable $get_mod ): array {
		if ( is_array( $object_types ) ) {
			return $object_types;
		}
		if ( is_string( $object_types ) && '' !== $object_types ) {
			return array_values(
				array_filter(
					(array) $get_mod( $object_types, array() ),
					static fn( $type ) => is_string( $type ) && '' !== $type
				)
			);
		}
		return array();
	}

	/**
	 * Pure: turn a definition into register_taxonomy() args.
	 *
	 * Required: key, singular, plural, and the `slug` key (null disables rewrite).
	 * Optional: description, public, publicly_queryable, hierarchical, show_tagcloud, show_in_quick_edit,
	 * show_admin_column, meta_box_cb, locked, args (overrides, win last).
	 *
	 * `locked` (default false) is for taxonomies whose terms are managed only by code (TermSeeder):
	 * edit_terms and delete_terms map to do_not_allow, so nobody can add, edit or delete terms in the
	 * admin, while manage_terms (term screen) and assign_terms (post checklist) keep working.
	 * wp_insert_term() and friends do not check capabilities, so seeding is unaffected.
	 *
	 * @param array $definition Taxonomy definition.
	 * @return array{key:string,args:array}
	 * @throws InvalidArgumentException When the definition is incomplete or the key is invalid.
	 */
	public static function build( array $definition ): array {
		foreach ( array( 'key', 'singular', 'plural' ) as $required ) {
			if ( empty( $definition[ $required ] ) ) {
				throw new InvalidArgumentException( esc_html( "Taxonomy definition is missing '$required'." ) );
			}
		}
		if ( ! array_key_exists( 'slug', $definition ) ) {
			throw new InvalidArgumentException( "Taxonomy definition is missing 'slug' (use null for no rewrite)." );
		}
		$key = (string) $definition['key'];
		if ( ! preg_match( self::KEY_PATTERN, $key ) ) {
			throw new InvalidArgumentException( esc_html( "Invalid taxonomy key '$key': use hyper_ + lowercase letters, digits, underscores, max 32 characters." ) );
		}

		$rewrite = null === $definition['slug']
			? false
			: array(
				'with_front'   => false,
				'hierarchical' => true,
				'slug'         => $definition['slug'],
			);

		$derived = array(
			'label'              => $definition['plural'],
			'description'        => $definition['description'] ?? '',
			'labels'             => Labels::taxonomy( $definition['singular'], $definition['plural'] ),
			'public'             => $definition['public'] ?? true,
			'publicly_queryable' => $definition['publicly_queryable'] ?? true,
			'hierarchical'       => $definition['hierarchical'] ?? true,
			'show_tagcloud'      => $definition['show_tagcloud'] ?? false,
			'show_in_quick_edit' => $definition['show_in_quick_edit'] ?? true,
			'show_admin_column'  => $definition['show_admin_column'] ?? false,
			'rewrite'            => $rewrite,
		);
		if ( ! empty( $definition['locked'] ) ) {
			$derived['capabilities'] = array_merge(
				self::DEFAULTS['capabilities'],
				array(
					'edit_terms'   => 'do_not_allow',
					'delete_terms' => 'do_not_allow',
				)
			);
		}
		if ( array_key_exists( 'meta_box_cb', $definition ) ) {
			$derived['meta_box_cb'] = $definition['meta_box_cb'];
		}

		return array(
			'key'  => $key,
			'args' => array_merge( self::DEFAULTS, $derived, $definition['args'] ?? array() ),
		);
	}
}
