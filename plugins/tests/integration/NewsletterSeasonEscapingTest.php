<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class NewsletterSeasonEscapingTest extends WP_UnitTestCase {

	public function test_season_name_is_escaped_in_the_newsletter_list_heading(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$term    = wp_insert_term( 'placeholder', 'hyper_season' );
		$this->assertIsArray( $term );
		// Term names are sanitized on save; write the markup straight to the row to test the output side only.
		global $wpdb;
		$wpdb->update( $wpdb->terms, array( 'name' => '<b>2026</b>' ), array( 'term_id' => $term['term_id'] ) );
		clean_term_cache( $term['term_id'], 'hyper_season' );
		wp_set_object_terms( $post_id, array( (int) $term['term_id'] ), 'hyper_season' );

		// A list context (not singular, not a season archive) is where the season name is printed.
		$this->go_to( get_post_type_archive_link( 'hyper_newsletter' ) );
		$this->assertTrue( have_posts() );
		the_post();

		ob_start();
		include dirname( __DIR__, 2 ) . '/hyperpress-newsletter/templates/content.php';
		$html = ob_get_clean();

		$this->assertStringContainsString( '&lt;b&gt;2026&lt;/b&gt;: ', $html );
		$this->assertStringNotContainsString( '<b>2026</b>', $html );
	}
}
