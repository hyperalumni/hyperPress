<?php
/**
 * Registry of label definitions.
 *
 * @package HyperPress\Label
 */

namespace HyperPress\Label;

defined( 'ABSPATH' ) || exit;

/**
 * Holds label definitions, filled lazily through the `hyperpress_label_register` action.
 */
final class Registry {

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Label definitions keyed by ID, in insertion order.
	 *
	 * @var array<string, array{group:string,title:string,callback:callable,default_post_types:string[],priority:int}>
	 */
	private array $items = array();

	/**
	 * Whether the register action has fired.
	 *
	 * @var bool
	 */
	private bool $collected = false;

	/**
	 * Get the shared registry.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Replace the shared registry with a fresh one.
	 *
	 * @param bool $skip_collect When true the new registry is empty and marked as collected.
	 */
	public static function reset( bool $skip_collect = false ): void {
		self::$instance            = new self();
		self::$instance->collected = $skip_collect;
	}

	/**
	 * Register a label. Entries without a callable callback are ignored.
	 *
	 * @param string              $id   Label ID, e.g. `post.date`.
	 * @param array<string,mixed> $args Definition: group, title, callback, default_post_types, priority.
	 */
	public function add( string $id, array $args ): void {
		if ( '' === $id || ! isset( $args['callback'] ) || ! is_callable( $args['callback'] ) ) {
			return;
		}
		$this->items[ $id ] = array(
			'group'              => isset( $args['group'] ) ? (string) $args['group'] : '',
			'title'              => isset( $args['title'] ) ? (string) $args['title'] : '',
			'callback'           => $args['callback'],
			'default_post_types' => isset( $args['default_post_types'] ) ? array_values( array_map( 'strval', (array) $args['default_post_types'] ) ) : array(),
			'priority'           => isset( $args['priority'] ) ? (int) $args['priority'] : 10,
		);
	}

	/**
	 * All definitions, sorted by priority ascending (insertion order for ties).
	 *
	 * @return array<string, array{group:string,title:string,callback:callable,default_post_types:string[],priority:int}>
	 */
	public function all(): array {
		if ( ! $this->collected ) {
			$this->collected = true;
			do_action( 'hyperpress_label_register', $this );
		}
		$items = $this->items;
		uasort(
			$items,
			static fn ( array $a, array $b ): int => $a['priority'] <=> $b['priority']
		);
		return $items;
	}

	/**
	 * Default post types for a label.
	 *
	 * @param string $id Label ID.
	 * @return string[]
	 */
	public function default_post_types( string $id ): array {
		$all   = $this->all();
		$types = isset( $all[ $id ] ) ? $all[ $id ]['default_post_types'] : array();
		/**
		 * Filters the post types a label shows on by default.
		 *
		 * @param string[] $types    Post type names.
		 * @param string   $label_id Label ID.
		 */
		$types = apply_filters( 'hyperpress_label_default_post_types', $types, $id );
		return is_array( $types ) ? array_values( $types ) : array();
	}

	/**
	 * Registered label IDs in display order.
	 *
	 * @return string[]
	 */
	public function ids(): array {
		return array_keys( $this->all() );
	}
}
