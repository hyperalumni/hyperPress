<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class SnapshotLegacyTest extends WP_UnitTestCase {

	public const FIXTURE = __DIR__ . '/../fixtures/legacy-registrations.json';

	public function test_write_snapshot_of_current_registrations(): void {
		if ( '1' !== getenv( 'HYPERPRESS_WRITE_SNAPSHOT' ) ) {
			$this->markTestSkipped( 'Set HYPERPRESS_WRITE_SNAPSHOT=1 to regenerate (pre-Plan-B code only).' );
		}

		$snapshot = array(
			'post_types' => array(),
			'taxonomies' => array(),
		);

		foreach ( get_post_types( array( '_builtin' => false ), 'objects' ) as $key => $pt ) {
			$snapshot['post_types'][ $key ] = array(
				'public'              => $pt->public,
				'publicly_queryable'  => $pt->publicly_queryable,
				'hierarchical'        => $pt->hierarchical,
				'exclude_from_search' => $pt->exclude_from_search,
				'show_in_rest'        => $pt->show_in_rest,
				'has_archive'         => $pt->has_archive,
				'rewrite'             => $pt->rewrite,
				'query_var'           => $pt->query_var,
				'menu_position'       => $pt->menu_position,
				'menu_icon'           => $pt->menu_icon,
				'description'         => $pt->description,
				'supports'            => array_keys( get_all_post_type_supports( $key ) ),
				'labels'              => (array) $pt->labels,
			);
		}

		foreach ( get_taxonomies( array( '_builtin' => false ), 'objects' ) as $key => $tax ) {
			$snapshot['taxonomies'][ $key ] = array(
				'object_type'        => $tax->object_type,
				'public'             => $tax->public,
				'publicly_queryable' => $tax->publicly_queryable,
				'hierarchical'       => $tax->hierarchical,
				'show_in_rest'       => $tax->show_in_rest,
				'show_tagcloud'      => $tax->show_tagcloud,
				'show_in_quick_edit' => $tax->show_in_quick_edit,
				'show_admin_column'  => $tax->show_admin_column,
				'rewrite'            => $tax->rewrite,
				'query_var'          => $tax->query_var,
				'cap'                => (array) $tax->cap,
				'labels'             => (array) $tax->labels,
			);
		}

		ksort( $snapshot['post_types'] );
		ksort( $snapshot['taxonomies'] );

		$this->assertEqualsCanonicalizing(
			array_keys( Convention::OLD_TO_NEW ),
			array_merge( array_keys( $snapshot['post_types'] ), array_keys( $snapshot['taxonomies'] ) ),
			'Snapshot keys differ from Convention::OLD_TO_NEW. Fix OLD_TO_NEW to match the real legacy keys.'
		);

		file_put_contents( self::FIXTURE, wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
		$this->assertFileExists( self::FIXTURE );
	}
}
