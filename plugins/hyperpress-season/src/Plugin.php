<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

use HyperPress\Season\Shortcodes\SeasonCount;
use HyperPress\Utils\TaxonomyRegistrar;

final class Plugin {

	public const TAXONOMY = 'hyper_season';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		SeasonBanner::register();
		Breadcrumbs::register();
		Customizer::register();
		MetaBoxes::register();
		Rewrite::register();
		SeasonCount::register();
		Labels::register();
	}

	public static function register(): void {
		TaxonomyRegistrar::register( self::definition() );
	}

	public static function definition(): array {
		return array(
			'key'                => self::TAXONOMY,
			'singular'           => __( 'Season', 'hyperpress' ),
			'plural'             => __( 'Seasons', 'hyperpress' ),
			'description'        => __( 'Build Seasons of Team HYPER', 'hyperpress' ),
			'slug'               => 'seasons',
			'object_types'       => 'hyperpress_season_post_types',
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
		);
	}
}
