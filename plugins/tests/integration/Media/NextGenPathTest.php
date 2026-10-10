<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\Breadcrumbs;
use WP_UnitTestCase;

final class NextGenPathTest extends WP_UnitTestCase {

	private const BASE = 'http://example.org/media/photos/nggallery/';

	private static function seg( string $slug, ?string $raw = null ): array {
		return array(
			'slug' => $slug,
			'raw'  => $raw ?? $slug,
		);
	}

	private static function finder( array $map ): callable {
		return static function ( string $slug ) use ( $map ) {
			return $map[ $slug ] ?? null;
		};
	}

	private static function album( string $slug, string $name ): object {
		return (object) array(
			'slug' => $slug,
			'name' => $name,
		);
	}

	private static function gallery( string $slug, string $title ): object {
		return (object) array(
			'slug'  => $slug,
			'title' => $title,
		);
	}

	private static function titles( array $crumbs ): array {
		return array_column( $crumbs, 'title' );
	}

	private static function urls( array $crumbs ): array {
		return array_column( $crumbs, 'url' );
	}

	public function test_a_path_without_the_slug_does_not_match(): void {
		$this->assertNull( Breadcrumbs::parse_path( '/photos/', 'nggallery' ) );
	}

	public function test_the_slug_without_a_trailing_slash_does_not_match(): void {
		$this->assertNull( Breadcrumbs::parse_path( '/photos/nggallery', 'nggallery' ) );
	}

	public function test_an_empty_slug_never_matches(): void {
		$this->assertNull( Breadcrumbs::parse_path( '/photos/nggallery/a/b', '' ) );
	}

	public function test_the_slug_alone_gives_no_segments(): void {
		$this->assertSame( array(), Breadcrumbs::parse_path( '/photos/nggallery/', 'nggallery' ) );
	}

	public function test_a_single_segment_is_returned(): void {
		$expected = array( self::seg( 'summer' ) );
		$this->assertSame( $expected, Breadcrumbs::parse_path( '/photos/nggallery/summer', 'nggallery' ) );
		$this->assertSame( $expected, Breadcrumbs::parse_path( '/photos/nggallery/summer/', 'nggallery' ) );
	}

	public function test_nested_album_and_gallery_segments_are_returned_in_order(): void {
		$this->assertSame(
			array( self::seg( 'missing' ), self::seg( 'gal' ) ),
			Breadcrumbs::parse_path( '/photos/nggallery/missing/gal/', 'nggallery' )
		);
		$this->assertSame(
			array( self::seg( '5' ), self::seg( 'gal' ) ),
			Breadcrumbs::parse_path( '/photos/nggallery/5/gal', 'nggallery' )
		);
	}

	public function test_the_flat_layout_url_keeps_the_literal_album_segment(): void {
		$this->assertSame(
			array( self::seg( 'album' ), self::seg( '2011-2012' ) ),
			Breadcrumbs::parse_path( '/media/photos/nggallery/album/2011-2012', 'nggallery' )
		);
	}

	public function test_more_than_two_segments_are_all_returned(): void {
		$this->assertSame(
			array( self::seg( 'a' ), self::seg( 'b' ), self::seg( 'c' ) ),
			Breadcrumbs::parse_path( '/photos/nggallery/a/b/c/', 'nggallery' )
		);
	}

	public function test_empty_segments_are_dropped(): void {
		$this->assertSame(
			array( self::seg( 'a' ), self::seg( 'b' ) ),
			Breadcrumbs::parse_path( '/photos/nggallery/a//b/', 'nggallery' )
		);
	}

	public function test_parsing_stops_at_reserved_segments(): void {
		$ab = array( self::seg( 'a' ), self::seg( 'b' ) );
		$this->assertSame( $ab, Breadcrumbs::parse_path( '/photos/nggallery/a/b/page/2', 'nggallery' ) );
		$this->assertSame( array( self::seg( 'a' ) ), Breadcrumbs::parse_path( '/photos/nggallery/a/image/5', 'nggallery' ) );
		$this->assertSame( array( self::seg( 'a' ) ), Breadcrumbs::parse_path( '/photos/nggallery/a/tags/x', 'nggallery' ) );
		$this->assertSame( array(), Breadcrumbs::parse_path( '/photos/nggallery/slideshow/', 'nggallery' ) );
		$this->assertSame( array(), Breadcrumbs::parse_path( '/photos/nggallery/Thumbnails', 'nggallery' ) );
		$this->assertSame( array(), Breadcrumbs::parse_path( '/photos/nggallery/imagebrowser', 'nggallery' ) );
	}

