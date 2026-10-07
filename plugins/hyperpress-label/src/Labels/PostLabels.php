<?php
/**
 * Post labels.
 *
 * @package HyperPress\Label
 */

namespace HyperPress\Label\Labels;

use HyperPress\Label\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the date, author, category and comment labels for posts.
 */
final class PostLabels {

	/**
	 * Hook the registration.
	 */
	public static function register(): void {
		add_action( 'hyperpress_label_register', array( self::class, 'register_labels' ) );
	}

	/**
	 * Add the post labels to the registry.
	 *
	 * @param Registry $registry Label registry.
	 */
	public static function register_labels( Registry $registry ): void {
		$registry->add(
			'post.date',
			array(
				'group'              => 'Post',
				'title'              => 'Date',
				'callback'           => array( self::class, 'date' ),
				'default_post_types' => array( 'post' ),
				'priority'           => 10,
			)
		);
		$registry->add(
			'post.author',
			array(
				'group'              => 'Post',
				'title'              => 'Author',
				'callback'           => array( self::class, 'author' ),
				'default_post_types' => array( 'post' ),
				'priority'           => 20,
			)
		);
		$registry->add(
			'post.category',
			array(
				'group'              => 'Post',
				'title'              => 'Category',
				'callback'           => array( self::class, 'category' ),
				'default_post_types' => array( 'post' ),
				'priority'           => 40,
			)
		);
		$registry->add(
			'post.comments',
			array(
				'group'              => 'Post',
				'title'              => 'Comments',
				'callback'           => array( self::class, 'comments' ),
				'default_post_types' => array( 'post' ),
				'priority'           => 50,
			)
		);
	}

	/**
	 * Date and time label.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function date(): array {
		return array(
			array(
				/* translators: 1: post date, 2: post time */
				'label'   => sprintf( __( '%1$s @ %2$s', 'hyperpress' ), get_the_date(), get_the_time() ),
				'title'   => 'Posted At',
				'classes' => array( 'dark' ),
				'url'     => '',
				'rel'     => '',
			),
		);
	}

	/**
	 * Author label.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function author(): array {
		return array(
			array(
				'label'   => get_the_author(),
				'title'   => 'Posted By',
				'classes' => array( 'primary', 'byline', 'author' ),
				'url'     => get_author_posts_url( (int) get_the_author_meta( 'ID' ) ),
				'rel'     => 'author',
			),
		);
	}

	/**
	 * First category label; empty when the post has none.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function category(): array {
		$categories = get_the_category();
		$category   = ! empty( $categories ) ? reset( $categories ) : null;
		if ( empty( $category ) ) {
			return array();
		}
		return array(
			array(
				'label'   => $category->cat_name,
				'title'   => 'Posted In',
				'classes' => array( 'info' ),
				'url'     => get_category_link( $category->term_id ),
				'rel'     => '',
			),
		);
	}

	/**
	 * Comment count label.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function comments(): array {
		$comments_number = (int) get_comments_number();
		return array(
			array(
				'label'   => $comments_number . ( 1 === $comments_number ? ' comment' : ' comments' ),
				'title'   => '',
				'classes' => array( 'neutral' ),
				'url'     => get_comments_link(),
				'rel'     => '',
			),
		);
	}
}
