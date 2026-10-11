<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Utils\TermSeeder;
use WP_UnitTestCase;

final class LockedTaxonomiesTest extends WP_UnitTestCase {

	private const LOCKED = array( 'hyper_season', 'hyper_program', 'hyper_district', 'hyper_week', 'hyper_competition_type' );

	public static function locked_taxonomies(): array {
		$cases = array();
		foreach ( self::LOCKED as $key ) {
			$cases[ $key ] = array( $key );
		}
		return $cases;
	}

	public function set_up(): void {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** @dataProvider locked_taxonomies */
	public function test_administrator_cannot_add_edit_or_delete_terms( string $key ): void {
		$cap = get_taxonomy( $key )->cap;
		$this->assertFalse( current_user_can( $cap->edit_terms ), "$key: edit_terms" );
		$this->assertFalse( current_user_can( $cap->delete_terms ), "$key: delete_terms" );
	}

	/** @dataProvider locked_taxonomies */
	public function test_administrator_can_still_see_the_term_screen_and_assign_terms( string $key ): void {
		$cap = get_taxonomy( $key )->cap;
		$this->assertTrue( current_user_can( $cap->manage_terms ), "$key: manage_terms" );
		$this->assertTrue( current_user_can( $cap->assign_terms ), "$key: assign_terms" );
	}

	/** @dataProvider locked_taxonomies */
	public function test_per_term_meta_caps_are_denied( string $key ): void {
		$term_id = self::factory()->term->create( array( 'taxonomy' => $key ) );
		$this->assertFalse( current_user_can( 'edit_term', $term_id ), "$key: edit_term" );
		$this->assertFalse( current_user_can( 'delete_term', $term_id ), "$key: delete_term" );
	}

	public function test_tier_stays_editable(): void {
		$cap = get_taxonomy( 'hyper_tier' )->cap;
		$this->assertTrue( current_user_can( $cap->edit_terms ) );
		$this->assertTrue( current_user_can( $cap->delete_terms ) );
	}

	public function test_seeding_ignores_the_lock(): void {
		TermSeeder::seed( 'hyper_week', array( 'Locked Test Week' ) );
		$this->assertInstanceOf( \WP_Term::class, get_term_by( 'name', 'Locked Test Week', 'hyper_week' ) );
	}

	public function test_existing_terms_can_still_be_assigned_to_a_post(): void {
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => 'Locked Test Season' ) );
		$post_id = self::factory()->post->create();
		register_taxonomy_for_object_type( 'hyper_season', 'post' );

		$result = wp_set_object_terms( $post_id, array( $term_id ), 'hyper_season' );

		$this->assertSame( array( $term_id ), array_map( 'intval', $result ) );
		$this->assertSame( array( $term_id ), wp_get_object_terms( $post_id, 'hyper_season', array( 'fields' => 'ids' ) ) );
		unregister_taxonomy_for_object_type( 'hyper_season', 'post' );
	}
}
