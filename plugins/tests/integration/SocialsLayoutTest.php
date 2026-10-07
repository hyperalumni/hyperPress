<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Social\Widgets\DisplaySocialWidget;
use WP_UnitTestCase;

final class SocialsLayoutTest extends WP_UnitTestCase {

	private const INLINE = '<ul class="menu simple">'
		. '<li><a href="https://github.com/x" title="GitHub" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-github fa-inverse" aria-hidden="true"></i></a></li>'
		. '<li><a href="https://facebook.com/x" title="Facebook" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-facebook-f fa-inverse" aria-hidden="true"></i></a></li>'
		. '<li><a href="/about/" title="Instagram" target="_self"><i class="fa-brands fa-instagram fa-inverse" aria-hidden="true"></i></a></li>'
		. '</ul>';

	private const GRID_WRAPPER = 'grid-x grid-padding-x xsmall-up-3 small-up-4 medium-up-2 large-up-3 xlarge-up-4';

	private const GRID = '<div class="' . self::GRID_WRAPPER . '">'
		. '<div class="cell"><a href="https://github.com/x" title="GitHub" target="_blank" rel="noopener noreferrer"><span class="fa-stack fa-2x"><i class="fa-solid fa-square fa-stack-2x"></i><i class="fa-brands fa-github fa-stack-1x fa-inverse"></i></span></a></div>'
		. '<div class="cell"><a href="https://facebook.com/x" title="Facebook" target="_blank" rel="noopener noreferrer"><span class="fa-stack fa-2x"><i class="fa-solid fa-square fa-stack-2x"></i><i class="fa-brands fa-facebook-f fa-stack-1x fa-inverse"></i></span></a></div>'
		. '<div class="cell"><a href="/about/" title="Instagram" target="_self"><span class="fa-stack fa-2x"><i class="fa-solid fa-square fa-stack-2x"></i><i class="fa-brands fa-instagram fa-stack-1x fa-inverse"></i></span></a></div>'
		. '</div>';

	public function tear_down(): void {
		foreach ( array( 'github', 'facebook', 'instagram' ) as $key ) {
			remove_theme_mod( 'hyperpress_social_' . $key );
		}
		parent::tear_down();
	}

	private function set_three_links(): void {
		set_theme_mod( 'hyperpress_social_github', 'https://github.com/x' );
		set_theme_mod( 'hyperpress_social_facebook', 'https://facebook.com/x' );
		set_theme_mod( 'hyperpress_social_instagram', '/about/' );
	}

