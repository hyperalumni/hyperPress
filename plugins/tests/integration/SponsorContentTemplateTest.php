<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SponsorContentTemplateTest extends WP_UnitTestCase {
	public function test_single_sponsor_supplies_a_readable_content_template(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_sponsor' ) );
		$this->go_to( get_permalink( $post_id ) );
		$path = apply_filters( 'content_template', '' );
		$this->assertNotSame( '', $path );
		$this->assertFileIsReadable( $path );
	}

	public static function website_links(): array {
		$cases = array(
			'external'     => array( 'https://external.example/sponsor/', 'target="_blank" rel="noopener noreferrer"' ),
			'internal'     => array( home_url( '/x/' ), 'target="_self">' ),
			'internal pdf' => array( home_url( '/files/info.pdf' ), 'target="_blank" rel="noopener noreferrer"' ),
		);

		$shapes = array();
		foreach ( $cases as $name => list( $url, $expected ) ) {
			$shapes[ "$name, legacy string" ] = array( $url, $expected );
			$shapes[ "$name, link array" ]    = array(
				array(
					'url'    => $url,
					'title'  => 'Sponsor site',
					'target' => '',
				),
				$expected,
			);
		}

		return $shapes;
	}

	/**
	 * @dataProvider website_links
	 * @param string|array<string,string> $value Stored website value.
	 */
	public function test_website_link_opens_according_to_its_url( $value, string $expected ): void {
		$html = $this->render_sponsor( array( 'hyperpress_sponsor_website' => $value ), true );

		$this->assertStringContainsString( $expected, $html );
		if ( str_contains( $expected, '_self' ) ) {
			$this->assertStringNotContainsString( 'rel="noopener', $html );
		}
	}

	public function test_link_array_with_a_title_uses_it_as_the_button_text(): void {
		$html = $this->render_sponsor(
			array(
				'hyperpress_sponsor_website' => array(
					'url'   => 'https://external.example/sponsor/',
					'title' => 'Meet Acme <b>Corp</b>',
				),
			),
			true
		);

		$this->assertStringContainsString( '>Meet Acme &lt;b&gt;Corp&lt;/b&gt;</a>', $html );
		$this->assertStringNotContainsString( 'Visit our sponsor', $html );
	}

	public function test_link_array_without_a_title_uses_the_default_button_text(): void {
		foreach ( array( '', 'https://external.example/sponsor/' ) as $title ) {
			$html = $this->render_sponsor(
				array(
					'hyperpress_sponsor_website' => array(
						'url'   => 'https://external.example/sponsor/',
						'title' => $title,
					),
				),
				true
			);

			$this->assertStringContainsString( '>Visit our sponsor</a>', $html );
		}
	}

	public function test_legacy_plain_url_renders_a_button_with_the_default_text(): void {
		$html = $this->render_sponsor( array( 'hyperpress_sponsor_website' => 'https://external.example/sponsor/' ), true );

		$this->assertStringContainsString( 'href="https://external.example/sponsor/"', $html );
		$this->assertStringContainsString( '>Visit our sponsor</a>', $html );
	}

	public function test_external_link_opens_in_a_new_tab_even_when_its_array_target_is_empty(): void {
		$html = $this->render_sponsor(
			array(
				'hyperpress_sponsor_website' => array(
					'url'    => 'https://external.example/sponsor/',
					'target' => '',
				),
			),
			true
		);

		$this->assertStringContainsString( 'target="_blank" rel="noopener noreferrer"', $html );
	}

	public function test_unsafe_or_empty_website_renders_no_button(): void {
		$values = array(
			'unsafe array'  => array(
				'url'   => 'javascript:alert(1)',
				'title' => 'Click',
			),
			'empty array'   => array(),
			'blank url'     => array(
				'url'   => '',
				'title' => 'Click',
			),
			'unsafe string' => 'javascript:alert(1)',
		);
		foreach ( $values as $value ) {
			$html = $this->render_sponsor( array( 'hyperpress_sponsor_website' => $value ), true );

			$this->assertStringNotContainsString( 'class="button', $html );
		}
	}

	private function render_sponsor( array $meta, bool $single, bool $with_thumbnail = false ): string {
		global $post;

		$post_id = self::factory()->post->create(
			array(
				'post_type'  => 'hyper_sponsor',
				'meta_input' => $meta,
			)
		);
		if ( $with_thumbnail ) {
			$attachment_id = self::factory()->attachment->create(
				array(
					'post_mime_type' => 'image/jpeg',
					'post_parent'    => $post_id,
					'file'           => 'sponsor.jpg',
				)
			);
			set_post_thumbnail( $post_id, $attachment_id );
		}

		if ( $single ) {
			$this->go_to( get_permalink( $post_id ) );
			the_post();
		} else {
			$this->go_to( home_url( '/' ) );
			$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );
		}

		ob_start();
		include dirname( __DIR__, 2 ) . '/hyperpress-sponsor/templates/content.php';
		return (string) ob_get_clean();
	}

	public function test_single_page_shows_the_full_description_without_the_more_marker(): void {
		$html = $this->render_sponsor( array( 'hyperpress_sponsor_description' => "Before the marker.\n<!--more-->\nAfter the marker." ), true );

		$this->assertStringContainsString( 'sponsor-description', $html );
		$this->assertStringContainsString( 'Before the marker.', $html );
		$this->assertStringContainsString( 'After the marker.', $html );
		$this->assertStringNotContainsString( '<!--more-->', $html );
		$this->assertStringNotContainsString( 'Read more', $html );
	}

	public function test_list_shows_only_text_before_the_marker_and_a_read_more_link(): void {
		$html = $this->render_sponsor( array( 'hyperpress_sponsor_description' => "Before the marker.\n<!--more-->\nAfter the marker." ), false );

		$this->assertStringContainsString( 'Before the marker.', $html );
		$this->assertStringNotContainsString( 'After the marker.', $html );
		$this->assertStringNotContainsString( '<!--more-->', $html );
		$this->assertStringContainsString( '<a class="button neutral small-down-expanded text-capitalize" href="' . esc_url( get_permalink() ) . '">Learn more about our sponsor</a>', $html );
		$this->assertStringNotContainsString( 'Read more', $html );
	}

	public function test_list_shows_no_learn_more_when_nothing_follows_the_marker(): void {
		$html = $this->render_sponsor( array( 'hyperpress_sponsor_description' => "Only this.\n<!--more-->" ), false );

		$this->assertStringContainsString( 'Only this.', $html );
		$this->assertStringNotContainsString( 'Learn more about our sponsor', $html );
		$this->assertStringNotContainsString( 'sponsor-links', $html );
	}

	public function test_learn_more_and_website_buttons_share_one_wrapper_in_that_order(): void {
		$html = $this->render_sponsor(
			array(
				'hyperpress_sponsor_description' => "Before.\n<!--more-->\nAfter.",
				'hyperpress_sponsor_website'     => 'https://external.example/sponsor/',
			),
			false
		);

		$this->assertSame( 1, substr_count( $html, 'sponsor-links' ) );
		$this->assertMatchesRegularExpression( '/<p class="sponsor-links">\s*<a class="button neutral[^"]*"[^>]*>Learn more about our sponsor<\/a>\s*<a class="button small-down-expanded[^"]*"[^>]*>Visit our sponsor<\/a>\s*<\/p>/', $html );
		$this->assertSame( 2, preg_match_all( '/<a class="button[^"]*\btext-capitalize\b[^"]*"/', $html ) );
		$this->assertMatchesRegularExpression( '/<div class="sponsor-description">\s*<p>Before\.<\/p>\s*<\/div>/', $html );
	}

	public function test_no_buttons_wrapper_renders_when_neither_button_applies(): void {
		$cases = array(
			'single with marker' => array( "Before.\n<!--more-->\nAfter.", true ),
			'list no marker'     => array( 'Whole description.', false ),
			'list empty after'   => array( "Only this.\n<!--more-->", false ),
		);
		foreach ( $cases as list( $description, $single ) ) {
			$html = $this->render_sponsor( array( 'hyperpress_sponsor_description' => $description ), $single );

			$this->assertStringNotContainsString( 'sponsor-links', $html );
			$this->assertStringNotContainsString( 'class="button', $html );
		}
	}

	public function test_description_without_marker_shows_whole_on_single_and_list_without_read_more(): void {
		foreach ( array( true, false ) as $single ) {
			$html = $this->render_sponsor( array( 'hyperpress_sponsor_description' => 'Whole description.' ), $single );

			$this->assertStringContainsString( 'Whole description.', $html );
			$this->assertStringNotContainsString( 'Read more', $html );
		}
	}

	public function test_no_description_renders_no_description_element(): void {
		foreach ( array( true, false ) as $single ) {
			$html = $this->render_sponsor( array(), $single );

			$this->assertStringNotContainsString( 'sponsor-description', $html );
		}
	}

	public function test_description_renders_after_the_image_and_before_the_website(): void {
		$html = $this->render_sponsor(
			array(
				'hyperpress_sponsor_description' => 'Ordered text.',
				'hyperpress_sponsor_website'     => 'https://external.example/sponsor/',
			),
			true,
			true
		);

		$image   = strpos( $html, '<img' );
		$text    = strpos( $html, 'Ordered text.' );
		$website = strpos( $html, 'Visit our sponsor' );
		$this->assertNotFalse( $image );
		$this->assertLessThan( $text, $image );
		$this->assertLessThan( $website, $text );
	}

	public function test_website_renders_as_a_button_link_with_a_visit_label(): void {
		$html = $this->render_sponsor( array( 'hyperpress_sponsor_website' => array( 'url' => 'https://external.example/sponsor/' ) ), true );

		$this->assertStringContainsString( '<a class="button small-down-expanded text-capitalize" href="https://external.example/sponsor/"', $html );
		$this->assertStringContainsString( '>Visit our sponsor</a>', $html );
		$this->assertStringNotContainsString( 'Website:', $html );
	}

	public function test_image_is_spaced_from_what_follows_with_a_foundation_margin_utility(): void {
		$descriptions = array(
			'with description'    => array( 'hyperpress_sponsor_description' => 'Spaced text.' ),
			'without description' => array(),
		);
		foreach ( $descriptions as $meta ) {
			$html = $this->render_sponsor( $meta, true, true );

			$this->assertMatchesRegularExpression( '/<div class="margin-bottom-2">\s*<img[^>]*wp-post-image/', $html );
			$this->assertStringNotContainsString( 'margin-top-1', $html );
		}
	}

	public function test_featured_image_has_no_thumbnail_class(): void {
		foreach ( array( true, false ) as $single ) {
			$html = $this->render_sponsor( array(), $single, true );

			$this->assertStringContainsString( '<img', $html );
			$this->assertDoesNotMatchRegularExpression( '/class="[^"]*(?<![-\w])thumbnail(?![-\w])/', $html );
		}
	}

	public function test_description_sanitizer_preserves_the_more_marker(): void {
		$sanitizer = null;
		foreach ( \HyperPress\Sponsor\MetaBoxes::register_sponsor_meta_boxes( array() )[0]['fields'] as $field ) {
			if ( 'hyperpress_sponsor_description' === $field['id'] ) {
				$sanitizer = $field['sanitize_callback'];
			}
		}
		$this->assertIsCallable( $sanitizer );

		$value = "Before.\n<!--more-->\nAfter.";
		$this->assertSame( $value, call_user_func( $sanitizer, $value ) );
	}
}
