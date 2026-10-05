<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * Characterization test: the tag list in the footer of the content templates.
 * It must behave the same whatever the local variable holding get_the_tags() is called.
 */
final class PostTagsTemplateTest extends WP_UnitTestCase {

	public static function templates(): array {
		return array(
			'content'      => array( 'template-parts/content' ),
			'content-page' => array( 'template-parts/content-page' ),
		);
	}

	public function set_up(): void {
		parent::set_up();
		// WordPress core still hooks the deprecated the_block_template_skip_link() on wp_footer, which trips the test case's deprecation check.
		remove_action( 'wp_footer', 'the_block_template_skip_link' );
	}

	private function render( string $template, int $post_id ): string {
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( have_posts() );
		the_post();

		ob_start();
		get_template_part( $template );
		return ob_get_clean();
	}

	/** @dataProvider templates */
	public function test_a_post_with_tags_lists_them_in_the_footer( string $template ): void {
		$id = self::factory()->post->create();
		wp_set_post_tags( $id, array( 'Alpha tag', 'Beta tag' ) );

		$html = $this->render( $template, $id );

		$this->assertStringContainsString( 'Alpha tag', $html );
		$this->assertStringContainsString( 'Beta tag', $html );
		$this->assertStringContainsString( 'rel="tag"', $html );
	}

	/** @dataProvider templates */
	public function test_a_post_without_tags_prints_no_tag_list( string $template ): void {
		$id = self::factory()->post->create();

		$html = $this->render( $template, $id );

		$this->assertStringNotContainsString( 'rel="tag"', $html );
		$this->assertStringNotContainsString( 'Tags:', $html );
		$this->assertDoesNotMatchRegularExpression( '#</nav>\s*<p>#', $html, 'no empty tag paragraph after the page nav' );
		$this->assertMatchesRegularExpression( '#<footer>\s*</footer>#', $html, 'footer is empty when there are no tags' );
	}
}
