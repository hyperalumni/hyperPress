<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Label\Registry;
use HyperPress\Label\Renderer;
use WP_UnitTestCase;

final class NewsletterLabelsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Registry::reset();
		Renderer::register();
	}

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_labels_content' );
		Registry::reset( true );
		parent::tear_down();
	}

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

		$labels = Renderer::labels();

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

		$labels = Renderer::labels();
		$season = array_values( array_filter( $labels, static fn ( $label ) => 'Posted During' === $label['title'] ) );

		$this->assertCount( 1, $season );
		$this->assertSame( get_term_link( get_term( $term_id, 'hyper_season' ) ), $season[0]['url'] );
	}

	public function test_newsletter_gets_date_author_season_comments_in_order(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026' ) );
		wp_set_object_terms( $post_id, array( $term_id ), 'hyper_season' );
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		$this->assertSame(
			array( 'Posted At', 'Posted By', 'Posted During', '' ),
			array_column( Renderer::labels(), 'title' )
		);
	}

	public function test_newsletter_has_no_category_label(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		$this->assertNotContains( 'Posted In', array_column( Renderer::labels(), 'title' ) );
		$this->assertNotContains( 'hyper_newsletter', Registry::instance()->ids() );
		$this->assertNotContains( 'hyper_newsletter', Registry::instance()->default_post_types( 'post.category' ) );
	}

	public function test_filter_adds_newsletter_only_for_the_four_shared_labels(): void {
		$registry = Registry::instance();
		foreach ( array( 'post.date', 'post.author', 'post.comments', 'season.posted_during' ) as $id ) {
			$this->assertContains( 'hyper_newsletter', $registry->default_post_types( $id ), $id );
		}
		$this->assertNotContains( 'hyper_newsletter', $registry->default_post_types( 'post.category' ) );
		$this->assertSame( array( 'x', 'hyper_newsletter' ), \HyperPress\Newsletter\Labels::default_post_types( array( 'x' ), 'post.date' ) );
		$this->assertSame( array( 'hyper_newsletter' ), \HyperPress\Newsletter\Labels::default_post_types( array( 'hyper_newsletter' ), 'post.date' ) );
		$this->assertSame( array( 'hyper_newsletter' ), \HyperPress\Newsletter\Labels::default_post_types( 'bad', 'post.date' ) );
		$this->assertSame( array( 'x' ), \HyperPress\Newsletter\Labels::default_post_types( array( 'x' ), 'sponsor.tier' ) );
		$this->assertFalse( has_filter( 'hyperpress_labels_content', array( \HyperPress\Newsletter\Labels::class, 'labels_newsletter' ) ) );
	}
}
