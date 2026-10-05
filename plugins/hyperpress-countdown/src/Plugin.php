<?php
namespace HyperPress\Countdown;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Capabilities;
use HyperPress\Utils\PostTypeRegistrar;
use HyperPress\Utils\Version;

final class Plugin {

	public const POST_TYPE    = 'hyper_countdown';
	private const CAPS_OPTION = 'hyperpress_countdown_caps_version';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		Customizer::register();
		Assets::register();
		MetaBoxes::register();
		Shortcode::register();
	}

	public static function register(): void {
		PostTypeRegistrar::register( self::definition() );
	}

	public static function ensure_capabilities(): void {
		Capabilities::ensure( self::CAPS_OPTION, Version::of( dirname( __DIR__ ) . '/hyperpress-countdown.php' ), array( self::POST_TYPE ) );
	}

	public static function activate(): void {
		self::register();
		self::ensure_capabilities();
		flush_rewrite_rules();
	}

	public static function definition(): array {
		return array(
			'key'           => self::POST_TYPE,
			'singular'      => __( 'Countdown', 'hyperpress' ),
			'plural'        => __( 'Countdowns', 'hyperpress' ),
			'description'   => __( 'Time Circle Count Downs for Display in the UI', 'hyperpress' ),
			'slug'          => 'countdown', // Required by the registrar; unused because rewrite is disabled below.
			'archive'       => false,
			'menu_position' => 11,
			'menu_icon'     => 'dashicons-clock',
			'supports'      => array( 'title' ), // Legacy also listed 'slug', which is not a real feature.
			'args'          => array(
				'label'               => __( 'Count Downs', 'hyperpress' ), // Legacy 'label' differs from the plural label text.
				'public'              => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'query_var'           => false,
				'rewrite'             => false,
			),
		);
	}
}
