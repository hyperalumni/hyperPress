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
	 * @param string $archive_template Template path chosen so far.
	 */
	public static function add_archive_template( $archive_template ): string {
		if ( get_post_type() === Plugin::POST_TYPE ) {
			$archive_template = dirname( __DIR__ ) . '/templates/archive.php';
		}

		return $archive_template;
	}
}
