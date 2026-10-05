<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class AuthorBannerTest extends WP_UnitTestCase {

	private function banner_for( array $user_args ): array {
		$user_id = self::factory()->user->create( $user_args );
		self::factory()->post->create( array( 'post_author' => $user_id ) );
		$this->go_to( get_author_posts_url( $user_id ) );
		$this->assertTrue( is_author() );

		return apply_filters( 'hyperpress_banner_content', array() );
	}

	public function test_title_has_prefix_and_full_name(): void {
		$banner = $this->banner_for(
			array(
				'first_name'   => 'Ada',
				'last_name'    => 'Lovelace',
				'display_name' => 'Countess',
			)
		);

		$this->assertSame( 'author', $banner['type'] );
		$this->assertSame( 'Published By Ada Lovelace', $banner['title'] );
	}

	public function test_title_falls_back_to_display_name_when_names_are_empty(): void {
		$banner = $this->banner_for(
			array(
				'first_name'    => '',
				'last_name'     => '',
				'display_name'  => 'Countess',
				'user_nicename' => 'countess',
			)
		);

		$this->assertSame( 'Published By Countess', $banner['title'] );
		$this->assertStringContainsString( 'countess', $banner['subtitle'] );
	}
}
