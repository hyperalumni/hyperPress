<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class TextDomainTest extends WP_UnitTestCase {

	/** @var array<int, array{string, string}> */
	private array $calls = array();

	public function set_up(): void {
		parent::set_up();
		// WordPress core still hooks the deprecated the_block_template_skip_link() on wp_footer, which trips the test case's deprecation check.
		remove_action( 'wp_footer', 'the_block_template_skip_link' );
		$this->calls = array();
		add_filter(
			'gettext',
			function ( $translation, $text, $domain ) {
				$watched = array( 'FP Small', 'FP Medium', 'FP Large', 'FP XLarge', '&laquo; %title', '%title &raquo;' );
				if ( in_array( $text, $watched, true ) ) {
					$this->calls[] = array( $text, $domain );
				}
				return $translation;
			},
			10,
			3
		);
	}

	private function assert_only_theme_domain( array $expected_texts ): void {
		$texts = array_column( $this->calls, 0 );
		foreach ( $expected_texts as $text ) {
			$this->assertContains( $text, $texts, "'$text' was translated" );
		}
		foreach ( $this->calls as list( $text, $domain ) ) {
			$this->assertSame( 'hyperpress', $domain, "'$text' must use the hyperpress text domain" );
		}
	}

	public function test_image_size_names_use_the_theme_text_domain(): void {
		apply_filters( 'image_size_names_choose', array() );

		$this->assert_only_theme_domain( array( 'FP Small', 'FP Medium', 'FP Large', 'FP XLarge' ) );
	}

	public function test_post_navigation_labels_use_the_theme_text_domain(): void {
		$first  = self::factory()->post->create( array( 'post_date' => '2020-01-01 00:00:00' ) );
		$second = self::factory()->post->create( array( 'post_date' => '2021-01-01 00:00:00' ) );
		$third  = self::factory()->post->create( array( 'post_date' => '2022-01-01 00:00:00' ) );
		$this->go_to( get_permalink( $second ) );
		$this->assertTrue( have_posts() );
		the_post();

		ob_start();
		get_template_part( 'template-parts/post-navigation' );
		$html = ob_get_clean();

		$this->assertStringContainsString( get_permalink( $first ), $html, 'has a previous neighbour' );
		$this->assertStringContainsString( get_permalink( $third ), $html, 'has a next neighbour' );
		$this->assert_only_theme_domain( array( '&laquo; %title', '%title &raquo;' ) );
	}
}
