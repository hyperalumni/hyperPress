<?php
namespace HyperPress\Robot;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\Capabilities;
use HyperPress\Utils\PostTypeRegistrar;

final class Plugin {

	public const POST_TYPE    = 'hyper_robot';
	private const CAPS_OPTION = 'hyperpress_robot_caps_version';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		Breadcrumbs::register( array( self::POST_TYPE ) );
	}

	public static function register(): void {
		PostTypeRegistrar::register( self::definition() );
	}

	public static function ensure_capabilities(): void {
		Capabilities::ensure( self::CAPS_OPTION, self::version(), array( self::POST_TYPE ) );
	}

	public static function activate(): void {
		self::register();
		self::ensure_capabilities();
		flush_rewrite_rules();
	}

	public static function definition(): array {
		return array(
			'key'           => self::POST_TYPE,
			'singular'      => __( 'Robot', 'hyperpress' ),
			'plural'        => __( 'Robots', 'hyperpress' ),
			'description'   => __( 'Robots made by Team HYPER', 'hyperpress' ),
			'slug'          => 'robot',
			'archive'       => 'robots',
			'menu_position' => 23,
			'menu_icon'     => 'dashicons-superhero',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'revisions' ),
		);
	}

	private static function version(): string {
		$data = get_file_data( dirname( __DIR__ ) . '/hyperpress-robot.php', array( 'version' => 'Version' ) );
		return $data['version'];
	}
}
