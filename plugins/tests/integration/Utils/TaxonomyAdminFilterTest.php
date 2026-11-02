<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\TaxonomyAdminFilter;
use WP_UnitTestCase;

final class TaxonomyAdminFilterTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		register_taxonomy( 'hyper_taf_tree', array( 'post' ), array( 'public' => true, 'hierarchical' => true ) );
		register_taxonomy( 'hyper_taf_novar', array( 'post' ), array( 'public' => true, 'hierarchical' => true, 'query_var' => false ) );
	}

	public function tear_down(): void {
		unset( $_GET['hyper_taf_tree'] );
		unregister_taxonomy( 'hyper_taf_tree' );
		unregister_taxonomy( 'hyper_taf_novar' );
		parent::tear_down();
	}

	private function render( string $taxonomy, string $post_type = 'post', string $label = 'All things' ): string {
		ob_start();
		TaxonomyAdminFilter::render( $taxonomy, $label, $post_type );
		return (string) ob_get_clean();
	}

	private function term( string $name, string $slug, string $taxonomy = 'hyper_taf_tree', int $parent = 0 ): int {
		return self::factory()->term->create( array( 'taxonomy' => $taxonomy, 'name' => $name, 'slug' => $slug, 'parent' => $parent ) );
	}

	public function test_prints_a_dropdown_named_after_the_query_var_with_the_all_option(): void {
		$this->term( 'Alpha', 'alpha' );

		$html = $this->render( 'hyper_taf_tree' );

		$this->assertStringContainsString( "name='hyper_taf_tree'", $html );
		$this->assertStringContainsString( 'All things', $html );
		$this->assertStringContainsString( 'value="alpha"', $html );
	}

	public function test_the_all_label_is_escaped(): void {
		$this->term( 'Alpha', 'alpha' );

		$html = $this->render( 'hyper_taf_tree', 'post', '<b>x</b>' );

		$this->assertStringContainsString( '&lt;b&gt;x&lt;/b&gt;', $html );
		$this->assertStringNotContainsString( '<b>', $html );
	}

	public function test_the_dropdown_has_a_screen_reader_label_tied_to_it(): void {
		$this->term( 'Alpha', 'alpha' );

		$html = $this->render( 'hyper_taf_tree' );

		$this->assertMatchesRegularExpression( '/<label class="screen-reader-text" for="hyper_taf_tree">Filter by [^<]+<\/label>\s*<select[^>]*id=\'hyper_taf_tree\'/', $html );
	}

	public function test_child_terms_are_listed_under_their_parent(): void {
		$parent = $this->term( 'Parent', 'parent' );
		$this->term( 'Child', 'child', 'hyper_taf_tree', $parent );

		$html = $this->render( 'hyper_taf_tree' );

		$this->assertMatchesRegularExpression( '/value="parent".*value="child"/s', $html );
	}

	public function test_the_current_get_value_is_selected(): void {
		$this->term( 'Alpha', 'alpha' );
		$this->term( 'Beta', 'beta' );
		$_GET['hyper_taf_tree'] = 'beta';

		$html = $this->render( 'hyper_taf_tree' );

		$this->assertMatchesRegularExpression( '/value="beta"\s+selected="selected"/', $html );
		$this->assertDoesNotMatchRegularExpression( '/value="alpha"\s+selected="selected"/', $html );
	}

	public function test_prints_nothing_without_terms(): void {
		$this->assertSame( '', $this->render( 'hyper_taf_tree' ) );
	}

	public function test_prints_nothing_for_a_post_type_without_the_taxonomy(): void {
		$this->term( 'Alpha', 'alpha' );

		$this->assertSame( '', $this->render( 'hyper_taf_tree', 'page' ) );
		$this->assertSame( '', $this->render( 'hyper_taf_tree', 'attachment' ) );
	}

	public function test_prints_nothing_for_an_unregistered_taxonomy(): void {
		$this->assertSame( '', $this->render( 'hyper_taf_missing' ) );
	}

	public function test_prints_nothing_for_a_taxonomy_without_a_query_var(): void {
		$this->term( 'Alpha', 'alpha', 'hyper_taf_novar' );

		$this->assertSame( '', $this->render( 'hyper_taf_novar' ) );
	}

	public function test_array_or_markup_get_values_are_ignored_safely(): void {
		$this->term( 'Alpha', 'alpha' );

		$_GET['hyper_taf_tree'] = array( 'x' );
		$html                   = $this->render( 'hyper_taf_tree' );
		$this->assertStringContainsString( 'value="alpha"', $html );
		$this->assertStringNotContainsString( 'selected="selected"', $html );

		$_GET['hyper_taf_tree'] = '"><script>alert(1)</script>';
		$html                   = $this->render( 'hyper_taf_tree' );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_registering_a_taxonomy_twice_keeps_one_entry_in_the_registry(): void {
		$this->term( 'Alpha', 'alpha' );
		TaxonomyAdminFilter::register( 'hyper_taf_tree', static fn() => 'All things' );
		TaxonomyAdminFilter::register( 'hyper_taf_tree', static fn() => 'All things' );

		ob_start();
		do_action( 'restrict_manage_posts', 'post', 'top' );
		$html = (string) ob_get_clean();

		$this->assertSame( 1, substr_count( $html, "name='hyper_taf_tree'" ) );
	}

	public function test_the_label_callable_is_resolved_at_render_time(): void {
		$this->term( 'Alpha', 'alpha' );
		$label = 'Everything old';
		TaxonomyAdminFilter::register( 'hyper_taf_tree', static function () use ( &$label ) {
			return $label;
		} );
		$label = 'Everything new';

		ob_start();
		do_action( 'restrict_manage_posts', 'post', 'top' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Everything new', $html );
		$this->assertStringNotContainsString( 'Everything old', $html );
	}

	public function test_the_hook_prints_nothing_for_a_non_string_post_type(): void {
		$this->term( 'Alpha', 'alpha' );
		TaxonomyAdminFilter::register( 'hyper_taf_tree', static fn() => 'All things' );

		ob_start();
		TaxonomyAdminFilter::on_restrict_manage_posts( array() );
		$this->assertSame( '', (string) ob_get_clean() );
	}
}