	/** @param array<string, mixed> $instance */
	private function render( array $instance, string $before_title = '', string $after_title = '' ): string {
		ob_start();
		the_widget(
			DisplaySocialWidget::class,
			$instance,
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => $before_title,
				'after_title'   => $after_title,
			)
		);
		return (string) ob_get_clean();
	}

	/** @return array<string, array{0: array<string, mixed>}> */
	public static function grid_instances(): array {
		return array(
			'layout missing'  => array( array( 'title' => '' ) ),
			'layout grid'     => array( array( 'title' => '', 'layout' => 'grid' ) ),
			'layout garbage'  => array( array( 'title' => '', 'layout' => 'sideways' ) ),
			'layout uppercase' => array( array( 'title' => '', 'layout' => 'INLINE' ) ),
			'layout array'    => array( array( 'title' => '', 'layout' => array( 'inline' ) ) ),
		);
	}

	/**
	 * @dataProvider grid_instances
	 * @param array<string, mixed> $instance
	 */
	public function test_anything_but_inline_renders_the_unchanged_grid( array $instance ): void {
		$this->set_three_links();

		$this->assertSame( self::GRID, $this->render( $instance ) );
	}

	/**
	 * @dataProvider grid_instances
	 * @param array<string, mixed> $instance
	 */
	public function test_the_grid_with_no_links_prints_its_empty_wrapper( array $instance ): void {
		$this->assertSame( '<div class="' . self::GRID_WRAPPER . '"></div>', $this->render( $instance ) );
	}

	public function test_the_inline_layout_matches_the_footer_markup_exactly(): void {
		$this->set_three_links();

		$this->assertSame( self::INLINE, $this->render( array( 'title' => '', 'layout' => 'inline' ) ) );
	}

	public function test_the_inline_layout_puts_rel_only_on_blank_links(): void {
		$this->set_three_links();

		$html = $this->render( array( 'title' => '', 'layout' => 'inline' ) );

		$this->assertSame( 2, substr_count( $html, ' rel="noopener noreferrer"' ) );
		$this->assertStringContainsString( 'title="Instagram" target="_self"><i', $html );
	}

	public function test_the_inline_layout_with_no_links_prints_the_empty_list(): void {
		$this->assertSame( '<ul class="menu simple"></ul>', $this->render( array( 'title' => '', 'layout' => 'inline' ) ) );
	}

	public function test_the_inline_layout_prints_no_title_when_the_title_is_empty(): void {
		$html = $this->render( DisplaySocialWidget::default_instance(), '<h2>', '</h2>' );

		$this->assertSame( '<ul class="menu simple"></ul>', $html );
	}

	public function test_a_non_empty_title_is_still_printed_around_the_inline_list(): void {
		$html = $this->render( array( 'title' => 'Follow', 'layout' => 'inline' ), '<h2>', '</h2>' );

		$this->assertSame( '<h2>Follow</h2><ul class="menu simple"></ul>', $html );
	}

	public function test_the_widget_wrappers_surround_the_inline_list(): void {
		ob_start();
		the_widget(
			DisplaySocialWidget::class,
			array( 'title' => '', 'layout' => 'inline' ),
			array(
				'before_widget' => '<aside>',
				'after_widget'  => '</aside>',
				'before_title'  => '',
				'after_title'   => '',
			)
		);

		$this->assertSame( '<aside><ul class="menu simple"></ul></aside>', (string) ob_get_clean() );
	}

	/** @return array<string, array{0: array<string, mixed>, 1: string}> */
	public static function layouts_to_save(): array {
		return array(
			'inline kept'        => array( array( 'layout' => 'inline' ), 'inline' ),
			'grid kept'          => array( array( 'layout' => 'grid' ), 'grid' ),
			'uppercase INLINE'   => array( array( 'layout' => 'INLINE' ), 'grid' ),
			'empty string'       => array( array( 'layout' => '' ), 'grid' ),
			'array value'        => array( array( 'layout' => array() ), 'grid' ),
			'null value'         => array( array( 'layout' => null ), 'grid' ),
			'garbage'            => array( array( 'layout' => 'x' ), 'grid' ),
			'no layout submitted' => array( array( 'x' => 'grid' ), 'grid' ),
		);
	}

	/**
	 * @dataProvider layouts_to_save
	 * @param array<string, mixed> $submitted
	 */
	public function test_update_stores_inline_only_for_the_exact_value( array $submitted, string $expected ): void {
		$widget = new DisplaySocialWidget();

		$this->assertSame(
			array( 'title' => 'Hi', 'layout' => $expected ),
			$widget->update( array_merge( array( 'title' => 'Hi' ), $submitted ), array() )
		);
	}

	private function form( array $instance ): string {
		$widget = new DisplaySocialWidget();
		$widget->_set( 1 );

		ob_start();
		$widget->form( $instance );
		return (string) ob_get_clean();
	}

	public function test_the_form_has_a_layout_select_with_both_options(): void {
		$html = $this->form( array( 'title' => 'x', 'layout' => 'grid' ) );

		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( 'name="widget-hyperpress_social[1][layout]"', $html );
		$this->assertMatchesRegularExpression( '#<option value="grid"[^>]*>Grid \(large icons\)</option>#', $html );
		$this->assertMatchesRegularExpression( '#<option value="inline"[^>]*>Inline list \(small icons\)</option>#', $html );
	}

	public function test_the_form_selects_the_saved_inline_layout(): void {
		$html = $this->form( array( 'title' => 'x', 'layout' => 'inline' ) );

		$this->assertMatchesRegularExpression( '#<option value="inline" selected=\'selected\'>#', $html );
		$this->assertDoesNotMatchRegularExpression( '#<option value="grid" selected#', $html );
	}

	/** @return array<string, array{0: array<string, mixed>}> */
	public static function grid_selected_instances(): array {
		return array(
			'grid'    => array( array( 'layout' => 'grid' ) ),
			'missing' => array( array() ),
			'garbage' => array( array( 'layout' => 'x' ) ),
		);
	}

	/**
	 * @dataProvider grid_selected_instances
	 * @param array<string, mixed> $instance
	 */
	public function test_the_form_selects_grid_by_default( array $instance ): void {
		$html = $this->form( $instance );

		$this->assertMatchesRegularExpression( '#<option value="grid" selected=\'selected\'>#', $html );
		$this->assertDoesNotMatchRegularExpression( '#<option value="inline" selected#', $html );
	}

	public function test_default_instance_is_an_untitled_inline_widget(): void {
		$this->assertSame( array( 'title' => '', 'layout' => 'inline' ), DisplaySocialWidget::default_instance() );
	}
}
