<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

final class Archive {

	public static function register(): void {
		add_filter( 'archive_template', array( self::class, 'add_archive_template' ) );
	}

	/**
	 * Supplies the archive template WordPress loads for the sponsor post type.
	 *
	 * @param mixed $archive_template Default chosen so far; returned unchanged when not handled.
	 *
	 * @return mixed
	 */
	public static function add_archive_template( mixed $archive_template ): mixed {
		if ( is_post_type_archive( Plugin::POST_TYPE ) ) {
			$archive_template = dirname( __DIR__ ) . '/templates/archive.php';
		}

		return $archive_template;
	}
}
