<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/** URLs and dates that templates print must be escaped for their context, whatever a filter hands them. */
final class TemplateEscapingTest extends WP_UnitTestCase {

	private const HOSTILE = 'x" onmouseover="1';

	public function set_up(): void {
		parent::set_up();
		// Core still hooks the deprecated the_block_template_skip_link() on wp_body_open.
		remove_action( 'wp_body_open', 'the_block_template_skip_link' );
	}

	/** @return string[] names of every attribute found on any element of the HTML fragment. */
	private function attribute_names( string $html ): array {
		$dom = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_NOERROR );
		$names = array();
		foreach ( ( new \DOMXPath( $dom ) )->query( '//@*' ) as $attribute ) {
			$names[] = $attribute->nodeName;
		}
		return $names;
	}

	private function render_404(): string {
		$this->go_to( home_url( '/definitely-not-a-real-page/' ) );
		add_filter( 'home_url', fn( $url ) => $url . self::HOSTILE );
		ob_start();
		include get_template_directory() . '/404.php';
		return (string) ob_get_clean();
	}

	public function test_404_links_are_escaped_and_keep_their_markup(): void {
		$html = $this->render_404();

		$this->assertNotContains( 'onmouseover', $this->attribute_names( $html ) );
		$this->assertMatchesRegularExpression( '#Return to the <a href="http[^"]+">home page</a>#', $html );
		$this->assertStringContainsString( 'Click the <a href="javascript:history.back()">Back</a> button', $html );
	}

	public function test_search_form_action_is_escaped(): void {
		add_filter( 'home_url', fn( $url ) => $url . self::HOSTILE );
		ob_start();
		include get_template_directory() . '/searchform.php';
		$html = (string) ob_get_clean();

		$this->assertNotContains( 'onmouseover', $this->attribute_names( $html ) );
		$this->assertStringContainsString( '<form role="search" method="get" id="searchform" action="http', $html );
	}

	public function test_nothing_found_links_the_new_post_screen_for_editors_on_the_blog_home(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->go_to( home_url( '/' ) );
		ob_start();
		include get_template_directory() . '/template-parts/content-none.php';
		$html = (string) ob_get_clean();

		$this->assertMatchesRegularExpression( '#Ready to publish your first post\? <a href="http[^"]+post-new\.php">Get started here</a>\.#', $html );
	}

	public function test_menu_fallback_links_are_kept(): void {
		ob_start();
		hyperpress_menu_fallback();
		$html = (string) ob_get_clean();

		$this->assertMatchesRegularExpression( '#<a href="http[^"]+nav-menus\.php">Menus</a>#', $html );
		$this->assertMatchesRegularExpression( '#<a href="http[^"]+customize\.php">Customize</a>#', $html );
	}

	public function test_comment_walker_prints_the_formatted_date_as_the_link_text(): void {
		$comment = get_comment(
			self::factory()->comment->create(
				array(
					'comment_post_ID'  => self::factory()->post->create(),
					'comment_approved' => '1',
					'comment_date'     => '2026-03-04 05:06:07',
				)
			)
		);

		ob_start();
		$walker = new \HyperPress_Theme_Comments();
		$out    = '';
		$walker->start_el( $out, $comment, 0, array( 'avatar_size' => 32, 'max_depth' => 5 ) );
		unset( $walker );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<time datetime="2026-03-04T05:06:07', $html );
		$this->assertStringContainsString( '>' . get_comment_date( '', $comment ) . '</a></time>', $html );
	}

	public function test_comment_date_and_link_are_escaped_in_the_comment_walker(): void {
		$post_id = self::factory()->post->create();
		$comment = get_comment(
			self::factory()->comment->create(
				array(
					'comment_post_ID'  => $post_id,
					'comment_approved' => '1',
				)
			)
		);
		add_filter( 'get_comment_date', fn() => self::HOSTILE );
		add_filter( 'get_comment_link', fn( $link ) => $link . self::HOSTILE );

		ob_start();
		$walker = new \HyperPress_Theme_Comments();
		$out    = '';
		$walker->start_el( $out, $comment, 0, array( 'avatar_size' => 32, 'max_depth' => 5 ) );
		unset( $walker );
		$html = (string) ob_get_clean();

		$this->assertNotContains( 'onmouseover', $this->attribute_names( $html ) );
		$this->assertStringContainsString( '<time datetime="', $html );
	}
}
