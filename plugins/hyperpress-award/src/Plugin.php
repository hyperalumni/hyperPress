<?php
namespace HyperPress\Award;

defined( 'ABSPATH' ) || exit;

use HyperPress\Season\SingularBanner;
use HyperPress\Utils\Banner;
use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\Capabilities;
use HyperPress\Utils\PostTypeRegistrar;
use HyperPress\Utils\Version;

final class Plugin {

	public const POST_TYPE    = 'hyper_award';
	private const CAPS_OPTION = 'hyperpress_award_caps_version';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		Breadcrumbs::register( array( self::POST_TYPE ) );
		add_action( 'init', array( self::class, 'register_banner' ) );
		SingularBanner::register( self::POST_TYPE, __( 'Award', 'hyperpress' ) );
		add_filter( 'default_option_' . Settings::OPTION, array( Settings::class, 'default_option' ), 10, 3 );
		SettingsPage::register();
		MetaBoxes::register();
	}

	public static function register_banner(): void {
		Banner::register(
			self::POST_TYPE,
			array(
				'type'                     => 'award',
				'subtitle'                 => array( Settings::class, 'banner_subtitle' ),
				'color_mod'                => 'hyperpress_hyper_blue',
				'archive_title'            => __( 'Awards', 'hyperpress' ),
				'term_joiner'              => 'in',
				'always_set_archive_title' => true,
			)
		);
	}

	public static function register(): void {
		PostTypeRegistrar::register( self::definition() );
	}

	public static function ensure_capabilities(): void {
		Capabilities::ensure( self::CAPS_OPTION, Version::of( dirname( __DIR__ ) . '/hyperpress-award.php' ), array( self::POST_TYPE ) );
	}

	public static function activate(): void {
		self::register();
		self::ensure_capabilities();
		flush_rewrite_rules();
	}

	public static function definition(): array {
		return array(
			'key'           => self::POST_TYPE,
			'singular'      => __( 'Award', 'hyperpress' ),
			'plural'        => __( 'Awards', 'hyperpress' ),
			'description'   => __( 'Awards won by Team HYPER', 'hyperpress' ),
			'slug'          => 'award',
			'archive'       => 'awards',
			'menu_position' => 24,
			'menu_icon'     => 'dashicons-awards',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'hierarchical'  => false,
		);
	}
}
