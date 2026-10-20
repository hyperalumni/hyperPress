<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class PluginHooksRenderTest extends WP_UnitTestCase {

	private string $temp_template = '';

	public function tear_down(): void {
		if ( '' !== $this->temp_template && file_exists( $this->temp_template ) ) {
			unlink( $this->temp_template );
		}
		$this->temp_template = '';
		remove_all_actions( 'hyperpress_after_banner' );
		remove_all_actions( 'hyperpress_before_content' );
		remove_all_actions( 'hyperpress_after_content' );
		remove_all_actions( 'hyperpress_before_main' );
		remove_all_actions( 'hyperpress_after_main' );
		remove_all_filters( 'hyperpress_posts_template' );
		parent::tear_down();
	}

	private function render( string $template ): string {
		global $post; // Templates are included in global scope by the loader.
		ob_start();
		include get_stylesheet_directory() . '/' . $template;
		$html = (string) ob_get_clean();
		$this->assertStringNotContainsString( 'Fatal error', $html );
		return $html;
	}

	public function test_the_banner_partial_fires_after_banner_once_after_the_banner_markup(): void {
		$calls = 0;
		add_action(
			'hyperpress_after_banner',
			static function () use ( &$calls ): void {
				++$calls;
				echo '<!--after-banner-->';
			}
		);
		$this->go_to( home_url( '/' ) );

		ob_start();
		get_template_part( 'template-parts/banner' );
		$html = (string) ob_get_clean();

		$this->assertSame( 1, $calls );
		$this->assertSame( 1, substr_count( $html, '<!--after-banner-->' ) );
		$this->assertGreaterThan( strpos( $html, '</header>' ), strpos( $html, '<!--after-banner-->' ) );
	}

	public function test_an_archive_fires_the_content_and_main_hooks_in_order(): void {
		self::factory()->post->create( array( 'post_title' => 'Archive Loop Post' ) );
		foreach ( array( 'hyperpress_after_banner', 'hyperpress_before_content', 'hyperpress_before_main', 'hyperpress_after_main', 'hyperpress_after_content' ) as $hook ) {
			add_action(
				$hook,
				static function () use ( $hook ): void {
					echo '<!--' . $hook . '-->'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- test marker.
				}
			);
		}
		$this->go_to( home_url( '/?m=' . gmdate( 'Y' ) ) );

		$html = $this->render( 'archive.php' );

		$positions = array(
			strpos( $html, '<!--hyperpress_after_banner-->' ),
			strpos( $html, '<!--hyperpress_before_content-->' ),
			strpos( $html, 'class="main-container' ),
			strpos( $html, '<!--hyperpress_before_main-->' ),
			strpos( $html, 'Archive Loop Post' ),
			strpos( $html, '<!--hyperpress_after_main-->' ),
			strpos( $html, '<!--hyperpress_after_content-->' ),
		);
		$this->assertNotContains( false, $positions );
		$sorted = $positions;
		sort( $sorted );
		$this->assertSame( $sorted, $positions );
	}

	public function test_the_posts_template_filter_replaces_the_archive_loop(): void {
		self::factory()->post->create( array( 'post_title' => 'Normal Loop Post' ) );
		$this->temp_template = (string) tempnam( sys_get_temp_dir(), 'hp-posts-' );
		file_put_contents( $this->temp_template, '<p class="custom-loop">Custom archive loop</p>' );
		add_filter( 'hyperpress_posts_template', fn() => $this->temp_template );
		$this->go_to( home_url( '/?m=' . gmdate( 'Y' ) ) );

		$html = $this->render( 'archive.php' );

		$this->assertStringContainsString( 'Custom archive loop', $html );
		$this->assertStringNotContainsString( 'Normal Loop Post', $html );
		$this->assertStringContainsString( 'class="main-container', $html );
		$this->assertStringContainsString( 'class="banner', $html );
	}

	public function test_the_archive_uses_the_normal_loop_when_nothing_hooks_the_posts_template(): void {
		self::factory()->post->create( array( 'post_title' => 'Normal Loop Post' ) );
		$this->go_to( home_url( '/?m=' . gmdate( 'Y' ) ) );

		$html = $this->render( 'archive.php' );

		$this->assertStringContainsString( 'Normal Loop Post', $html );
	}

	public function test_an_unreadable_posts_template_falls_back_to_the_normal_loop(): void {
		self::factory()->post->create( array( 'post_title' => 'Normal Loop Post' ) );
		add_filter( 'hyperpress_posts_template', static fn() => '/nonexistent/template.php' );
		$this->go_to( home_url( '/?m=' . gmdate( 'Y' ) ) );

		$this->assertStringContainsString( 'Normal Loop Post', $this->render( 'archive.php' ) );
	}
}
