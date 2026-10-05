<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class NewsletterLabelsTest extends WP_UnitTestCase {

	/**
	 * @return array<string,array{0:int,1:string}>
	 */
	public static function comment_counts(): array {
		return array(
			'none'  => array( 0, '0 comments' ),
			'one'   => array( 1, '1 comment' ),
			'two'   => array( 2, '2 comments' ),
		);
	}

	/**
	 * @dataProvider comment_counts
	 */
	public function test_comment_label_pluralises_by_approved_comment_count( int $count, string $expected ): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		self::factory()->comment->create_many( $count, array( 'comment_post_ID' => $post_id, 'comment_approved' => 1 ) );
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		$labels = apply_filters( 'hyperpress_labels_content', array() );

		$this->assertContains( $expected, array_column( $labels, 'label' ) );
	}

	/**
	 * Characterization: the season label links to the term archive, as get_term_link() builds it.
	 */
	public function test_season_label_url_is_the_term_link(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026' ) );
		wp_set_object_terms( $post_id, array( $term_id ), 'hyper_season' );
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		$labels = apply_filters( 'hyperpress_labels_content', array() );
		$season = array_values( array_filter( $labels, static fn ( $label ) => 'Posted During' === $label['title'] ) );

		$this->assertCount( 1, $season );
		$this->assertSame( get_term_link( get_term( $term_id, 'hyper_season' ) ), $season[0]['url'] );
	}
}
