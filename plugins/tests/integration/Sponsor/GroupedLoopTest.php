<?php
namespace HyperPress\Tests\Integration\Sponsor;

use HyperPress\Sponsor\ArchiveLoop;
use HyperPress\Sponsor\Content;
use WP_UnitTestCase;

final class GroupedLoopTest extends WP_UnitTestCase {

	/** Counter that gives every created post a later date, so the default (date DESC) order is the reverse of creation. */
	private int $clock = 0;

	public function set_up(): void {
		parent::set_up();
		$this->clock = 0;
		ArchiveLoop::register();
		Content::register();
	}

	private function term_id( string $taxonomy, string $name, string $slug ): int {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term ) {
			return (int) $term->term_id;
		}
		$ids = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
		$this->assertIsArray( $ids );

		return (int) $ids['term_id'];
	}

	/**
	 * @param string|null $season Season number or null for none.
	 * @param int|null    $tier   Tier level or null for none.
	 */
	private function make_sponsor( string $title, ?string $season, ?int $tier ): int {
		++$this->clock;
		$id = self::factory()->post->create(
			array(
				'post_type'  => 'hyper_sponsor',
				'post_title' => $title,
				'post_date'  => gmdate( 'Y-m-d H:i:s', 1700000000 + $this->clock * 60 ),
			)
		);
		if ( null !== $season ) {
			wp_set_object_terms( $id, array( $this->term_id( 'hyper_season', 'Season ' . $season, 'season-' . $season ) ), 'hyper_season' );
		}
		if ( null !== $tier ) {
			wp_set_object_terms( $id, array( $this->term_id( 'hyper_tier', 'Level ' . $tier, 'level-' . $tier ) ), 'hyper_tier' );
		}

		return $id;
	}

	private function render( string $url ): string {
		$this->go_to( home_url( $url ) );
		$template = ArchiveLoop::posts_template( '' );
		$this->assertIsString( $template );
		$this->assertNotSame( '', $template );
		ob_start();
		include $template;

		return (string) ob_get_clean();
	}

	/** @return array<int,string> "tag:text" of every h2..h6 in the markup, in order. */
	private function headings( string $html ): array {
		preg_match_all( '#<(h[2-6])\b[^>]*>(.*?)</\1>#s', $html, $matches, PREG_SET_ORDER );

		return array_map(
			static fn( array $m ): string => $m[1] . ':' . trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( $m[2] ) ) ),
			$matches
		);
	}

	public function test_all_time_archive_prints_season_tier_and_entries(): void {
		$this->make_sponsor( 'a', '32', 4 );
		$this->make_sponsor( 'b', '32', 3 );
		$this->make_sponsor( 'c', '31', 3 );
		$this->make_sponsor( 'z', null, null );

		$html = $this->render( '/?post_type=hyper_sponsor' );

		$this->assertSame(
			array(
				'h2:Season 32',
				'h3:Level 4',
				'h4:a',
				'h3:Level 3',
				'h4:b',
				'h2:Season 31',
				'h3:Level 3',
				'h4:c',
				'h2:Other sponsors',
				'h3:Other sponsors',
				'h4:z',
			),
			$this->headings( $html )
		);
		$this->assertStringContainsString( '<div class="section-divider">', $html );
		$this->assertStringContainsString( 'class="sponsor-group-heading"', $html );
		$this->assertStringNotContainsString( '(continued)', $html );
	}

	public function test_season_scoped_archive_has_no_season_heading(): void {
		$this->make_sponsor( 'a', '31', 4 );
		$this->make_sponsor( 'b', '31', 3 );
		$this->make_sponsor( 'x', '30', 4 );
		flush_rewrite_rules();

		$html = $this->render( '/seasons/season-31/sponsors/' );

		$this->assertSame( array( 'h3:Level 4', 'h4:a', 'h3:Level 3', 'h4:b' ), $this->headings( $html ) );
	}

	public function test_page_two_starting_mid_group_marks_both_headings_continued(): void {
		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => 2 );
		$this->make_sponsor( 'a', '31', 3 );
		$this->make_sponsor( 'b', '31', 3 );
		$this->make_sponsor( 'c', '31', 3 );
		$this->make_sponsor( 'd', '31', 3 );

		$page_one = $this->render( '/?post_type=hyper_sponsor' );
		$this->assertSame( array( 'h2:Season 31', 'h3:Level 3', 'h4:a', 'h4:b' ), $this->headings( $page_one ) );
		$this->assertStringNotContainsString( '(continued)', $page_one );

		$page_two = $this->render( '/?post_type=hyper_sponsor&paged=2' );
		$this->assertSame(
			array( 'h2:Season 31 (continued)', 'h3:Level 3 (continued)', 'h4:c', 'h4:d' ),
			$this->headings( $page_two )
		);
	}

	public function test_page_two_starting_at_a_boundary_has_no_continued(): void {
		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => 2 );
		$this->make_sponsor( 'a', '32', 3 );
		$this->make_sponsor( 'b', '32', 3 );
		$this->make_sponsor( 'c', '31', 2 );
		$this->make_sponsor( 'd', '31', 2 );

		$html = $this->render( '/?post_type=hyper_sponsor&paged=2' );

		$this->assertSame( array( 'h2:Season 31', 'h3:Level 2', 'h4:c', 'h4:d' ), $this->headings( $html ) );
		$this->assertStringNotContainsString( '(continued)', $html );
	}

	public function test_page_two_continues_the_season_but_not_a_new_tier(): void {
		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => 2 );
		$this->make_sponsor( 'a', '31', 3 );
		$this->make_sponsor( 'b', '31', 3 );
		$this->make_sponsor( 'c', '31', 2 );

		$html = $this->render( '/?post_type=hyper_sponsor&paged=2' );

		$this->assertSame( array( 'h2:Season 31 (continued)', 'h3:Level 2', 'h4:c' ), $this->headings( $html ) );
	}

	public function test_season_scoped_page_two_continues_only_the_tier(): void {
		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => 2 );
		$this->make_sponsor( 'a', '31', 3 );
		$this->make_sponsor( 'b', '31', 3 );
		$this->make_sponsor( 'c', '31', 3 );
		flush_rewrite_rules();

		$html = $this->render( '/seasons/season-31/sponsors/?paged=2' );

		$this->assertSame( array( 'h3:Level 3 (continued)', 'h4:c' ), $this->headings( $html ) );
	}

	public function test_term_names_are_escaped_in_headings(): void {
		global $wpdb;
		$id = $this->make_sponsor( 'a', '31', 3 );
		$this->assertGreaterThan( 0, $id );
		$tier = get_term_by( 'slug', 'level-3', 'hyper_tier' );
		$wpdb->update( $wpdb->terms, array( 'name' => 'A <b>"B"</b>' ), array( 'term_id' => $tier->term_id ) );
		clean_term_cache( $tier->term_id, 'hyper_tier' );

		$html = $this->render( '/?post_type=hyper_sponsor' );

		$this->assertStringContainsString( '>A &lt;b&gt;&quot;B&quot;&lt;/b&gt;</h3>', $html );
		$this->assertStringNotContainsString( '<b>', $html );
	}

	public function test_empty_archive_prints_the_theme_nothing_found_content(): void {
		$parts = array();
		add_action(
			'get_template_part_template-parts/content',
			static function ( $slug, $name ) use ( &$parts ): void {
				$parts[] = (string) $name;
			},
			10,
			2
		);

		$html = $this->render( '/?post_type=hyper_sponsor' );

		$this->assertSame( array( 'none' ), $parts );
		$this->assertSame( array(), $this->headings( $html ) );
	}

	public function test_content_template_prints_h2_titles_outside_the_loop(): void {
		$id = $this->make_sponsor( 'solo', '31', 3 );
		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$GLOBALS['post'] = get_post( $id );
		setup_postdata( $GLOBALS['post'] );

		ob_start();
		include Content::add_content_template( '' );
		$html = (string) ob_get_clean();
		wp_reset_postdata();

		$this->assertSame( array( 'h2:solo' ), $this->headings( $html ) );
	}
}
