<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\Breadcrumbs;
use WP_UnitTestCase;

final class NextGenPathTest extends WP_UnitTestCase {

	public function test_a_path_without_the_slug_does_not_match(): void {
		$this->assertNull( Breadcrumbs::parse_path( '/photos/', 'nggallery' ) );
	}

	public function test_the_slug_without_a_trailing_slash_does_not_match(): void {
		$this->assertNull( Breadcrumbs::parse_path( '/photos/nggallery', 'nggallery' ) );
	}

	public function test_an_empty_slug_never_matches(): void {
		$this->assertNull( Breadcrumbs::parse_path( '/photos/nggallery/a/b', '' ) );
	}

	public function test_the_slug_alone_gives_empty_segments(): void {
		$this->assertSame( array( 'album' => '', 'gallery' => '' ), Breadcrumbs::parse_path( '/photos/nggallery/', 'nggallery' ) );
	}

	public function test_an_album_segment_is_returned(): void {
		$this->assertSame( array( 'album' => 'album', 'gallery' => '' ), Breadcrumbs::parse_path( '/photos/nggallery/album', 'nggallery' ) );
		$this->assertSame( array( 'album' => 'album', 'gallery' => '' ), Breadcrumbs::parse_path( '/photos/nggallery/album/', 'nggallery' ) );
	}

	public function test_album_and_gallery_segments_are_returned(): void {
		$this->assertSame( array( 'album' => 'missing', 'gallery' => 'gal' ), Breadcrumbs::parse_path( '/photos/nggallery/missing/gal/', 'nggallery' ) );
		$this->assertSame( array( 'album' => '5', 'gallery' => 'gal' ), Breadcrumbs::parse_path( '/photos/nggallery/5/gal', 'nggallery' ) );
	}

	public function test_regex_characters_in_the_slug_are_taken_literally(): void {
		$this->assertSame( array( 'album' => 'a', 'gallery' => 'b' ), Breadcrumbs::parse_path( '/photos/n.g+(x)#/a/b', 'n.g+(x)#' ) );
		$this->assertNull( Breadcrumbs::parse_path( '/photos/nXg+(x)#/a/b', 'n.g+(x)#' ) );
		$this->assertNull( Breadcrumbs::parse_path( '/photos/nggallery/a/b', '(' ) );
	}

	public function test_the_slug_must_be_a_whole_path_segment(): void {
		$this->assertNull( Breadcrumbs::parse_path( '/photos/notnggallery/a/b', 'nggallery' ) );
	}

	public function test_hostile_segments_come_out_as_sanitized_slugs(): void {
		$this->assertSame(
			array( 'album' => 'or-11', 'gallery' => 'x' ),
			Breadcrumbs::parse_path( '/photos/nggallery/%27%20OR%201%3D1/x%3Cscript%3E', 'nggallery' )
		);
	}

	public function test_plain_slugs_are_unchanged(): void {
		$this->assertSame( array( 'album' => 'my-album_2', 'gallery' => 'gal-1' ), Breadcrumbs::parse_path( '/photos/nggallery/my-album_2/gal-1/', 'nggallery' ) );
	}
}
