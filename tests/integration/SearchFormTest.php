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
}
