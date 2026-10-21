<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

use HyperPress\Season\SingularBanner;
use HyperPress\Utils\Banner;
use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\Capabilities;
use HyperPress\Utils\NotFound;
use HyperPress\Utils\PostTypeRegistrar;
use HyperPress\Utils\TaxonomyRegistrar;
use HyperPress\Utils\TermSeeder;
use HyperPress\Utils\Version;

final class Plugin {

	public const POST_TYPE    = 'hyper_competition';
	public const DISTRICT     = 'hyper_district';
	public const WEEK         = 'hyper_week';
	public const TYPE         = 'hyper_competition_type';
	private const CAPS_OPTION = 'hyperpress_competition_caps_version';
	private const SEED_OPTION = 'hyperpress_competition_terms_seeded';

	/**
	 * Taxonomies whose terms the competition archive and single trails show, in order, after the season.
	 *
	 * @var string[]
	 */
	public const BREADCRUMB_TAXONOMIES = array( self::TYPE, self::DISTRICT, self::WEEK );

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		add_action( 'init', array( self::class, 'seed_terms' ), 20 );
		Breadcrumbs::register( array( self::POST_TYPE ), self::BREADCRUMB_TAXONOMIES );
		TaxonomyBreadcrumbs::register();
		Rewrite::register();
		add_filter( 'hyperpress_breadcrumbs_term_url', array( self::class, 'term_crumb_url' ), 10, 4 );
		NotFound::register( array( self::TYPE, self::DISTRICT, self::WEEK ) );
		add_action( 'init', array( self::class, 'register_banner' ) );
		SingularBanner::register( self::POST_TYPE, __( 'Competition', 'hyperpress' ), false );
		add_filter( 'default_option_' . Settings::OPTION, array( Settings::class, 'default_option' ), 10, 3 );
		SettingsPage::register();
		MetaBoxes::register();
		Labels::register();
		Countdown::register();
		Content::register();
		Map::register();
		AdminAssets::register();
	}

	/**
	 * Breadcrumb filter: with a season, an extra term crumb of a competition links to its season-scoped archive.
	 *
	 * @param mixed $url       The term's all-seasons archive URL.
	 * @param mixed $term      The crumb's term.
	 * @param mixed $post_type Post type key.
	 * @param mixed $season    The season term, or null.
	 * @return mixed The season-scoped URL, else $url.
	 */
	public static function term_crumb_url( $url, $term, $post_type, $season ) {
		if ( self::POST_TYPE !== $post_type || ! $term instanceof \WP_Term || ! $season instanceof \WP_Term ) {
			return $url;
		}

		$scoped = Rewrite::archive_url( $season, $term );

		return '' === $scoped ? $url : $scoped;
	}

	public static function register_banner(): void {
		Banner::register(
			self::POST_TYPE,
			array(
				'type'          => 'competition',
				'subtitle'      => array( Settings::class, 'banner_subtitle' ),
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => __( 'Competitions', 'hyperpress' ),
				'term_joiner'   => 'in',
				'taxonomies'    => array( self::DISTRICT, self::WEEK, self::TYPE ),
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
			self::DISTRICT => array(
				array(
					'name'        => 'NE FIRST',
					'description' => 'FIRST Robotics Programs in New England, United States',
				),
				'FTC MA',
			),
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
	 * Seed the default terms whenever the default lists change (runs on `init`, after registration,
	 * and on activation). Existing terms are never renamed or re-parented, and terms an admin
	 * deletes are not resurrected until a default list changes.
	 */
	public static function seed_terms(): void {
		TermSeeder::ensure( self::SEED_OPTION, self::default_terms() );
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
			'locked'             => true,
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
			'locked'             => true,
		);
	}

	public static function type_definition(): array {
		return array(
			'key'                => self::TYPE,
			'singular'           => __( 'Competition Type', 'hyperpress' ),
			'plural'             => __( 'Competition Types', 'hyperpress' ),
			'labels'             => array(
				// Short text of the type archive's breadcrumb (TaxonomyBreadcrumbs); not a WordPress label key.
				'breadcrumb_label' => __( 'Type', 'hyperpress' ),
			),
			'description'        => __( 'Kinds of competition: qualifying, championship or unofficial', 'hyperpress' ),
			'slug'               => 'competition-types',
			'object_types'       => array( self::POST_TYPE ),
			'public'             => false,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'locked'             => true,
		);
	}
}
