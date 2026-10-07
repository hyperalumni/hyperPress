<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Social\Icons;
use HyperPress\Social\Widgets\DisplaySocialWidget;
use ReflectionClass;
use WP_UnitTestCase;

final class SocialIconsTest extends WP_UnitTestCase {

	private const KEYS = array( 'firstinspires', 'thebluealliance', 'github' );

	/** @var array<int, string> Throwaway icon files to delete. */
	private array $temp_files = array();

	public function tear_down(): void {
		foreach ( $this->temp_files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
		foreach ( self::KEYS as $key ) {
			remove_theme_mod( 'hyperpress_social_' . $key );
		}
		parent::tear_down();
	}

	private function icons_dir(): string {
		return dirname( (string) ( new ReflectionClass( Icons::class ) )->getFileName(), 2 ) . '/assets/icons/';
	}

	private function write_temp_icon( string $name, string $contents ): void {
		$file               = $this->icons_dir() . $name . '.svg';
		$this->temp_files[] = $file;
		file_put_contents( $file, $contents );
	}

	private function render( string $layout ): string {
		ob_start();
		the_widget(
			DisplaySocialWidget::class,
			array( 'title' => '', 'layout' => $layout ),
			array( 'before_widget' => '', 'after_widget' => '', 'before_title' => '', 'after_title' => '' )
		);
		return (string) ob_get_clean();
	}

	private function set_networks(): void {
		foreach ( self::KEYS as $key ) {
			set_theme_mod( 'hyperpress_social_' . $key, 'https://example.test/' . $key );
		}
	}

	/** @return array<string, array{0: string}> */
	public static function shipped_icons(): array {
		return array(
			'firstinspires'   => array( 'firstinspires' ),
			'thebluealliance' => array( 'thebluealliance' ),
		);
	}

	/** @dataProvider shipped_icons */
	public function test_a_shipped_icon_is_sanitized_inline_markup_with_the_given_attributes( string $name ): void {
		$svg = Icons::svg( $name, 'svg-inline--fa fa-inverse' );

		$this->assertStringStartsWith( '<svg', $svg );
		$this->assertStringEndsWith( '</svg>', $svg );
		$this->assertStringContainsString( 'class="svg-inline--fa fa-inverse"', $svg );
		$this->assertStringContainsString( 'aria-hidden="true"', $svg );
		$this->assertStringContainsString( 'focusable="false"', $svg );
		$this->assertStringContainsString( 'fill="currentColor"', $svg );
		$this->assertStringContainsString( 'viewBox="', $svg );
		$this->assertStringContainsString( 'xmlns="http://www.w3.org/2000/svg"', $svg );
		$this->assertSame( 1, substr_count( $svg, '<svg' ) );
	}

	/** @dataProvider shipped_icons */
	public function test_a_shipped_icon_has_no_metadata_scripts_or_fixed_dimensions( string $name ): void {
		$svg = Icons::svg( $name );

		foreach ( array( '<script', 'onload', '<metadata', 'rdf', '<?xml', 'style=' ) as $forbidden ) {
			$this->assertStringNotContainsString( $forbidden, $svg );
		}
		$this->assertStringNotContainsString( 'class=', $svg );
		$this->assertDoesNotMatchRegularExpression( '/^<svg[^>]*\s(width|height)=/', $svg );
	}

	/** @dataProvider shipped_icons */
	public function test_a_shipped_icon_uses_only_the_current_color( string $name ): void {
		$svg = Icons::svg( $name );

		$this->assertDoesNotMatchRegularExpression( '/fill="(?!currentColor")/', $svg );
	}

	/** @dataProvider shipped_icons */
	public function test_the_icon_is_well_formed_xml( string $name ): void {
		$doc = new \DOMDocument();

		$this->assertTrue( $doc->loadXML( Icons::svg( $name, 'a b' ) ) );
	}

	public function test_classes_are_escaped(): void {
		$svg = Icons::svg( 'firstinspires', 'a" onclick="x' );

		$this->assertStringNotContainsString( 'onclick="x"', $svg );
		$this->assertStringContainsString( 'class="a&quot; onclick=&quot;x"', $svg );
	}

	public function test_no_classes_gives_no_class_attribute(): void {
		$this->assertStringNotContainsString( 'class=', Icons::svg( 'firstinspires' ) );
	}

	/** @return array<string, array{0: string}> */
	public static function invalid_names(): array {
		return array(
			'traversal' => array( '../x' ),
			'uppercase' => array( 'FIRST' ),
			'empty'     => array( '' ),
			'dot'       => array( 'a.b' ),
			'slash'     => array( 'a/b' ),
			'newline'   => array( "firstinspires\n" ),
		);
	}

	/** @dataProvider invalid_names */
	public function test_an_invalid_name_gives_nothing( string $name ): void {
		$this->assertSame( '', Icons::svg( $name, 'x' ) );
	}

	public function test_a_trailing_newline_is_rejected_even_when_the_file_exists(): void {
		$this->write_temp_icon( 'zz-newline-check', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2 2"><path d="M0 0h2v2z" fill="currentColor"/></svg>' );

		$this->assertSame( '', Icons::svg( "zz-newline-check\n" ) );
		$this->assertStringStartsWith( '<svg', Icons::svg( 'zz-newline-check' ) );

		// The name must fail validation itself, not merely miss a file: a name that passed would have been looked up and cached.
		$cache = ( new ReflectionClass( Icons::class ) )->getProperty( 'cache' );
		$cache->setAccessible( true );
		$this->assertArrayNotHasKey( "zz-newline-check\n", $cache->getValue() );
	}

	public function test_backreference_like_sequences_in_classes_appear_literally(): void {
		$svg = Icons::svg( 'firstinspires', 'a$1 b\\0 c${1}' );

		$this->assertStringContainsString( 'class="a$1 b\\0 c${1}"', $svg );
		$this->assertStringStartsWith( '<svg class="a$1 b\\0 c${1}" aria-hidden="true" focusable="false"', $svg );
	}

	public function test_fixed_dimensions_and_role_on_the_root_are_stripped(): void {
		$this->write_temp_icon( 'tmp-sized-icon', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2 2" width="20" height="20" role="img"><path d="M0 0h2v2z" fill="currentColor"/></svg>' );

		$svg = Icons::svg( 'tmp-sized-icon' );

		$this->assertStringStartsWith( '<svg', $svg );
		$this->assertDoesNotMatchRegularExpression( '/^<svg[^>]*\s(width|height|role)=/', $svg );
	}

	public function test_a_missing_file_gives_nothing_without_warnings(): void {
		$this->assertSame( '', Icons::svg( 'no-such-icon-file', 'x' ) );
	}

	public function test_scripts_and_handlers_are_stripped(): void {
		$this->write_temp_icon(
			'tmp-evil-icon',
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2 2" onload="alert(1)"><script>alert(2)</script>'
			. '<foreignObject><p onclick="x()">hi</p></foreignObject><path d="M0 0h2v2z" fill="currentColor" onmouseover="y()"/></svg>'
		);

		$svg = Icons::svg( 'tmp-evil-icon', 'c' );

		$this->assertStringStartsWith( '<svg', $svg );
		$this->assertStringContainsString( 'd="M0 0h2v2z"', $svg );
		foreach ( array( '<script', 'alert', 'onload', 'onclick', 'onmouseover', 'foreignObject', '<p>', '<p ' ) as $forbidden ) {
			$this->assertStringNotContainsString( $forbidden, $svg );
		}
	}

	public function test_a_file_that_is_not_an_svg_gives_nothing(): void {
		$this->write_temp_icon( 'tmp-not-svg', '<script>alert(1)</script>plain text' );

		$this->assertSame( '', Icons::svg( 'tmp-not-svg', 'x' ) );
	}

	public function test_the_grid_layout_renders_the_inline_svg_in_the_stack(): void {
		$this->set_networks();

		$html = $this->render( 'grid' );

		foreach ( array( 'firstinspires', 'thebluealliance' ) as $name ) {
			$this->assertStringContainsString(
				'<span class="fa-stack fa-2x"><i class="fa-solid fa-square fa-stack-2x"></i>'
				. Icons::svg( $name, 'svg-inline--fa fa-stack-1x fa-inverse' ) . '</span>',
				$html
			);
		}
		$this->assertStringContainsString( '<i class="fa-brands fa-github fa-stack-1x fa-inverse"></i>', $html );
		$this->assertStringNotContainsString( 'fa-robot', $html );
		$this->assertStringNotContainsString( 'fa-lightbulb', $html );
	}

	public function test_the_inline_layout_renders_the_inline_svg(): void {
		$this->set_networks();

		$html = $this->render( 'inline' );

		foreach ( array( 'firstinspires', 'thebluealliance' ) as $name ) {
			$this->assertStringContainsString(
				'target="_blank" rel="noopener noreferrer">' . Icons::svg( $name, 'svg-inline--fa fa-inverse' ) . '</a></li>',
				$html
			);
		}
		$this->assertStringContainsString( '<i class="fa-brands fa-github fa-inverse" aria-hidden="true"></i>', $html );
		$this->assertStringNotContainsString( '<i class="fa-solid fa-robot', $html );
		$this->assertStringNotContainsString( 'fa-lightbulb', $html );
	}

	public function test_the_svg_links_keep_their_titles_and_urls(): void {
		$this->set_networks();

		$html = $this->render( 'inline' );

		$this->assertStringContainsString( '<a href="https://example.test/firstinspires" title="FIRST Inspires"', $html );
		$this->assertStringContainsString( '<a href="https://example.test/thebluealliance" title="The Blue Alliance"', $html );
	}
}
