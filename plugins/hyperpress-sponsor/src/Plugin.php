<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Banner;
use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\Capabilities;
use HyperPress\Utils\PostTypeRegistrar;
use HyperPress\Utils\TaxonomyRegistrar;
use HyperPress\Utils\Version;

final class Plugin {

	public const POST_TYPE    = 'hyper_sponsor';
	public const TIER         = 'hyper_tier';
	private const CAPS_OPTION = 'hyperpress_sponsor_caps_version';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		Breadcrumbs::register( array( self::POST_TYPE ) );
		add_action( 'init', array( self::class, 'register_banner' ) );
		Archive::register();
		Content::register();
		Customizer::register();
		Labels::register();
		MetaBoxes::register();
		Widgets::register();
	}

	public static function register_banner(): void {
		Banner::register(
			self::POST_TYPE,
			array(
				'type'          => 'sponsor',
				'subtitle_mod'  => 'hyperpress_sponsor_banner_subtitle',
				'color_mod'     => 'hyperpress_hyper_red',
				'archive_title' => __( 'Sponsors', 'hyperpress' ),
				'term_joiner'   => 'in',
			)
		);
	}

	public static function register(): void {
		PostTypeRegistrar::register( self::definition() );
		TaxonomyRegistrar::register( self::tier_definition() );
	}

	public static function ensure_capabilities(): void {
		Capabilities::ensure( self::CAPS_OPTION, Version::of( dirname( __DIR__ ) . '/hyperpress-sponsor.php' ), array( self::POST_TYPE ) );
	}

	public static function activate(): void {
		self::register();
		self::ensure_capabilities();
		flush_rewrite_rules();
	}

	public static function definition(): array {
		return array(
			'key'                  => self::POST_TYPE,
			'singular'             => __( 'Sponsor', 'hyperpress' ),
			'plural'               => __( 'Sponsors', 'hyperpress' ),
			'description'          => __( 'Sponsors of Team HYPER', 'hyperpress' ),
			'slug'                 => 'sponsor',
			'archive'              => 'sponsors',
			'menu_position'        => 12,
			'menu_icon'            => 'dashicons-businessman',
			'supports'             => array( 'title', 'thumbnail', 'page-attributes', 'revisions' ),
			// Legacy post type is non-hierarchical; only its rewrite rules were hierarchical.
			'hierarchical'         => false,
			'rewrite_hierarchical' => true,
		);
	}

	public static function tier_definition(): array {
		return array(
			'key'                => self::TIER,
			'singular'           => __( 'Tier', 'hyperpress' ),
			'plural'             => __( 'Tiers', 'hyperpress' ),
			'description'        => __( 'Levels of Sponsorship', 'hyperpress' ),
			'slug'               => null,
			'object_types'       => array( self::POST_TYPE ),
			'public'             => false,
			'publicly_queryable' => false,
			'hierarchical'       => false,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => false,
			'show_admin_column'  => true,
			'meta_box_cb'        => false,
			// Legacy tier had query_var disabled on an internal, non-public taxonomy.
			'args'               => array( 'query_var' => false ),
		);
	}
}
