<?php
namespace HyperPress\Tests\Support;

/**
 * Single source of truth for what every plugin must look like after the migration plans.
 */
final class Convention {

	public const PLUGINS = array(
		'hyperpress-award',
		'hyperpress-competition',
		'hyperpress-countdown',
		'hyperpress-label',
		'hyperpress-media',
		'hyperpress-newsletter',
		'hyperpress-program',
		'hyperpress-robot',
		'hyperpress-season',
		'hyperpress-shortcodes',
		'hyperpress-social',
		'hyperpress-sponsor',
		'hyperpress-student',
		'hyperpress-utils',
	);

	/** Dependencies first. Utils before everything, then season/program/robot. */
	public const LOAD_ORDER = array(
		'hyperpress-utils',
		'hyperpress-season',
		'hyperpress-program',
		'hyperpress-robot',
		'hyperpress-social',
		'hyperpress-award',
		'hyperpress-competition',
		'hyperpress-countdown',
		'hyperpress-newsletter',
		'hyperpress-sponsor',
		'hyperpress-label',
		'hyperpress-student',
		'hyperpress-shortcodes',
		'hyperpress-media',
	);

	public const REQUIRES_PHP      = '8.3';
	public const REQUIRES_AT_LEAST = '6.9';

	/** Expected `Requires Plugins` per plugin (order-insensitive). */
	public const REQUIRES_PLUGINS = array(
		'hyperpress-award'       => array( 'hyperpress-utils', 'hyperpress-season' ),
		'hyperpress-competition' => array( 'hyperpress-utils', 'hyperpress-season', 'hyperpress-robot', 'meta-box-aio' ),
		'hyperpress-countdown'   => array( 'hyperpress-utils', 'meta-box-aio' ),
		'hyperpress-newsletter'  => array( 'hyperpress-utils', 'hyperpress-season', 'meta-box-aio' ),
		'hyperpress-robot'       => array( 'hyperpress-utils', 'hyperpress-season' ),
		'hyperpress-sponsor'     => array( 'hyperpress-utils', 'hyperpress-season', 'meta-box-aio' ),
		'hyperpress-season'      => array( 'hyperpress-utils', 'meta-box-aio' ),
		'hyperpress-program'     => array( 'hyperpress-utils', 'meta-box-aio' ),
		'hyperpress-social'     => array(),
		'hyperpress-label'       => array(),
		'hyperpress-student'     => array(),
		'hyperpress-shortcodes' => array(),
		'hyperpress-media'      => array( 'safe-svg' ),
		'hyperpress-utils'       => array(),
	);

	/** New post type key => owning plugin folder. */
	public const POST_TYPES = array(
		'hyper_award'       => 'hyperpress-award',
		'hyper_competition' => 'hyperpress-competition',
		'hyper_countdown'   => 'hyperpress-countdown',
		'hyper_newsletter'  => 'hyperpress-newsletter',
		'hyper_robot'       => 'hyperpress-robot',
		'hyper_sponsor'     => 'hyperpress-sponsor',
	);

	/** New taxonomy key => owning plugin folder. */
	public const TAXONOMIES = array(
		'hyper_season'   => 'hyperpress-season',
		'hyper_program'  => 'hyperpress-program',
		'hyper_district' => 'hyperpress-competition',
		'hyper_week'     => 'hyperpress-competition',
		'hyper_tier'     => 'hyperpress-sponsor',
	);

	/** Old registered key => new registered key (post types and taxonomies). */
	public const OLD_TO_NEW = array(
		'award'       => 'hyper_award',
		'competition' => 'hyper_competition',
		'countdown'   => 'hyper_countdown',
		'newsletter'  => 'hyper_newsletter',
		'robot'       => 'hyper_robot',
		'sponsor'     => 'hyper_sponsor',
		'season'      => 'hyper_season',
		'program'     => 'hyper_program',
		'district'    => 'hyper_district',
		'week'        => 'hyper_week',
		'tier'        => 'hyper_tier',
	);

	/** Taxonomies with a fixed attachment. Season and program are theme-mod driven (default: attached to nothing). */
	public const TAXONOMY_OBJECT_TYPES = array(
		'hyper_district' => array( 'hyper_competition' ),
		'hyper_week'     => array( 'hyper_competition' ),
		'hyper_tier'     => array( 'hyper_sponsor' ),
	);

	public const THEME_DRIVEN_TAXONOMIES = array( 'hyper_season', 'hyper_program' );

	public const KEY_MAX_LENGTH = 20;

	public const IDENTIFIER_PATTERN = '/^hyperpress_[a-z0-9_]+$/';

	public static function root(): string {
		return dirname( __DIR__, 3 );
	}

	public static function main_file( string $plugin ): string {
		return self::root() . '/' . $plugin . '/' . $plugin . '.php';
	}
}
