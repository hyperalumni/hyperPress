<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Banner;
use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\Capabilities;
use HyperPress\Utils\PostTypeRegistrar;
use HyperPress\Utils\TaxonomyRegistrar;
use HyperPress\Utils\Version;

final class Plugin {

	public const POST_TYPE    = 'hyper_competition';
	public const DISTRICT     = 'hyper_district';
	public const WEEK         = 'hyper_week';
	private const CAPS_OPTION = 'hyperpress_competition_caps_version';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		Breadcrumbs::register( array( self::POST_TYPE ) );
		add_action( 'init', array( self::class, 'register_banner' ) );
		Customizer::register();
		MetaBoxes::register();
	}

	public static function register_banner(): void {
		Banner::register(
			self::POST_TYPE,
			array(
				'type'          => 'competition',
				'subtitle_mod'  => 'hyperpress_competition_banner_subtitle',
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => __( 'Competitions', 'hyperpress' ),
				'term_joiner'   => 'in',
			)
		);
	}

	public static function register(): void {
		PostTypeRegistrar::register( self::definition() );
		TaxonomyRegistrar::register( self::district_definition() );
		TaxonomyRegistrar::register( self::week_definition() );
	}

	public static function ensure_capabilities(): void {
		Capabilities::ensure( self::CAPS_OPTION, Version::of( dirname( __DIR__ ) . '/hyperpress-competition.php' ), array( self::POST_TYPE ) );
	}

	public static function activate(): void {
		self::register();
		self::ensure_capabilities();
		flush_rewrite_rules();
	}

	public static function definition(): array {
		return array(
			'key'                  => self::POST_TYPE,
			'singular'             => __( 'Competition', 'hyperpress' ),
			'plural'               => __( 'Competitions', 'hyperpress' ),
			'description'          => __( 'Competitions competed at by Team HYPER', 'hyperpress' ),
			'slug'                 => 'competition',
			'archive'              => 'competitions',
			'menu_position'        => 22,
			'menu_icon'            => 'dashicons-calendar',
			'supports'             => array( 'title', 'thumbnail', 'revisions' ),
			// Legacy post type is non-hierarchical; only its rewrite rules were hierarchical.
			'hierarchical'         => false,
			'rewrite_hierarchical' => true,
		);
	}

	public static function district_definition(): array {
		return array(
			'key'                => self::DISTRICT,
			'singular'           => __( 'District', 'hyperpress' ),
			'plural'             => __( 'Districts', 'hyperpress' ),
			'description'        => __( 'Subdivisions within FRC', 'hyperpress' ),
			'slug'               => 'districts',
			'object_types'       => array( self::POST_TYPE ),
			'public'             => false,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
		);
	}

	public static function week_definition(): array {
		return array(
			'key'                => self::WEEK,
			'singular'           => __( 'Week', 'hyperpress' ),
			'plural'             => __( 'Weeks', 'hyperpress' ),
			'description'        => __( 'FRC Competition Weeks', 'hyperpress' ),
			'slug'               => 'weeks',
			'object_types'       => array( self::POST_TYPE ),
			'public'             => false,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
		);
	}
}
