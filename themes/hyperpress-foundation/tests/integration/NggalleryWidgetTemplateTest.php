<?php
namespace HyperPress\ThemeTests\Integration;

use Imagely\NGG\DisplayType\Controller;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

require_once __DIR__ . '/stubs/nextgen.php';

final class NggalleryWidgetTemplateTest extends WP_UnitTestCase {

	private const TEMPLATE = 'Widget/Display/Gallery.php';

	private string $default_effect_code;

	public function set_up(): void {
		parent::set_up();
		$this->default_effect_code = Controller::$effect_code;
	}

	public function tear_down(): void {
		Controller::$effect_code = $this->default_effect_code;
		remove_all_filters( 'ngg_gallery_image_attributes' );
		parent::tear_down();
	}

	private function render( array $images, array $settings = array() ): string {
		$path    = dirname( __DIR__, 2 ) . '/nggallery/' . self::TEMPLATE;
		$entity  = (object) array( 'display_settings' => array_merge( $this->settings(), $settings ) );
		$gallery = (object) array(
			'displayed_gallery' => new class( $entity ) {
				public function __construct( private object $entity ) {}

				public function get_entity(): object {
					return $this->entity;
				}
			},
		);
		ob_start();
		( static function ( string $template_file, object $gallery, array $images ): void {
			include $template_file;
		} )( $path, $gallery, $images );
		return (string) ob_get_clean();
	}

	private function settings(): array {
		return array(
			'widget_setting_before_widget' => '<section id="ngg-images-2" class="widget ngg_images">',
			'widget_setting_before_title'  => '<h4>',
			'widget_setting_title'         => 'Random Photos',
			'widget_setting_after_title'   => '</h4>',
			'widget_setting_after_widget'  => '</section>',
			'image_type'                   => 'thumb',
			'image_width'                  => 150,
			'image_height'                 => 120,
		);
	}

	private function image( array $over = array() ): object {
		return (object) array_merge(
			array(
				'pid'         => 5,
				'description' => 'A caption',
				'alttext'     => 'Alt <b>text</b>',
				'meta_data'   => array(),
				'hidden'      => false,
			),
			$over
		);
	}

