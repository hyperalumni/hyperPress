<?php
/**
 * Tests for the label Renderer.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Label\Registry;
use HyperPress\Label\Renderer;
use HyperPress\Label\Resolver;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Label\Renderer
 */
final class LabelRendererTest extends WP_UnitTestCase {

	/**
	 * Whether the flag callback ran.
	 *
	 * @var bool
	 */
	public static bool $ran = false;

	/**
	 * Theme override file written by a test, if any.
	 *
	 * @var string
	 */
	private string $override = '';

	/**
	 * Post used by the tests.
	 *
	 * @var int
	 */
	private int $post_id = 0;

	/**
	 * Provider that reports it ran.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function flagged(): array {
		self::$ran = true;
		return array( array( 'label' => 'Flag' ) );
	}

	/**
	 * Provider returning a non-array.
	 *
	 * @return string
	 */
	public static function not_an_array(): string {
		return 'nope';
	}

	/**
	 * Provider mixing good and bad entries.
	 *
	 * @return array<int,mixed>
	 */
	public static function mixed_entries(): array {
		return array( 'junk', array( 'label' => 'X' ), 5 );
	}

	/**
	 * Provider returning a linked label.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function linked(): array {
		return array(
			array(
				'label'   => 'Linked',
				'title'   => 'Tip',
				'classes' => array( 'primary' ),
				'url'     => 'https://example.org/x',
				'rel'     => 'nofollow',
			),
		);
	}

	/**
	 * Provider returning one label named by $name.
	 *
	 * @param string $name Label text.
	 * @return callable
	 */
	private static function named( string $name ): callable {
		return static fn (): array => array( array( 'label' => $name ) );
	}

	/**
	 * Merge values into the saved label settings option.
	 *
	 * @param string $key   Option array key.
	 * @param mixed  $value Value.
	 */
	private function save( string $key, $value ): void {
		update_option( 'hyperpress_label', array_merge( (array) get_option( 'hyperpress_label', array() ), array( $key => $value ) ) );
	}

	public function set_up(): void {
		parent::set_up();
		self::$ran = false;
		Registry::reset( true );
		Renderer::register();
		$this->post_id = self::factory()->post->create();
		$this->go_to_post();
	}

	public function tear_down(): void {
		delete_option( 'hyperpress_label' );
		remove_all_filters( 'hyperpress_labels_content' );
		if ( '' !== $this->override ) {
			if ( file_exists( $this->override ) ) {
				unlink( $this->override );
			}
			if ( is_dir( dirname( $this->override ) ) ) {
				rmdir( dirname( $this->override ) );
			}
		}
		Registry::reset( true );
		parent::tear_down();
	}

