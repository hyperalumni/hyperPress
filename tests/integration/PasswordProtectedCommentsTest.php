<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class PasswordProtectedCommentsTest extends WP_UnitTestCase {

	/** Set up the main request for a post and include the theme's comments.php the way comments_template() would. */
	private function render_comments( int $post_id ): string {
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( have_posts() );
		the_post();
		// comments_template() loads the comments into the main query before including the template.
		$GLOBALS['wp_query']->comments      = get_comments(
			array(
				'post_id' => $post_id,
				'status'  => 'approve',
			)
		);
		$GLOBALS['wp_query']->comment_count = count( $GLOBALS['wp_query']->comments );
		ob_start();
		include dirname( __DIR__, 2 ) . '/comments.php';
		return (string) ob_get_clean();
	}

	public function test_comments_of_a_password_protected_post_are_not_listed(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_status'   => 'publish',
				'post_password' => 'secret',
			)
		);
		self::factory()->comment->create(
			array(
				'comment_post_ID'  => $post_id,
				'comment_content'  => 'Hidden behind the password',
				'comment_approved' => '1',
			)
		);

		$html = $this->render_comments( $post_id );

		$this->assertStringNotContainsString( 'Hidden behind the password', $html );
		$this->assertStringContainsString( 'password protected', $html );
	}
}
