<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Competition\Plugin as Competition;
use HyperPress\Program\Plugin as Program;
use HyperPress\Program\Settings as ProgramSettings;
use HyperPress\Sponsor\Plugin as Sponsor;
use WP_Query;
use WP_UnitTestCase;

final class TaxonomyAdminFiltersTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( ProgramSettings::OPTION );
		Program::register();
		Sponsor::register();
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * WordPress keeps the query var of a non-public taxonomy (Tier) only while is_admin() is true, so register it as
	 * wp-admin would.
	 */
	private function as_admin(): void {
		set_current_screen( 'edit-' . Sponsor::POST_TYPE );
		Sponsor::register();
	}

	private function list_table_html( string $post_type ): string {
		ob_start();
		do_action( 'restrict_manage_posts', $post_type, 'top' );
		return (string) ob_get_clean();
	}

	private function term( string $taxonomy, string $slug ): int {
		return self::factory()->term->create( array( 'taxonomy' => $taxonomy, 'name' => 'TAF ' . $slug, 'slug' => 'taf-' . $slug ) );
	}

	private function assign_program_to( array $post_types ): void {
		update_option( ProgramSettings::OPTION, array( ProgramSettings::POST_TYPES => $post_types ) );
		Program::register();
		if ( ! term_exists( 'taf-program', Program::TAXONOMY ) ) {
			$this->term( Program::TAXONOMY, 'program' );
		}
	}

	public function test_competitions_get_district_week_and_type_dropdowns(): void {
		$this->term( Competition::DISTRICT, 'district' );
		$this->term( Competition::WEEK, 'week' );
		$this->term( Competition::TYPE, 'type' );

		$html = $this->list_table_html( Competition::POST_TYPE );

		foreach ( array( 'hyper_district' => 'All districts', 'hyper_week' => 'All weeks', 'hyper_competition_type' => 'All competition types' ) as $name => $label ) {
			$this->assertStringContainsString( "name='$name'", $html );
			$this->assertStringContainsString( $label, $html );
		}
	}

	public function test_sponsors_get_the_tier_dropdown(): void {
		$this->as_admin();
		$this->term( Sponsor::TIER, 'tier' );

		$html = $this->list_table_html( Sponsor::POST_TYPE );

		$this->assertStringContainsString( "name='hyper_tier'", $html );
		$this->assertStringContainsString( 'All tiers', $html );
	}

	public function test_program_appears_only_on_assigned_post_types(): void {
		$this->assign_program_to( array( Competition::POST_TYPE ) );

		$this->assertStringContainsString( 'All programs', $this->list_table_html( Competition::POST_TYPE ) );
		$this->assertStringNotContainsString( 'All programs', $this->list_table_html( Sponsor::POST_TYPE ) );

		$this->assign_program_to( array( Competition::POST_TYPE, Sponsor::POST_TYPE ) );

		$this->assertStringContainsString( 'All programs', $this->list_table_html( Sponsor::POST_TYPE ) );
	}

	public function test_competition_dropdowns_do_not_appear_on_sponsors_or_the_tier_on_competitions(): void {
		$this->term( Competition::DISTRICT, 'district' );
		$this->term( Sponsor::TIER, 'tier' );

		$sponsors     = $this->list_table_html( Sponsor::POST_TYPE );
		$competitions = $this->list_table_html( Competition::POST_TYPE );

		$this->assertStringNotContainsString( 'hyper_district', $sponsors );
		$this->assertStringNotContainsString( 'hyper_tier', $competitions );
	}

	public function test_filtering_competitions_by_district_uses_the_query_var(): void {
		$in  = self::factory()->post->create( array( 'post_type' => Competition::POST_TYPE, 'post_status' => 'publish' ) );
		$out = self::factory()->post->create( array( 'post_type' => Competition::POST_TYPE, 'post_status' => 'publish' ) );
		wp_set_object_terms( $in, array( $this->term( Competition::DISTRICT, 'ne' ) ), Competition::DISTRICT );
		wp_set_object_terms( $out, array( $this->term( Competition::DISTRICT, 'ma' ) ), Competition::DISTRICT );

		$query = new WP_Query( array( 'post_type' => Competition::POST_TYPE, 'hyper_district' => 'taf-ne', 'fields' => 'ids' ) );

		$this->assertSame( array( $in ), $query->posts );
	}

	public function test_the_tier_query_var_exists_in_wp_admin_only(): void {
		$this->assertFalse( get_taxonomy( Sponsor::TIER )->query_var, 'front end: the internal taxonomy cannot be filtered by URL' );

		$this->as_admin();

		$this->assertSame( 'hyper_tier', get_taxonomy( Sponsor::TIER )->query_var );
	}

	public function test_filtering_sponsors_by_tier_uses_the_query_var(): void {
		$this->as_admin();

		$in  = self::factory()->post->create( array( 'post_type' => Sponsor::POST_TYPE, 'post_status' => 'publish' ) );
		$out = self::factory()->post->create( array( 'post_type' => Sponsor::POST_TYPE, 'post_status' => 'publish' ) );
		wp_set_object_terms( $in, array( $this->term( Sponsor::TIER, 'gold' ) ), Sponsor::TIER );
		wp_set_object_terms( $out, array( $this->term( Sponsor::TIER, 'silver' ) ), Sponsor::TIER );

		$query = new WP_Query( array( 'post_type' => Sponsor::POST_TYPE, 'hyper_tier' => 'taf-gold', 'fields' => 'ids' ) );

		$this->assertSame( array( $in ), $query->posts );
	}
}
