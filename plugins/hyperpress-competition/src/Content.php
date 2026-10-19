<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

final class Content {

	public static function register(): void {
		add_filter( 'content_template', array( self::class, 'add_content_template' ) );
	}

	/**
	 * Supplies the path of the template the theme includes for a competition.
	 *
	 * @param mixed $content_template Default chosen so far (the theme front page passes a WP_Post); returned unchanged when not handled.
	 *
	 * @return mixed
	 */
	public static function add_content_template( mixed $content_template ): mixed {
		if ( get_post_type() === Plugin::POST_TYPE ) {
			$custom_content_template = dirname( __DIR__ ) . '/templates/content.php';
			if ( file_exists( $custom_content_template ) ) {
				return $custom_content_template;
			}
		}

		return $content_template;
	}

	/**
	 * Whether the competition in the Loop is the page being viewed (not one listed elsewhere).
	 */
	public static function is_full_view(): bool {
		return is_singular( Plugin::POST_TYPE ) && get_the_ID() === get_queried_object_id();
	}
}
