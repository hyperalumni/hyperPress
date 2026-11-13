<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Capabilities;
use HyperPress\Utils\PostTypeRegistrar;
use HyperPress\Utils\TaxonomyAdminFilter;
use HyperPress\Utils\TaxonomyRegistrar;
use HyperPress\Utils\Version;

final class Plugin {

	public const POST_TYPE    = 'hyper_event';
	public const SERIES       = 'hyper_event_series';
	public const BASE         = 'event';
	private const CAPS_OPTION = 'hyperpress_event_caps_version';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		Slugs::register();
		Rewrite::register();
		SettingsPage::register();
		Fields::register();
		Countdown::register();
		FrontpageCountdown::register();
		Appearance::register();
		Single::register();
		ListShortcode::register();
		SeriesArchive::register();
		Alias::register();
		Breadcrumbs::register();
		Menus::register();
		Tickets::register();
		Photos::register();
		add_filter( 'default_option_' . Settings::OPTION, array( Settings::class, 'default_option' ), 10, 3 );
	}

	public static function register(): void {
		// The series first: its permastruct sits earlier in the rewrite rules, so `event/series/<term>/` matches before
		// the post type's attachment rule `event/[^/]+/([^/]+)/?$` (which would 404 it).
		TaxonomyRegistrar::register( self::series_definition() );
		PostTypeRegistrar::register( self::definition() );
		TaxonomyAdminFilter::register( self::SERIES, static fn(): string => __( 'All series', 'hyperpress' ) );
	}

	public static function ensure_capabilities(): void {
		Capabilities::ensure( self::CAPS_OPTION, Version::of( dirname( __DIR__ ) . '/hyperpress-event.php' ), array( self::POST_TYPE ) );
	}

	public static function activate(): void {
		self::register();
		self::ensure_capabilities();
		flush_rewrite_rules();
	}

	public static function definition(): array {
		return array(
			'key'           => self::POST_TYPE,
			'singular'      => __( 'Event', 'hyperpress' ),
			'plural'        => __( 'Events', 'hyperpress' ),
			'description'   => __( 'Events run by Team HYPER', 'hyperpress' ),
			'slug'          => self::BASE,
			'archive'       => false,
			'menu_position' => 23,
			'menu_icon'     => 'dashicons-calendar-alt',
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			'args'          => array( 'show_in_rest' => true ),
		);
	}

	public static function series_definition(): array {
		return array(
			'key'               => self::SERIES,
			'singular'          => __( 'Series', 'hyperpress' ),
			'plural'            => __( 'Series', 'hyperpress' ),
			'description'       => __( 'Groups of recurring events', 'hyperpress' ),
			'slug'              => self::BASE . '/' . Slugs::RESERVED,
			'object_types'      => array( self::POST_TYPE ),
			'hierarchical'      => false,
			'show_admin_column' => true,
		);
	}
}
