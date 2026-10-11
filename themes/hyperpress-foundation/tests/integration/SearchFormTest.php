<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class SearchFormTest extends WP_UnitTestCase {

	public function test_placeholder_has_no_surrounding_whitespace(): void {
		$html = get_search_form( array( 'echo' => false ) );

		$this->assertStringContainsString( 'placeholder="Search"', $html );
	}

	public function test_placeholder_is_escaped_for_attributes(): void {
		add_filter(
			'gettext_with_context',
			static function ( $translation, $text, $context, $domain ) {
				return ( 'hyperpress' === $domain && 'placeholder' === $context ) ? 'Find "it"' : $translation;
			},
			10,
			4
		);

		$html = get_search_form( array( 'echo' => false ) );

		$this->assertStringContainsString( 'placeholder="Find &quot;it&quot;"', $html );
	}

	public function test_the_form_has_no_fixed_ids_so_it_can_appear_twice(): void {
		$one = get_search_form( array( 'echo' => false ) );
		$two = get_search_form( array( 'echo' => false ) );

		$this->assertStringNotContainsString( ' id="', $one, 'no fixed ids: nothing in the theme styles or scripts them' );
		$this->assertStringContainsString( 'name="s"', $one );
		$this->assertStringContainsString( 'aria-label="Search"', $one, 'the field is still labeled for assistive technology' );

		preg_match_all( '/\sid="([^"]+)"/', $one . $two, $m );
		$this->assertSame( array_unique( $m[1] ), $m[1], 'no duplicate ids when the form is rendered twice' );
	}
}
