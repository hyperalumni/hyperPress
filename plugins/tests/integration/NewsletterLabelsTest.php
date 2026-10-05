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
}