	/**
	 * Visit the test post and set up the loop.
	 */
	private function go_to_post(): void {
		$this->go_to( get_permalink( $this->post_id ) );
		$GLOBALS['post'] = get_post( $this->post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test setup.
		setup_postdata( $GLOBALS['post'] );
	}

	/**
	 * Register a label on `post`.
	 *
	 * @param string   $id       Label ID.
	 * @param callable $callback Provider.
	 * @param int      $priority Priority.
	 */
	private function add( string $id, callable $callback, int $priority = 10 ): void {
		Registry::instance()->add(
			$id,
			array(
				'callback'           => $callback,
				'default_post_types' => array( 'post' ),
				'priority'           => $priority,
			)
		);
	}

	/**
	 * Capture the action output.
	 */
	private function output(): string {
		ob_start();
		do_action( 'hyperpress_labels' );
		return (string) ob_get_clean();
	}

	public function test_labels_follow_registry_order_and_are_filtered(): void {
		$this->add( 'b', self::named( 'Second' ), 20 );
		$this->add( 'a', self::named( 'First' ), 10 );
		add_filter(
			'hyperpress_labels_content',
			static function ( array $labels ): array {
				$labels[] = array( 'label' => 'Extra' );
				return $labels;
			}
		);

		$this->assertSame( array( 'First', 'Second', 'Extra' ), array_column( Renderer::labels(), 'label' ) );
	}

	public function test_disabled_post_type_runs_no_callback(): void {
		$this->add( 'a', array( self::class, 'flagged' ) );
		$this->save( Resolver::enabled_key( 'post' ), false );

		$this->assertSame( array(), Renderer::labels() );
		$this->assertFalse( self::$ran );
		$this->assertSame( '', $this->output() );
	}

	public function test_unselected_label_is_skipped(): void {
		$this->add( 'a', self::named( 'A' ) );
		$this->add( 'b', array( self::class, 'flagged' ) );
		$this->save( Resolver::labels_key( 'post' ), array( 'a' ) );

		$this->assertSame( array( 'A' ), array_column( Renderer::labels(), 'label' ) );
		$this->assertFalse( self::$ran );
	}

	public function test_no_output_outside_the_loop(): void {
		$this->add( 'a', self::named( 'A' ) );
		$GLOBALS['post'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test setup.
		$this->go_to( '/' );
		$GLOBALS['post'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test setup.

		$this->assertFalse( get_post_type() );
		$this->assertSame( array(), Renderer::labels() );
		$this->assertSame( '', $this->output() );
	}

	public function test_works_after_go_to_without_the_post(): void {
		$this->add( 'a', self::named( 'A' ) );
		$GLOBALS['post'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test setup.
		$this->go_to( get_permalink( $this->post_id ) );
		$GLOBALS['post'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test setup.

		$this->assertSame( array( 'A' ), array_column( Renderer::labels(), 'label' ) );
	}

	public function test_non_array_callback_results_and_entries_are_ignored(): void {
		$this->add( 'a', array( self::class, 'not_an_array' ) );
		$this->add( 'b', array( self::class, 'mixed_entries' ), 20 );

		$labels = Renderer::labels();

		$this->assertCount( 1, $labels );
		$this->assertSame( 'X', $labels[0]['label'] );
		$this->assertStringContainsString( 'X', $this->output() );
	}

	public function test_missing_keys_are_filled(): void {
		$this->add( 'a', self::named( 'X' ) );

		$labels = Renderer::labels();

		$this->assertSame( array(), $labels[0]['classes'] );
		$this->assertSame( '', $labels[0]['title'] );
		$this->assertSame( '', $labels[0]['url'] );
		$this->assertSame( '', $labels[0]['rel'] );
		$this->assertStringContainsString( '>', $this->output() );
	}

	public function test_entries_added_by_the_content_filter_are_normalized(): void {
		$this->add( 'a', self::named( 'A' ) );
		add_filter(
			'hyperpress_labels_content',
			static function ( array $labels ): array {
				$labels[] = array( 'label' => 'Only label' );
				$labels[] = array(
					'label'   => 'String class',
					'classes' => 'primary',
				);
				$labels[] = array(
					'label'   => 'Junk class',
					'classes' => new \stdClass(),
				);
				$labels[] = 'not an entry';
				return $labels;
			}
		);

		$labels = Renderer::labels();

		$this->assertSame( array( 'A', 'Only label', 'String class', 'Junk class' ), array_column( $labels, 'label' ) );
		$this->assertSame(
			array(
				'label'   => 'Only label',
				'title'   => '',
				'classes' => array(),
				'url'     => '',
				'rel'     => '',
			),
			$labels[1]
		);
		$this->assertSame( array( 'primary' ), $labels[2]['classes'] );
		$this->assertSame( array(), $labels[3]['classes'] );
		$html = $this->output();
		$this->assertStringContainsString( 'label primary', $html );
		$this->assertStringContainsString( 'Only label', $html );
	}

	public function test_non_scalar_filter_fields_are_coerced_to_empty_strings(): void {
		$this->add( 'a', self::named( 'A' ) );
		add_filter(
			'hyperpress_labels_content',
			static function (): array {
				return array(
					array(
						'label' => array( 'x' ),
						'title' => new \stdClass(),
						'url'   => null,
						'rel'   => array(),
					),
					array(
						'label' => 5,
						'title' => 1.5,
						'url'   => true,
						'rel'   => 'nofollow',
					),
				);
			}
		);

		$labels = Renderer::labels();

		$this->assertSame( '', $labels[0]['label'] );
		$this->assertSame( '', $labels[0]['title'] );
		$this->assertSame( '', $labels[0]['url'] );
		$this->assertSame( '', $labels[0]['rel'] );
		$this->assertSame( '5', $labels[1]['label'] );
		$this->assertSame( '1.5', $labels[1]['title'] );
		$this->assertSame( '1', $labels[1]['url'] );
		$this->assertSame( 'nofollow', $labels[1]['rel'] );

		// Rendering must not raise warnings or TypeErrors (the test fails on any PHP notice).
		$html = $this->output();
		$this->assertSame( 2, substr_count( $html, '<div class="cell shrink label ' ) );
		$this->assertSame( 1, substr_count( $html, '<span title="">' ) );
		$this->assertMatchesRegularExpression( '/<span title="1\.5">\s*5\s*<\/span>/', $html );
		$this->assertStringContainsString( 'rel="nofollow"', $html );
		$this->assertStringNotContainsString( 'Array', $html );
		$this->assertStringNotContainsString( 'stdClass', $html );
	}

	public function test_markup_matches_the_old_template(): void {
		$this->add( 'a', array( self::class, 'linked' ) );

		$html = $this->output();

		$this->assertStringContainsString( '<div class="entry-meta">', $html );
		$this->assertStringContainsString( '<div class="cell shrink label primary">', $html );
		$this->assertStringContainsString( '<a href="https://example.org/x" rel="nofollow">', $html );
		$this->assertStringContainsString( '<span title="Tip">', $html );
	}

	public function test_theme_template_override_wins(): void {
		$this->add( 'a', self::named( 'A' ) );
		$dir = get_stylesheet_directory() . '/hyperpress-label';
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true );
		}
		$this->override = $dir . '/labels.php';
		file_put_contents( $this->override, '<?php echo "OVERRIDE-MARKER";' );

		$this->assertStringContainsString( 'OVERRIDE-MARKER', $this->output() );
	}
}
