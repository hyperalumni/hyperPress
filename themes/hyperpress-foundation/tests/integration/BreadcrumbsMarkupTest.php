<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class BreadcrumbsMarkupTest extends WP_UnitTestCase {

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		parent::tear_down();
	}

	private function render_breadcrumbs(): string {
		ob_start();
		get_template_part( 'template-parts/breadcrumbs' );
		return (string) ob_get_clean();
	}

	public function test_crumb_urls_are_escaped_as_urls(): void {
		add_filter(
			'hyperpress_breadcrumbs_content',
			static function ( array $crumbs ): array {
				$crumbs[] = array(
					'title'   => 'Evil <b>crumb</b>',
					'url'     => 'javascript:alert(1)',
					'classes' => array(
						'li'    => array( 'item-evil' ),
						'bread' => array( 'bread-evil' ),
					),
				);
				return $crumbs;
			}
		);

		$html = $this->render_breadcrumbs();

		$this->assertStringContainsString( 'crumb', $html );
		$this->assertStringNotContainsString( 'javascript:', $html );
		$this->assertStringNotContainsString( '<b>crumb</b>', $html );
	}

	/**
	 * Render the template with one extra crumb and return the title attribute and visible text of that crumb.
	 *
	 * @param array $crumb Crumb to append after Home.
	 * @return array{0:string,1:string,2:string} The raw html, the crumb element's title attribute, its text.
	 */
	private function render_crumb( array $crumb ): array {
		add_filter(
			'hyperpress_breadcrumbs_content',
			static function ( array $crumbs ) use ( $crumb ): array {
				$crumb['classes'] = array(
					'li'    => array( 'item-tip' ),
					'bread' => array( 'bread-tip' ),
				);
				$crumbs[]         = $crumb;
				return $crumbs;
			}
		);

		$html = $this->render_breadcrumbs();
		$dom  = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR );
		$xpath = new \DOMXPath( $dom );
		$node  = $xpath->query( '//li[contains(@class,"item-tip")]/*' )->item( 0 );
		$this->assertNotNull( $node );

		return array( $html, $node->getAttribute( 'title' ), trim( $node->textContent ) );
	}

	public function test_tooltip_is_the_hover_title_of_a_link_crumb_and_the_text_stays(): void {
		list( , $title, $text ) = $this->render_crumb(
			array(
				'title'   => 'Type',
				'tooltip' => 'Competition Type',
				'url'     => 'https://example.test/type/',
			)
		);

		$this->assertSame( 'Competition Type', $title );
		$this->assertSame( 'Type', $text );
	}

	public function test_tooltip_is_the_hover_title_of_a_span_crumb_and_the_text_stays(): void {
		list( $html, $title, $text ) = $this->render_crumb(
			array(
				'title'   => 'Type',
				'tooltip' => 'Competition Type',
			)
		);

		$this->assertStringContainsString( '<span class="bread-tip"', $html );
		$this->assertSame( 'Competition Type', $title );
		$this->assertSame( 'Type', $text );
	}

	public function test_tooltip_markup_and_quotes_are_escaped(): void {
		list( $html, $title ) = $this->render_crumb(
			array(
				'title'   => 'Type',
				'tooltip' => 'A "q" <b>bold</b> onmouseover="1',
			)
		);

		$this->assertStringNotContainsString( '<b>bold</b>', $html );
		$this->assertStringContainsString( 'title="A &quot;q&quot; &lt;b&gt;bold&lt;/b&gt; onmouseover=&quot;1"', $html );
		$this->assertSame( 'A "q" <b>bold</b> onmouseover="1', $title );
	}

	public function test_crumb_without_tooltip_uses_its_title_for_both_variants(): void {
		list( , $link_title, $link_text ) = $this->render_crumb(
			array(
				'title' => 'Plain <i>x</i>',
				'url'   => 'https://example.test/plain/',
			)
		);
		$this->assertSame( 'Plain <i>x</i>', $link_title );
		$this->assertSame( 'Plain <i>x</i>', $link_text );

		remove_all_filters( 'hyperpress_breadcrumbs_content' );
		list( , $span_title ) = $this->render_crumb( array( 'title' => 'Plain' ) );
		$this->assertSame( 'Plain', $span_title );
	}

	public function test_empty_or_non_string_tooltip_falls_back_to_the_title(): void {
		foreach ( array( '', null, 5, array( 'x' ), false ) as $tooltip ) {
			remove_all_filters( 'hyperpress_breadcrumbs_content' );
			list( , $link_title ) = $this->render_crumb(
				array(
					'title'   => 'Type',
					'tooltip' => $tooltip,
					'url'     => 'https://example.test/type/',
				)
			);
			$this->assertSame( 'Type', $link_title );

			remove_all_filters( 'hyperpress_breadcrumbs_content' );
			list( , $span_title ) = $this->render_crumb(
				array(
					'title'   => 'Type',
					'tooltip' => $tooltip,
				)
			);
			$this->assertSame( 'Type', $span_title );
		}
	}

	public function test_author_crumb_links_to_the_author_archive_not_the_website(): void {
		$author_id = self::factory()->user->create(
			array(
				'role'         => 'author',
				'display_name' => 'Ada Author',
				'user_url'     => 'http://author-site.example/',
			)
		);
		self::factory()->post->create( array( 'post_author' => $author_id ) );
		$this->go_to( get_author_posts_url( $author_id ) );
		$this->assertTrue( is_author() );

		$crumbs = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$titles = wp_list_pluck( $crumbs, 'title' );
		$crumb  = $crumbs[ array_search( 'Ada Author', $titles, true ) ];

		$this->assertSame( get_author_posts_url( $author_id ), $crumb['url'] );
		$this->assertStringNotContainsString( 'author-site.example', $crumb['url'] );
	}
}
