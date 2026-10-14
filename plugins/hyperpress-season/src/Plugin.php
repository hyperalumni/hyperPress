<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

use HyperPress\Season\Shortcodes\SeasonCount;
use HyperPress\Utils\NotFound;
use HyperPress\Utils\TaxonomyRegistrar;
use HyperPress\Utils\TermSeeder;

final class Plugin {

	public const TAXONOMY     = 'hyper_season';
	private const SEED_OPTION = 'hyperpress_season_terms_seeded';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'seed_terms' ), 20 );
		SeasonBanner::register();
		Breadcrumbs::register();
		add_filter( 'default_option_' . Settings::OPTION, array( Settings::class, 'default_option' ), 10, 3 );
		SettingsPage::register();
		MetaBoxes::register();
		AdminFilter::register();
		SeasonOrder::register();
		Rewrite::register();
		NotFound::register( array( self::TAXONOMY ) );
		SeasonCount::register();
		Labels::register();
	}

	public static function register(): void {
		TaxonomyRegistrar::register( self::definition() );
	}

	/**
	 * Seed Season 1 up to the current season (runs on `init`, after registration). The list grows
	 * on August 1, which changes its hash and seeds the new season on the next page load.
	 */
	public static function seed_terms(): void {
		TermSeeder::ensure( self::SEED_OPTION, array( self::TAXONOMY => SeasonTerms::default_terms( current_datetime() ) ) );
	}

	public static function definition(): array {
		return array(
			'key'                => self::TAXONOMY,
			'singular'           => __( 'Season', 'hyperpress' ),
			'plural'             => __( 'Seasons', 'hyperpress' ),
			'description'        => __( 'Build Seasons of Team HYPER', 'hyperpress' ),
			'slug'               => 'seasons',
			'object_types'       => Settings::post_types(),
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'locked'             => true,
		);
	}
}