	/** Returns the attributes of the first tag with the given name as name => value. */
	private function attributes_of( string $html, string $tag ): array {
		$processor = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $processor->next_tag( $tag ), "no <{$tag}> in: {$html}" );
		$attributes = array();
		foreach ( (array) $processor->get_attribute_names_with_prefix( '' ) as $name ) {
			$attributes[ $name ] = $processor->get_attribute( $name );
		}
		return $attributes;
	}

	public function test_prints_the_widget_wrappers_and_title(): void {
		$html = $this->render( array( $this->image() ) );

		$this->assertStringStartsWith( '<section id="ngg-images-2" class="widget ngg_images"><h4>Random Photos</h4>', trim( $html ) );
		$this->assertStringEndsWith( '</section>', trim( $html ) );
	}

	public function test_widget_wrappers_and_title_pass_through_kses(): void {
		$html = $this->render(
			array( $this->image() ),
			array(
				'widget_setting_before_widget' => '<section class="widget"><script>alert(1)</script>',
				'widget_setting_title'         => 'Photos <script>alert(2)</script>',
			)
		);

		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringContainsString( 'Photos', $html );
	}

	public function test_images_sit_in_a_compact_foundation_grid(): void {
		$html = $this->render( array( $this->image(), $this->image( array( 'pid' => 6 ) ) ) );

		$this->assertStringContainsString( '<div class="grid-x grid-margin-x small-up-2 medium-up-2">', $html );
		$this->assertSame( 2, substr_count( $html, '<div class="cell">' ) );
		$this->assertStringNotContainsString( 'ngg-widget', $html );
	}

	public function test_cells_have_no_per_image_id(): void {
		$html = $this->render( array( $this->image() ) );

		$this->assertStringNotContainsString( ' id="ngg-image-', $html );
		$this->assertStringNotContainsString( '<div class="cell" id=', $html );
	}

	public function test_link_merges_the_thumbnail_and_lightbox_classes_into_one_class_attribute(): void {
		$html = $this->render( array( $this->image() ) );

		$this->assertMatchesRegularExpression( '/<div class="cell">\s*<a /', $html );
		$link = substr( $html, (int) strpos( $html, '<a ' ), (int) strpos( $html, '>', (int) strpos( $html, '<a ' ) ) - (int) strpos( $html, '<a ' ) + 1 );
		$this->assertSame( 1, substr_count( $link, 'class=' ), $link );
		$attributes = $this->attributes_of( $link, 'a' );
		$this->assertSame( 'ngg-simplelightbox thumbnail', $attributes['class'] );
		$this->assertSame( 'widget-1', $attributes['rel'] );
	}

	public function test_link_without_effect_code_class_gets_only_thumbnail(): void {
		Controller::$effect_code = 'rel="nofollow"';

		$html       = $this->render( array( $this->image() ) );
		$attributes = $this->attributes_of( $html, 'a' );

		$this->assertSame( 'thumbnail', $attributes['class'] );
		$this->assertSame( 'nofollow', $attributes['rel'] );
	}

	public function test_link_with_empty_effect_code_still_gets_thumbnail(): void {
		Controller::$effect_code = '';

		$attributes = $this->attributes_of( $this->render( array( $this->image() ) ), 'a' );

		$this->assertSame( 'thumbnail', $attributes['class'] );
	}

	public function test_link_points_to_the_full_image_with_title_and_image_id(): void {
		$attributes = $this->attributes_of( $this->render( array( $this->image( array( 'pid' => 9 ) ) ) ), 'a' );

		$this->assertSame( 'https://example.test/full-9.jpg', $attributes['href'] );
		$this->assertSame( 'A caption', $attributes['title'] );
		$this->assertSame( '9', $attributes['data-image-id'] );
	}

	public function test_video_and_tiktok_attributes_only_when_present(): void {
		$plain = $this->attributes_of( $this->render( array( $this->image() ) ), 'a' );
		foreach ( array( 'data-video-url', 'data-tiktok-play-url', 'data-tiktok-share-url', 'data-tiktok-embed-url' ) as $name ) {
			$this->assertArrayNotHasKey( $name, $plain, $name );
		}

		$image      = $this->image(
			array(
				'meta_data' => array(
					'imagely_tiktok_play_url'   => 'https://t.example/play?a=1&b=2',
					'imagely_tiktok_share_url'  => 'https://t.example/share',
					'imagely_tiktok_embed_link' => 'https://t.example/embed',
					'video_link'                => 'https://v.example/clip',
				),
			)
		);
		$attributes = $this->attributes_of( $this->render( array( $image ) ), 'a' );

		$this->assertSame( 'https://t.example/play?a=1&b=2', $attributes['data-tiktok-play-url'] );
		$this->assertSame( 'https://t.example/share', $attributes['data-tiktok-share-url'] );
		$this->assertSame( 'https://t.example/embed', $attributes['data-tiktok-embed-url'] );
		$this->assertSame( 'https://v.example/clip', $attributes['data-video-url'] );
	}

	public function test_image_is_lazy_with_plain_text_alt_cache_busted_src_and_widget_size(): void {
		$html       = $this->render( array( $this->image( array( 'pid' => 7 ) ) ) );
		$attributes = $this->attributes_of( $html, 'img' );

		$this->assertSame( 'lazy', $attributes['loading'] );
		$this->assertSame( 'ngg-gallery-thumbnail-img', $attributes['class'] );
		$this->assertSame( 'Alt text', $attributes['alt'] );
		$this->assertSame( 'https://example.test/thumb-7.jpg?v=1', $attributes['src'] );
		$this->assertSame( '150', $attributes['width'] );
		$this->assertSame( '120', $attributes['height'] );
	}

	public function test_description_and_alt_are_escaped(): void {
		$image = $this->image(
			array(
				'description' => 'Say "hi" <script>x</script>',
				'alttext'     => 'Tom & "Jerry"',
			)
		);
		$html  = $this->render( array( $image ) );

		$this->assertStringNotContainsString( '<script>x', $html );
		$this->assertStringContainsString( 'title="Say &quot;hi&quot; &lt;script&gt;x&lt;/script&gt;"', $html );
		$this->assertSame( 'Tom & "Jerry"', $this->attributes_of( $html, 'img' )['alt'] );
		$this->assertStringContainsString( 'alt="Tom &amp; &quot;Jerry&quot;"', $html );
	}

	public function test_hidden_images_are_skipped(): void {
		$html = $this->render( array( $this->image( array( 'pid' => 1 ) ), $this->image( array( 'pid' => 2, 'hidden' => true ) ) ) );

		$this->assertStringContainsString( 'full-1.jpg', $html );
		$this->assertStringNotContainsString( 'full-2.jpg', $html );
		$this->assertSame( 1, substr_count( $html, '<div class="cell">' ) );
	}

	public function test_renders_nothing_at_all_without_images(): void {
		$this->assertSame( '', trim( $this->render( array() ) ) );
	}

	public function test_renders_nothing_at_all_when_every_image_is_hidden(): void {
		$this->assertSame( '', trim( $this->render( array( $this->image( array( 'hidden' => true ) ) ) ) ) );
	}
}
