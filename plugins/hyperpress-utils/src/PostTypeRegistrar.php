<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use InvalidArgumentException;

final class PostTypeRegistrar {

	public const KEY_PATTERN = '/^hyper_[a-z0-9_]{1,14}$/D'; // 20 chars max including "hyper_".

	private const DEFAULTS = array(
		'public'              => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'show_in_rest'        => false,
		'show_in_menu'        => true,
		'query_var'           => true,
		'can_export'          => true,
		'delete_with_user'    => false,
		'taxonomies'          => array(),
	);

	/**
	 * Register a post type from a definition.
	 *
	 * @param array $definition See build().
	 */
	public static function register( array $definition ): void {
		$built = self::build( $definition );
		register_post_type( $built['key'], $built['args'] );
	}

	/**
	 * Pure: turn a definition into register_post_type() input.
	 *
	 * Required: key, singular, plural, slug.
	 * Optional: description, archive (string|false), menu_position, menu_icon, supports,
	 * hierarchical (bool), rewrite_hierarchical (bool), cap_plural (string), args (overrides, win last).
	 *
	 * @param array $definition Post type definition.
	 * @return array{key:string,args:array}
	 * @throws InvalidArgumentException When the definition is incomplete or the key is invalid.
	 */
	public static function build( array $definition ): array {
		foreach ( array( 'key', 'singular', 'plural', 'slug' ) as $required ) {
			if ( empty( $definition[ $required ] ) ) {
				throw new InvalidArgumentException( esc_html( "Post type definition is missing '$required'." ) );
			}
		}
		$key = (string) $definition['key'];
		if ( ! preg_match( self::KEY_PATTERN, $key ) ) {
			throw new InvalidArgumentException( esc_html( "Invalid post type key '$key': use hyper_ + lowercase letters, digits, underscores, max 20 characters." ) );
		}

		$rewrite = array(
			'with_front' => false,
			'slug'       => $definition['slug'],
		);
		if ( ! empty( $definition['rewrite_hierarchical'] ) ) {
			$rewrite['hierarchical'] = true;
		}

		$derived = array(
			'label'           => $definition['plural'],
			'description'     => $definition['description'] ?? '',
			'labels'          => Labels::post_type( $definition['singular'], $definition['plural'] ),
			'hierarchical'    => $definition['hierarchical'] ?? false,
			'has_archive'     => $definition['archive'] ?? false,
			'menu_position'   => $definition['menu_position'] ?? null,
			'menu_icon'       => $definition['menu_icon'] ?? null,
			'supports'        => $definition['supports'] ?? array( 'title', 'editor' ),
			'rewrite'         => $rewrite,
			'capability_type' => array( $key, $definition['cap_plural'] ?? $key . 's' ),
			'map_meta_cap'    => true,
		);

		return array(
			'key'  => $key,
			'args' => array_merge( self::DEFAULTS, $derived, $definition['args'] ?? array() ),
		);
	}
}
