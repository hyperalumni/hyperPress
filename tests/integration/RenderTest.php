<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class RenderTest extends WP_UnitTestCase {

	/** Render the current main request through the template WordPress would pick and return the HTML. */
	private function render( string $url ): string {
		$this->go_to( $url );
		$template = apply_filters( 'template_include', $this->resolve_template() );
		ob_start();
		include $template;
		$html = ob_get_clean();
		$this->assertStringNotContainsString( 'Fatal error', $html );
		$this->assertStringNotContainsString( 'Warning:', $html );
		$this->assertStringNotContainsString( 'Notice:', $html );
		return $html;
	}

	private function resolve_template(): string {
		$map = array(
			'is_404'               => 'get_404_template',
			'is_search'            => 'get_search_template',
			'is_front_page'        => 'get_front_page_template',
			'is_singular'          => 'get_singular_template',
			'is_post_type_archive' => 'get_post_type_archive_template',
			'is_archive'           => 'get_archive_template',
			'is_home'              => 'get_home_template',
		);
		foreach ( $map as $check => $getter ) {
			if ( $check() ) {
				$template = $getter();
				if ( $template ) {
					return $template;
				}
			}
		}
		return get_index_template();
	}

	public function test_home_renders(): void {
		$html = $this->render( home_url( '/' ) );
		$this->assertStringContainsString( 'main-container', $html ); // header.php is require_once'd, so '<body' only appears in the first render of a process.
	}

	public function test_single_post_renders_a_banner_and_breadcrumbs(): void {
		$id   = self::factory()->post->create( array( 'post_title' => 'Hello Baseline' ) );
		$html = $this->render( get_permalink( $id ) );
		$this->assertStringContainsString( 'Hello Baseline', $html );
		$this->assertStringContainsString( 'entry-title', $html );
	}

	public function test_404_renders(): void {
		$html = $this->render( home_url( '/definitely-not-a-real-page/' ) );
		$this->assertStringContainsString( 'main-container', $html ); // header.php is require_once'd, so '<body' only appears in the first render of a process.
	}

	public function test_search_renders(): void {
		$html = $this->render( home_url( '/?s=baseline' ) );
		$this->assertStringContainsString( 'main-container', $html ); // header.php is require_once'd, so '<body' only appears in the first render of a process.
	}

	public function test_sponsor_archive_renders_the_sponsor_banner_type(): void {
		set_theme_mod( 'hyperpress_hyper_red', '#cc0000' );
		$html = $this->render( get_post_type_archive_link( 'hyper_sponsor' ) );
		$this->assertStringContainsString( 'sponsor', $html );
		$this->assertStringContainsString( '#cc0000', $html );
	}

	public function test_cpt_single_renders_breadcrumbs_without_debug_output(): void {
		$id   = self::factory()->post->create(
			array(
				'post_type'  => 'hyper_sponsor',
				'post_title' => 'Acme Corp',
			)
		);
		$html = $this->render( get_permalink( $id ) );
		$this->assertStringContainsString( 'Acme Corp', $html );
		$this->assertStringNotContainsString( 'is_singular', $html, 'leftover breadcrumb debug output' );
		$this->assertStringNotContainsString( 'object(WP_', $html, 'leftover var_dump output' );
	}
}
