<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Competition\Plugin as Competition;
use HyperPress\Utils\TermSeeder;
use WP_UnitTestCase;

final class TermSeederTest extends WP_UnitTestCase {

	private const TAXONOMY = 'hyper_seed_test';
	private const OPTION   = 'hyperpress_test_terms_seeded';

	public function set_up(): void {
		parent::set_up();
		register_taxonomy( self::TAXONOMY, 'post', array( 'hierarchical' => true ) );
		delete_option( self::OPTION );
	}

	public function tear_down(): void {
		unregister_taxonomy( self::TAXONOMY );
		delete_option( self::OPTION );
		parent::tear_down();
	}

	public function test_seed_creates_parents_children_and_flat_terms(): void {
		TermSeeder::seed( self::TAXONOMY, array( 'Alpha', 'Group' => array( 'One', 'Two' ) ) );

		$group = get_term_by( 'name', 'Group', self::TAXONOMY );
		$this->assertInstanceOf( \WP_Term::class, get_term_by( 'name', 'Alpha', self::TAXONOMY ) );
		$this->assertSame( 0, get_term_by( 'name', 'Alpha', self::TAXONOMY )->parent );
		$this->assertSame( $group->term_id, get_term_by( 'name', 'One', self::TAXONOMY )->parent );
		$this->assertSame( $group->term_id, get_term_by( 'name', 'Two', self::TAXONOMY )->parent );
	}

	public function test_seed_is_idempotent(): void {
		$terms = array( 'Alpha', 'Group' => array( 'One' ) );
		TermSeeder::seed( self::TAXONOMY, $terms );
		TermSeeder::seed( self::TAXONOMY, $terms );
		$this->assertEquals( 3, wp_count_terms( array( 'taxonomy' => self::TAXONOMY, 'hide_empty' => false ) ) );
	}

	public function test_seed_never_renames_or_reparents_existing_terms(): void {
		$other = wp_insert_term( 'Other', self::TAXONOMY );
		$one   = wp_insert_term( 'Renamed One', self::TAXONOMY, array( 'slug' => 'one', 'parent' => $other['term_id'] ) );

		TermSeeder::seed( self::TAXONOMY, array( 'Group' => array( 'One' ) ) );

		$term = get_term( $one['term_id'], self::TAXONOMY );
		$this->assertSame( 'Renamed One', $term->name );
		$this->assertSame( $other['term_id'], $term->parent );
		$this->assertFalse( get_term_by( 'name', 'One', self::TAXONOMY ) );
	}

	public function test_seed_skips_an_unregistered_taxonomy(): void {
		TermSeeder::seed( 'hyper_not_registered', array( 'Alpha' ) );
		$this->assertTrue( true ); // No exception, no warning.
	}

	public function test_ensure_seeds_and_stores_a_hash(): void {
		TermSeeder::ensure( self::OPTION, array( self::TAXONOMY => array( 'Alpha' ) ) );

		$this->assertInstanceOf( \WP_Term::class, get_term_by( 'name', 'Alpha', self::TAXONOMY ) );
		$this->assertNotEmpty( get_option( self::OPTION ) );
	}

	public function test_ensure_with_the_same_terms_does_not_resurrect_a_deleted_term(): void {
		$terms = array( self::TAXONOMY => array( 'Alpha' ) );
		TermSeeder::ensure( self::OPTION, $terms );

		$alpha = get_term_by( 'name', 'Alpha', self::TAXONOMY );
		wp_delete_term( $alpha->term_id, self::TAXONOMY );
		TermSeeder::ensure( self::OPTION, $terms );
		$this->assertFalse( get_term_by( 'name', 'Alpha', self::TAXONOMY ), 'same terms must not resurrect' );
	}

	public function test_ensure_seeds_again_when_the_terms_change(): void {
		TermSeeder::ensure( self::OPTION, array( self::TAXONOMY => array( 'Alpha' ) ) );
		$first_hash = get_option( self::OPTION );

		TermSeeder::ensure( self::OPTION, array( self::TAXONOMY => array( 'Alpha', 'Beta' ) ) );

		$this->assertInstanceOf( \WP_Term::class, get_term_by( 'name', 'Beta', self::TAXONOMY ) );
		$this->assertNotEmpty( get_option( self::OPTION ) );
		$this->assertNotSame( $first_hash, get_option( self::OPTION ) );
	}

	public function test_ensure_skips_and_does_not_record_an_unregistered_taxonomy(): void {
		TermSeeder::ensure( self::OPTION, array( 'hyper_not_registered' => array( 'Alpha' ) ) );
		$this->assertFalse( get_option( self::OPTION ) );
	}

	public function test_ensure_seeds_nothing_when_only_some_taxonomies_are_registered(): void {
		TermSeeder::ensure(
			self::OPTION,
			array(
				self::TAXONOMY         => array( 'Alpha' ),
				'hyper_not_registered' => array( 'Beta' ),
			)
		);

		$this->assertFalse( get_term_by( 'name', 'Alpha', self::TAXONOMY ) );
		$this->assertFalse( get_option( self::OPTION ) );
	}

	public function test_competition_seeds_its_default_terms(): void {
		delete_option( 'hyperpress_competition_terms_seeded' );
		Competition::seed_terms();

		$this->assertInstanceOf( \WP_Term::class, get_term_by( 'name', 'NE FIRST', 'hyper_district' ) );
		$this->assertInstanceOf( \WP_Term::class, get_term_by( 'name', 'FTC MA', 'hyper_district' ) );
		foreach ( range( 0, 7 ) as $week ) {
			$this->assertInstanceOf( \WP_Term::class, get_term_by( 'name', "Week $week", 'hyper_week' ) );
		}
		$championship = get_term_by( 'name', 'Championship', Competition::TYPE );
		$this->assertSame(
			$championship->term_id,
			get_term_by( 'name', 'FIRST Championship', Competition::TYPE )->parent
		);
		$this->assertCount( 11, get_terms( array( 'taxonomy' => Competition::TYPE, 'hide_empty' => false ) ) );
	}

	public function test_program_seeds_first_with_its_programs(): void {
		delete_option( 'hyperpress_program_terms_seeded' );
		\HyperPress\Program\Plugin::seed_terms();

		$first = get_term_by( 'name', 'FIRST', 'hyper_program' );
		foreach ( array( 'FRC', 'FTC', 'FLL' ) as $name ) {
			$this->assertSame( $first->term_id, get_term_by( 'name', $name, 'hyper_program' )->parent );
		}
	}
}