	public function test_parameter_style_segments_stop_parsing(): void {
		$this->assertSame( array( self::seg( 'a' ) ), Breadcrumbs::parse_path( '/photos/nggallery/a/nggpage--2', 'nggallery' ) );
		$this->assertSame( array(), Breadcrumbs::parse_path( '/photos/nggallery/album--x/gallery--y', 'nggallery' ) );
		$this->assertSame( array( self::seg( 'a' ) ), Breadcrumbs::parse_path( '/photos/nggallery/a/pid--5', 'nggallery' ) );
	}

	public function test_regex_characters_in_the_slug_are_taken_literally(): void {
		$this->assertSame(
			array( self::seg( 'a' ), self::seg( 'b' ) ),
			Breadcrumbs::parse_path( '/photos/n.g+(x)#/a/b', 'n.g+(x)#' )
		);
		$this->assertNull( Breadcrumbs::parse_path( '/photos/nXg+(x)#/a/b', 'n.g+(x)#' ) );
		$this->assertNull( Breadcrumbs::parse_path( '/photos/nggallery/a/b', '(' ) );
	}

	public function test_the_slug_must_be_a_whole_path_segment(): void {
		$this->assertNull( Breadcrumbs::parse_path( '/photos/notnggallery/a/b', 'nggallery' ) );
	}

	public function test_hostile_segments_come_out_as_sanitized_slugs(): void {
		$this->assertSame(
			array( self::seg( 'or-11', "' OR 1=1" ), self::seg( 'x', 'x<script>' ) ),
			Breadcrumbs::parse_path( '/photos/nggallery/%27%20OR%201%3D1/x%3Cscript%3E', 'nggallery' )
		);
	}

	public function test_percent_encoded_segments_keep_the_decoded_text(): void {
		$this->assertSame(
			array( self::seg( 'cafe', 'café' ), self::seg( 'x' ) ),
			Breadcrumbs::parse_path( '/photos/nggallery/caf%C3%A9/x', 'nggallery' )
		);
	}

	public function test_plain_slugs_are_unchanged(): void {
		$this->assertSame(
			array( self::seg( 'my-album_2' ), self::seg( 'gal-1' ) ),
			Breadcrumbs::parse_path( '/photos/nggallery/my-album_2/gal-1/', 'nggallery' )
		);
	}

	public function test_nested_layout_gives_an_album_crumb_then_a_gallery_crumb(): void {
		$crumbs = Breadcrumbs::resolve(
			array( self::seg( 'summer' ), self::seg( 'team' ) ),
			self::BASE,
			self::finder( array( 'summer' => self::album( 'summer', 'Summer' ) ) ),
			self::finder( array( 'team' => self::gallery( 'team', 'Team' ) ) )
		);

		$this->assertSame( array( 'Summer', 'Team' ), self::titles( $crumbs ) );
		$this->assertSame(
			array( self::BASE . 'summer/', self::BASE . 'summer/team/' ),
			self::urls( $crumbs )
		);
		$this->assertSame(
			array(
				'li'    => array( 'item-album', 'item-album-summer' ),
				'bread' => array( 'bread-album', 'bread-album-summer' ),
			),
			$crumbs[0]['classes']
		);
		$this->assertSame(
			array(
				'li'    => array( 'item-gallery', 'item-gallery-team' ),
				'bread' => array( 'bread-gallery', 'bread-gallery-team' ),
			),
			$crumbs[1]['classes']
		);
	}

	public function test_flat_layout_skips_the_literal_album_segment_but_keeps_it_in_the_url(): void {
		$crumbs = Breadcrumbs::resolve(
			array( self::seg( 'album' ), self::seg( '2011-2012' ) ),
			self::BASE,
			self::finder( array() ),
			self::finder( array( '2011-2012' => self::gallery( '2011-2012', '2011-2012' ) ) )
		);

		$this->assertSame( array( '2011-2012' ), self::titles( $crumbs ) );
		$this->assertSame( array( self::BASE . 'album/2011-2012/' ), self::urls( $crumbs ) );
	}

	public function test_a_lone_segment_is_an_album_before_it_is_a_gallery(): void {
		$crumbs = Breadcrumbs::resolve(
			array( self::seg( 'x' ) ),
			self::BASE,
			self::finder( array( 'x' => self::album( 'x', 'Album X' ) ) ),
			self::finder( array( 'x' => self::gallery( 'x', 'Gallery X' ) ) )
		);

		$this->assertSame( array( 'Album X' ), self::titles( $crumbs ) );
	}

