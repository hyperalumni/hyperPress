<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class BreadcrumbsMarkupTest extends WP_UnitTestCase {

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
