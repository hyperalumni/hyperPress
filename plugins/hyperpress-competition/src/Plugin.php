<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Banner;
use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\Capabilities;
use HyperPress\Utils\PostTypeRegistrar;
use HyperPress\Utils\TaxonomyRegistrar;
use HyperPress\Utils\TermSeeder;
use HyperPress\Utils\Version;

final class Plugin {

	public const POST_TYPE    = 'hyper_competition';
	public const DISTRICT     = 'hyper_district';
	public const WEEK         = 'hyper_week';
	public const TYPE         = 'hyper_comp_type';
	private const CAPS_OPTION = 'hyperpress_competition_caps_version';
	// Bump the seed version to seed newly added default terms on existing sites.
	private const SEED_OPTION  = 'hyperpress_competition_terms_seeded';
	private const SEED_VERSION = '1';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		add_action( 'init', array( self::class, 'seed_terms' ), 20 );
		Breadcrumbs::register( array( self::POST_TYPE ) );
		add_action( 'init', array( self::class, 'register_banner' ) );
		Customizer::register();
		MetaBoxes::register();
		Labels::register();
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
		TaxonomyRegistrar::register( self::type_definition() );
	}

	/**
	 * Default terms per taxonomy.
	 *
	 * @return array<string,array>
	 */
	public static function default_terms(): array {
		return array(
			self::DISTRICT => array( 'NE FIRST', 'FTC MA' ),
			self::WEEK     => array_map(
				static fn( int $week ): string => 'Week ' . $week,
				range( 0, 7 )
			),
			self::TYPE     => array(
				'Qualifying'   => array( 'Regional', 'District', 'Qualifier' ),
				'Championship' => array( 'District Championship', 'Regional Championship', 'FIRST Championship' ),
				'Unofficial'   => array( 'Offseason', 'Scrimmage' ),
			),
		);
	}

	/**
	 * Seed the default terms once per seed version (runs on `init`, after registration, and on
	 * activation). Existing terms are never renamed or re-parented, and terms an admin deletes are
	 * not resurrected until the seed version is bumped.
	 */
	public static function seed_terms(): void {
		if ( get_option( self::SEED_OPTION ) === self::SEED_VERSION ) {
			return;
		}
		$terms = self::default_terms();
		foreach ( array_keys( $terms ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				return;
			}
		}
		foreach ( $terms as $taxonomy => $list ) {
			TermSeeder::seed( $taxonomy, $list );
		}
		update_option( self::SEED_OPTION, self::SEED_VERSION );
	}

	public static function ensure_capabilities(): void {
		Capabilities::ensure( self::CAPS_OPTION, Version::of( dirname( __DIR__ ) . '/hyperpress-competition.php' ), array( self::POST_TYPE ) );
	}

	public static function activate(): void {
		self::register();
		self::ensure_capabilities();
		self::seed_terms();
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
			'show_admin_column'  => true,
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
			'show_admin_column'  => true,
		);
	}

	public static function type_definition(): array {
		return array(
			'key'                => self::TYPE,
			'singular'           => __( 'Competition Type', 'hyperpress' ),
			'plural'             => __( 'Competition Types', 'hyperpress' ),
			'description'        => __( 'Kinds of competition: qualifying, championship or unofficial', 'hyperpress' ),
			'slug'               => 'competition-types',
			'object_types'       => array( self::POST_TYPE ),
			'public'             => false,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
		);
	}
}