	public function test_a_lone_segment_falls_back_to_a_gallery(): void {
		$crumbs = Breadcrumbs::resolve(
			array( self::seg( 'team' ) ),
			self::BASE,
			self::finder( array() ),
			self::finder( array( 'team' => self::gallery( 'team', 'Team' ) ) )
		);

		$this->assertSame( array( 'Team' ), self::titles( $crumbs ) );
		$this->assertSame( array( self::BASE . 'team/' ), self::urls( $crumbs ) );
	}

	public function test_a_last_segment_after_an_album_is_a_gallery_even_when_an_album_shares_its_slug(): void {
		$crumbs = Breadcrumbs::resolve(
			array( self::seg( 'summer' ), self::seg( 'x' ) ),
			self::BASE,
			self::finder(
				array(
					'summer' => self::album( 'summer', 'Summer' ),
					'x'      => self::album( 'x', 'Album X' ),
				)
			),
			self::finder( array( 'x' => self::gallery( 'x', 'Gallery X' ) ) )
		);

		$this->assertSame( array( 'Summer', 'Gallery X' ), self::titles( $crumbs ) );
	}

	public function test_a_last_segment_after_an_album_falls_back_to_a_sub_album(): void {
		$crumbs = Breadcrumbs::resolve(
			array( self::seg( 'summer' ), self::seg( 'sub' ) ),
			self::BASE,
			self::finder(
				array(
					'summer' => self::album( 'summer', 'Summer' ),
					'sub'    => self::album( 'sub', 'Sub' ),
				)
			),
			self::finder( array() )
		);

		$this->assertSame( array( 'Summer', 'Sub' ), self::titles( $crumbs ) );
		$this->assertSame( array( self::BASE . 'summer/', self::BASE . 'summer/sub/' ), self::urls( $crumbs ) );
	}

	public function test_a_non_last_segment_is_never_looked_up_as_a_gallery(): void {
		$galleries_asked = array();
		$crumbs          = Breadcrumbs::resolve(
			array( self::seg( 'x' ), self::seg( 'team' ) ),
			self::BASE,
			self::finder( array() ),
			static function ( string $slug ) use ( &$galleries_asked ) {
				$galleries_asked[] = $slug;
				return 'team' === $slug ? (object) array(
					'slug'  => 'team',
					'title' => 'Team',
				) : (object) array(
					'slug'  => 'x',
					'title' => 'Wrong',
				);
			}
		);

		$this->assertSame( array( 'team' ), $galleries_asked );
		$this->assertSame( array( 'Team' ), self::titles( $crumbs ) );
	}

	public function test_an_unknown_middle_segment_is_skipped_but_kept_in_later_urls(): void {
		$crumbs = Breadcrumbs::resolve(
			array( self::seg( 'summer' ), self::seg( 'nope' ), self::seg( 'team' ) ),
			self::BASE,
			self::finder( array( 'summer' => self::album( 'summer', 'Summer' ) ) ),
			self::finder( array( 'team' => self::gallery( 'team', 'Team' ) ) )
		);

		$this->assertSame( array( 'Summer', 'Team' ), self::titles( $crumbs ) );
		$this->assertSame( array( self::BASE . 'summer/', self::BASE . 'summer/nope/team/' ), self::urls( $crumbs ) );
	}

	public function test_urls_use_the_stored_slug_and_unmatched_segments_are_encoded(): void {
		$crumbs = Breadcrumbs::resolve(
			array( self::seg( 'caf', 'café au lait' ), self::seg( 'team', 'Team' ) ),
			self::BASE,
			self::finder( array() ),
			self::finder( array( 'team' => self::gallery( 'team-stored', 'Team' ) ) )
		);

		$this->assertSame( array( self::BASE . 'caf%C3%A9%20au%20lait/team-stored/' ), self::urls( $crumbs ) );
	}

	public function test_nothing_resolving_gives_no_crumbs(): void {
		$none = self::finder( array() );

		$this->assertSame( array(), Breadcrumbs::resolve( array( self::seg( 'a' ), self::seg( 'b' ) ), self::BASE, $none, $none ) );
		$this->assertSame( array(), Breadcrumbs::resolve( array(), self::BASE, $none, $none ) );
	}
}
