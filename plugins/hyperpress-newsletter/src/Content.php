<?php
namespace HyperPress\Newsletter;

defined( 'ABSPATH' ) || exit;

final class Content {

	public static function register(): void {
		add_filter( 'content_template', array( self::class, 'add_content_template' ) );
	}

	/**
	 * Supplies the path of the template the theme includes for a newsletter.
	 *
	 * @param string $content_template Template path chosen so far.
	 */
	public static function add_content_template( $content_template ): string {
		if ( get_post_type() === Plugin::POST_TYPE ) {
			$custom_content_template = dirname( __DIR__ ) . '/templates/content.php';
			if ( file_exists( $custom_content_template ) ) {
				return $custom_content_template;
			}
		}

		return $content_template;
	}
}
