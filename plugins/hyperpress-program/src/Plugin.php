<?php
namespace HyperPress\Program;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\TaxonomyRegistrar;

final class Plugin {

	public const TAXONOMY = 'hyper_program';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		Customizer::register();
		MetaBoxes::register();
	}

	public static function register(): void {
		TaxonomyRegistrar::register( self::definition() );
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
