<?php
use HyperPress\Utils\ArchiveRequest;

/**
 * ArchiveRequest against real requests.
 */
class ArchiveRequestTest extends WP_UnitTestCase {

	private bool $registered_week = false;

	public function set_up(): void {
		parent::set_up();
		if ( ! taxonomy_exists( 'hyper_week' ) ) {
			register_taxonomy( 'hyper_week', array( 'hyper_sponsor' ), array( 'public' => true ) );
			$this->registered_week = true;
		}
	}

	public function tear_down(): void {
		if ( $this->registered_week ) {
			unregister_taxonomy( 'hyper_week' );
		}
		parent::tear_down();
	}

	public function test_post_type_archive_is_archive_not_singular(): void {
		$this->go_to( get_post_type_archive_link( 'hyper_sponsor' ) );

		$ctx = ArchiveRequest::for_post_type( 'hyper_sponsor' );

		$this->assertTrue( $ctx['is_archive'] );
		$this->assertFalse( $ctx['is_singular'] );
		$this->assertFalse( $ctx['is_tax_season'] );
		$this->assertNull( $ctx['season'] );
		$this->assertSame( array(), $ctx['extra_terms'] );
	}

	public function test_single_post_is_singular(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'hyper_sponsor' ) );
		$this->go_to( get_permalink( $id ) );

		$ctx = ArchiveRequest::for_post_type( 'hyper_sponsor' );

		$this->assertTrue( $ctx['is_singular'] );
		$this->assertFalse( $ctx['is_archive'] );
		$this->assertSame( array(), $ctx['extra_terms'] );
	}

	public function test_season_term_page_exposes_the_season(): void {
		$term = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'hyper_season',
				'name'     => '2026',
			)
		);
		$this->go_to( get_term_link( $term ) );

		$ctx = ArchiveRequest::for_post_type( 'hyper_sponsor' );

		$this->assertTrue( $ctx['is_tax_season'] );
		$this->assertInstanceOf( WP_Term::class, $ctx['season'] );
		$this->assertSame( $term->term_id, $ctx['season']->term_id );

		$taxonomy_term = ArchiveRequest::taxonomy_term( 'hyper_season' );
		$this->assertInstanceOf( WP_Term::class, $taxonomy_term );
		$this->assertSame( $term->term_id, $taxonomy_term->term_id );
	}

	public function test_taxonomy_term_is_null_on_a_post_type_archive_carrying_a_season(): void {
		$term = self::factory()->term->create_and_get( array( 'taxonomy' => 'hyper_season' ) );
		$this->go_to( add_query_arg( 'hyper_season', $term->slug, get_post_type_archive_link( 'hyper_sponsor' ) ) );

		$this->assertTrue( is_post_type_archive( 'hyper_sponsor' ) );
		$this->assertNull( ArchiveRequest::taxonomy_term( 'hyper_season' ) );
	}

	public function test_taxonomy_term_is_null_off_a_taxonomy_request(): void {
		$this->go_to( home_url( '/' ) );

		$this->assertNull( ArchiveRequest::taxonomy_term( 'hyper_season' ) );
	}

	public function test_extra_terms_are_empty_on_another_post_types_archive(): void {
		$week = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'hyper_week',
				'name'     => 'Zz Week ' . wp_generate_password( 6, false ),
			)
		);
		$this->go_to( add_query_arg( 'hyper_week', $week->slug, get_post_type_archive_link( 'hyper_sponsor' ) ) );

		$ctx = ArchiveRequest::for_post_type( 'hyper_competition', array( 'hyper_week' ) );

		$this->assertSame( array(), $ctx['extra_terms'] );
	}

	public function test_extra_terms_are_read_on_this_post_types_archive(): void {
		$week = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'hyper_week',
				'name'     => 'Zz Week ' . wp_generate_password( 6, false ),
			)
		);
		$this->go_to( add_query_arg( 'hyper_week', $week->slug, get_post_type_archive_link( 'hyper_sponsor' ) ) );

		$ctx = ArchiveRequest::for_post_type( 'hyper_sponsor', array( 'hyper_week' ) );

		$this->assertSame( array( $week->name ), $ctx['extra_terms'] );
	}
}
