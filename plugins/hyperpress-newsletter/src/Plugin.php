<?php
namespace HyperPress\Newsletter;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Banner;
use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\Capabilities;
use HyperPress\Utils\PostTypeRegistrar;
use HyperPress\Utils\Version;

final class Plugin {

	public const POST_TYPE    = 'hyper_newsletter';
	private const CAPS_OPTION = 'hyperpress_newsletter_caps_version';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		Breadcrumbs::register( array( self::POST_TYPE ) );
		add_action( 'init', array( self::class, 'register_banner' ) );
		BannerFilter::register();
		Content::register();
		Customizer::register();
		Labels::register();
		MetaBoxes::register();
	}

	public static function register_banner(): void {
		Banner::register(
			self::POST_TYPE,
			array(
				'type'                     => 'newsletter',
				'subtitle_mod'             => 'hyperpress_newsletter_banner_subtitle',
				'color_mod'                => 'hyperpress_hyper_orange',
				'archive_title'            => __( 'Newsletters', 'hyperpress' ),
				'term_joiner'              => 'from',
				'always_set_archive_title' => true,
			)
		);
	}

	public static function register(): void {
		PostTypeRegistrar::register( self::definition() );
	}

	public static function ensure_capabilities(): void {
		Capabilities::ensure( self::CAPS_OPTION, Version::of( dirname( __DIR__ ) . '/hyperpress-newsletter.php' ), array( self::POST_TYPE ) );
	}

	public static function activate(): void {
		self::register();
		self::ensure_capabilities();
		flush_rewrite_rules();
	}

	public static function definition(): array {
		return array(
			'key'                  => self::POST_TYPE,
			'singular'             => __( 'Newsletter', 'hyperpress' ),
			'plural'               => __( 'Newsletters', 'hyperpress' ),
			'description'          => __( 'Newsletters from Team HYPER Students', 'hyperpress' ),
			'slug'                 => 'newsletter',
			'archive'              => 'newsletters',
			'menu_position'        => 21,
			'menu_icon'            => 'dashicons-media-text',
			'supports'             => array( 'title', 'author', 'editor', 'revisions' ),
			// Legacy post type is non-hierarchical; only its rewrite rules were hierarchical.
			'hierarchical'         => false,
			'rewrite_hierarchical' => true,
		);
	}
}
