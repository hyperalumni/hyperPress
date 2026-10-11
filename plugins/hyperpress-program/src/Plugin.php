<?php
namespace HyperPress\Program;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\TaxonomyRegistrar;
use HyperPress\Utils\TermSeeder;

final class Plugin {

	public const TAXONOMY = 'hyper_program';

	private const SEED_OPTION   = 'hyperpress_program_terms_seeded';
	private const DEFAULT_TERMS = array(
		array(
			'name'        => 'FIRST',
			'description' => 'For Inspiration and Recognition of Science and Technology',
			'children'    => array(
				array(
					'name'        => 'FRC',
					'description' => 'FIRST Robotics Competition',
				),
				array(
					'name'        => 'FTC',
					'description' => 'FIRST Tech Challenge',
				),
				array(
					'name'        => 'FLL',
					'description' => 'FIRST LEGO League',
				),
			),
		),
	);

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'seed_terms' ), 20 );
		Customizer::register();
		MetaBoxes::register();
		Labels::register();
	}

	public static function register(): void {
		TaxonomyRegistrar::register( self::definition() );
	}

	/**
	 * Seed the default programs whenever the default list changes, without touching terms an admin edited.
	 * Runs on `init` after registration, so it also covers sites where the plugin is already active.
	 */
	public static function seed_terms(): void {
		TermSeeder::ensure( self::SEED_OPTION, array( self::TAXONOMY => self::DEFAULT_TERMS ) );
	}

	public static function definition(): array {
		return array(
			'key'                => self::TAXONOMY,
			'singular'           => __( 'Program', 'hyperpress' ),
			'plural'             => __( 'Programs', 'hyperpress' ),
			'description'        => __( 'Programs within FIRST', 'hyperpress' ),
			'slug'               => 'programs',
			'object_types'       => 'hyperpress_program_post_types',
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
		);
	}
}
