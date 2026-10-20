# Plugins 04: Migrate Countdown, Newsletter, Sponsor, Socials, Label, Student; Create Shortcodes and Media

> **STATUS: COMPLETED** (2026-10-04). Commits 52e6ab6..d10d636 on branch cleanup/convention-and-tests. All 14 plugins are on the new structure; every unit and integration test passes (4 skipped by design).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use checkbox (`- [x]`) syntax.

**Goal:** Finish migrating the remaining six plugins, then create the two plugins extracted from the theme (`hyperpress-shortcodes`, `hyperpress-media`), one commit per plugin.

**Prerequisite:** plugins-03-core-plugins complete. Its "Conversion rules" section (R1 to R7) applies here unchanged; this plan does not repeat it. The post-type `Plugin.php` shape is the one from `hyperpress-robot/src/Plugin.php` (plugins-03-core-plugins Task 4).
**Followed by:** plugins-05-standards-and-smoke-test.

**Conventions:** as in plugins-01-test-toolchain and plugins-02-shared-utils. Commit with an explicit pathspec.

---

## Task 1: Migrate `hyperpress-countdown`

**Requires Plugins:** `hyperpress-utils, meta-box-aio`. Countdown does NOT require season (non-public, no taxonomy). **Namespace:** `HyperPress\Countdown`. **Legacy files:** `countdown.php`, `lib/{custom-post-type,customize,enqueue-scripts,meta-boxes,shortcodes}.php`, `lib/metaboxes/{event-information,visibility}.php`, `lib/shortcodes/countdown.php`.

**Target files:** `hyperpress-countdown.php`, `src/Plugin.php`, `src/Customizer.php`, `src/Assets.php`, `src/MetaBoxes.php`, `src/Shortcode.php`.

- [x] **Step 1: Read the legacy files and print the legacy registration** (values this plan does not list):

Run: `docker compose run --rm php php -r 'echo json_encode(json_decode(file_get_contents("tests/fixtures/legacy-registrations.json"),true)["post_types"]["countdown"], JSON_PRETTY_PRINT);'`
Note `labels.singular_name`, `menu_position`, `menu_icon`, `has_archive`, `supports`.

- [x] **Step 2: R1 and R2** for `hyperpress-countdown.php`.

- [x] **Step 3: Write `src/Plugin.php`**: same shape as robot's, with `POST_TYPE = 'hyper_countdown'`, `CAPS_OPTION = 'hyperpress_countdown_caps_version'`, main file `hyperpress-countdown.php`, NO `Breadcrumbs::register` (non-public type), plus `Customizer::register()`, `Assets::register()`, `MetaBoxes::register()`, `Shortcode::register()` in `boot()`. Definition:

```php
	public static function definition(): array {
		return array(
			'key'           => self::POST_TYPE,
			'singular'      => __( 'Countdown', 'hyperpress' ),
			'plural'        => __( 'Countdowns', 'hyperpress' ),
			'description'   => __( 'Time Circle Count Downs for Display in the UI', 'hyperpress' ),
			'slug'          => 'countdown', // Required by the registrar; unused because rewrite is disabled below.
			'archive'       => false,
			'menu_position' => 11,
			'menu_icon'     => 'dashicons-clock',
			'supports'      => array( 'title' ), // Legacy also listed 'slug', which is not a real feature.
			'args'          => array(
				'label'               => __( 'Count Downs', 'hyperpress' ), // Legacy 'label' differs from the plural label text.
				'public'              => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'query_var'           => false,
				'rewrite'             => false,
			),
		);
	}
```

Values verified against legacy `lib/custom-post-type.php:47-75` (menu position 11, icon `dashicons-clock`, supports `title` plus the invalid `slug`, `label` 'Count Downs' but label strings 'Countdown(s)'). Step 1's printout should agree; if it does not, the snapshot wins. Legacy `capability_type => 'page'` is intentionally replaced by the shared specific capabilities, and the legacy `order => true` leftover is dropped.

- [x] **Step 4: Convert the concern files (R3) with these renames**

| Legacy                                                                                                  | New                                                                                                                                                                                                                                        |
| ------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| meta box ids (`lib/metaboxes/event-information.php`, `visibility.php`)                                  | `hyperpress_countdown_event_information`, `hyperpress_countdown_visibility`                                                                                                                                                                |
| fields `event_name`, `event_location`, `event_date_time`                                                | `hyperpress_countdown_event_name`, `hyperpress_countdown_event_location`, `hyperpress_countdown_event_date_time`                                                                                                                           |
| fields `countdown_past_zero`, `hide_before`, `hide_after`                                               | `hyperpress_countdown_past_zero`, `hyperpress_countdown_hide_before`, `hyperpress_countdown_hide_after`                                                                                                                                    |
| `post_types => ['countdown']`                                                                           | `['hyper_countdown']`                                                                                                                                                                                                                      |
| Customizer control ids `countdown_days_color`, `_hours_`, `_minutes_`, `_seconds_`                      | `hyperpress_countdown_days_color`, `hyperpress_countdown_hours_color`, `hyperpress_countdown_minutes_color`, `hyperpress_countdown_seconds_color` (the setting ids are already these; the section is WordPress core's `colors`, unchanged) |
| script and style handle `timecircles` (`enqueue-scripts.php`, and `lib/shortcodes/countdown.php:55-56`) | `hyperpress-countdown-timecircles`                                                                                                                                                                                                         |
| every read of the renamed meta keys in `lib/shortcodes/countdown.php` (`get_post_meta` / `rwmb_meta`)   | the new field ids                                                                                                                                                                                                                          |

Keep the `hyperpress_countdown` shortcode tag. Keep `extract()` in the shortcode and all output code as is: they are plugins audit findings, not part of this plan. Legacy `lib/shortcodes.php` is a loader; it is replaced by `Shortcode::register()`.

- [x] **Step 5: Delete legacy `lib/` files, run R7** (`<name>`=countdown, `<old>`=countdown).

- [x] **Step 6: Commit**

```bash
git add hyperpress-countdown
git commit -m "refactor(countdown): migrate to hyper_countdown and the shared structure" -- hyperpress-countdown
```

---

## Task 2: Migrate `hyperpress-newsletter`

**Requires Plugins:** `hyperpress-utils, hyperpress-season, meta-box-aio`. **Namespace:** `HyperPress\Newsletter`. **Legacy files:** `newsletter.php`, `lib/{banner,breadcrumbs,content,custom-post-type,customize,labels,meta-boxes,shortcodes}.php`.

**Target files:** `hyperpress-newsletter.php`, `src/Plugin.php`, `src/BannerFilter.php`, `src/Content.php`, `src/Customizer.php`, `src/Labels.php`, `src/MetaBoxes.php`, `src/Shortcodes.php`. (Note 2026-10-14: `BannerFilter` moved into the shared `HyperPress\Season\SingularBanner` (`hyperpress-season/src/SingularBanner.php`), which the newsletter, award and robot plugins register; `src/BannerFilter.php` no longer exists.)

- [x] **Step 1: Read the legacy files** (notably `labels.php`, `content.php`, `shortcodes.php`, which this plan has not seen) and print the legacy rewrite flags: newsletter's legacy rewrite is `{with_front:false, slug:newsletter, hierarchical:true}`.

- [x] **Step 2: R1 and R2** for `hyperpress-newsletter.php`. Header fixes: add `Plugin URI`, `License` and `License URI` (copy from `hyperpress-award`). **Before replacing the body**, read it: the legacy main file hooks the theme's `content_template` filter (`newsletter.php:43`, callback `hyperpress_add_newsletter_content_template`). That hook and callback move into `src/Content.php` (registered by `Plugin::boot()`), they are not deleted. The theme includes the path this filter returns, so the behavior must survive.

- [x] **Step 3: Write the regression test FIRST** `tests/integration/NewsletterBannerTest.php` (it documents current behavior, including the color quirk):

```php
<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class NewsletterBannerTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb'] );
		parent::tear_down();
	}

	private function banner_for( int $post_id ): array {
		$this->go_to( get_permalink( $post_id ) );
		return apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
	}

	public function test_singular_title_is_prefixed_with_the_latest_season(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter', 'post_title' => 'Kickoff' ) );
		$old     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2025' ) );
		$new     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026' ) );
		wp_set_object_terms( $post_id, array( $old, $new ), 'hyper_season' );

		$banner = $this->banner_for( $post_id );
		$this->assertSame( 'newsletter', $banner['type'] );
		$this->assertSame( '2026: Kickoff', $banner['title'] );
	}

	public function test_singular_without_a_season_falls_back_to_newsletter(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter', 'post_title' => 'Kickoff' ) );
		$this->assertSame( 'Newsletter', $this->banner_for( $post_id )['title'] );
	}

	public function test_singular_color_comes_from_the_per_post_meta_box(): void {
		$GLOBALS['hyperpress_test_rwmb']['hyperpress_banner_background_color'] = '#123456';
		set_theme_mod( 'hyperpress_hyper_orange', '#ff8800' );
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$this->assertSame( '#123456', $this->banner_for( $post_id )['backgroundColor'] );
	}

	public function test_archive_title_is_always_set_and_season_uses_from(): void {
		set_theme_mod( 'hyperpress_newsletter_banner_subtitle', 'News' );
		$this->go_to( get_post_type_archive_link( 'hyper_newsletter' ) );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => 'Preset' ) );
		$this->assertSame( 'Newsletters', $banner['title'] );
		$this->assertSame( 'News', $banner['subtitle'] );
	}
}
```

Run it: `docker compose run --rm php composer test:integration -- --filter NewsletterBannerTest`. Expected: FAIL (post type not registered yet).

- [x] **Step 3b: Add the content-template contract test** `tests/integration/NewsletterContentTemplateTest.php`:

```php
<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class NewsletterContentTemplateTest extends WP_UnitTestCase {
	public function test_single_newsletter_supplies_a_readable_content_template(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$this->go_to( get_permalink( $post_id ) );
		$path = apply_filters( 'content_template', '' );
		$this->assertNotSame( '', $path );
		$this->assertFileIsReadable( $path );
	}
}
```

It fails until Step 6 is done. If the legacy callback only returns a path for a request shape other than a single (check `newsletter/lib/content.php`), change the request in this test to match, not the callback.

- [x] **Step 4: Write `src/Plugin.php`**: robot's shape, with `POST_TYPE = 'hyper_newsletter'`, `CAPS_OPTION = 'hyperpress_newsletter_caps_version'`, main file `hyperpress-newsletter.php`, plus in `boot()`: `BannerFilter::register()`, `Content::register()`, `Customizer::register()`, `Labels::register()`, `MetaBoxes::register()`, `Shortcodes::register()` (register only the concerns that exist after Step 1), and:

```php
		Banner::register(
			self::POST_TYPE,
			array(
				'type'                     => 'newsletter',
				'subtitle_mod'             => 'hyperpress_newsletter_banner_subtitle',
				'color_mod'                => 'hyperpress_hyper_orange',
				'archive_title'            => __( 'Newsletters', 'hyperpress' ),
				'term_joiner'              => 'from',
				'always_set_archive_title' => true,
			)
		);
```

(`use HyperPress\Utils\Banner;`.) Definition:

```php
	public static function definition(): array {
		return array(
			'key'                  => self::POST_TYPE,
			'singular'             => __( 'Newsletter', 'hyperpress' ),
			'plural'               => __( 'Newsletters', 'hyperpress' ),
			'description'          => __( 'Newsletters from Team HYPER Students', 'hyperpress' ),
			'slug'                 => 'newsletter',
			'archive'              => 'newsletters',
			'menu_position'        => 21,
			'menu_icon'            => 'dashicons-media-text',
			'supports'             => array( 'title', 'author', 'editor', 'revisions' ),
			'hierarchical'         => false,
			'rewrite_hierarchical' => true,
		);
	}
```

- [x] **Step 5: Write `src/BannerFilter.php`** (moves the season-aware singular logic out of the shared code; behavior copied from legacy `lib/banner.php`, including that the per-post color overwrites the theme-mod color even when empty): (Note 2026-10-14: `BannerFilter` moved into the shared `HyperPress\Season\SingularBanner` (`hyperpress-season/src/SingularBanner.php`), which the newsletter, award and robot plugins register; `src/BannerFilter.php` no longer exists.)

```php
<?php
namespace HyperPress\Newsletter;

defined( 'ABSPATH' ) || exit;

final class BannerFilter {

	public static function register(): void {
		add_filter( 'hyperpress_banner_args', array( self::class, 'adjust' ), 10, 3 );
	}

	/**
	 * @param array  $banner    Assembled banner.
	 * @param string $post_type Post type the banner is for.
	 * @param array  $ctx       Request context from HyperPress\Utils\Banner.
	 */
	public static function adjust( $banner, $post_type, $ctx ) {
		if ( Plugin::POST_TYPE !== $post_type || empty( $ctx['is_singular'] ) ) {
			return $banner;
		}

		if ( empty( $banner['title'] ) ) {
			$seasons = wp_get_object_terms(
				get_the_ID(),
				'hyper_season',
				array(
					'orderby' => 'name',
					'order'   => 'DESC',
				)
			);
			if ( ! empty( $seasons ) && ! is_wp_error( $seasons ) ) {
				$banner['title'] = $seasons[0]->name . ': ' . get_the_title();
			} else {
				$banner['title'] = __( 'Newsletter', 'hyperpress' );
			}
		}

		$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color' );
		$blog_page_id              = get_option( 'page_for_posts' );
		if ( empty( $banner['backgroundColor'] ) && $blog_page_id > 0 ) {
			$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color', '', $blog_page_id );
		}

		return $banner;
	}
}
```

- [x] **Step 6: Convert the remaining concern files (R3) with these renames**

| Legacy                                             | New                                                                                                     |
| -------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| meta box id `information`                          | `hyperpress_newsletter_information`                                                                     |
| field `canva_newsletter_url`                       | `hyperpress_newsletter_canva_url` (and every read of it, including in `content.php` / `shortcodes.php`) |
| `post_types => ['newsletter']`                     | `['hyper_newsletter']`                                                                                  |
| Customizer control id `newsletter_banner_subtitle` | `hyperpress_newsletter_banner_subtitle` (the setting id is already this)                                |
| taxonomy `'season'` anywhere (queries, `is_tax`)   | `'hyper_season'`                                                                                        |
| post type `'newsletter'` anywhere                  | `'hyper_newsletter'`                                                                                    |

The legacy `lib/breadcrumbs.php` and `lib/banner.php` are deleted (replaced by the shared classes).

- [x] **Step 7: Delete legacy `lib/` files, run R7** (`<name>`=newsletter, `<old>`=newsletter) plus the regression test:

Run: `docker compose run --rm php composer test:integration -- --filter 'NewsletterBannerTest|RegistrationParityTest.*newsletter'`
Expected: PASS.

- [x] **Step 8: Commit**

```bash
git add hyperpress-newsletter tests/integration/NewsletterBannerTest.php
git commit -m "refactor(newsletter): migrate to hyper_newsletter; banner specifics move to a filter" -- hyperpress-newsletter tests/integration/NewsletterBannerTest.php
```

---

## Task 3: Migrate `hyperpress-sponsor`

**Requires Plugins:** `hyperpress-utils, hyperpress-season, meta-box-aio`. **Namespace:** `HyperPress\Sponsor`. **Legacy files:** `sponsor.php`, `lib/{archive,banner,breadcrumbs,content,custom-post-type,custom-taxonomy,customize,labels,meta-boxes,shortcodes}.php`, `lib/widgets/{display-sponsors,load}.php`.

**Target files:** `hyperpress-sponsor.php`, `src/Plugin.php`, `src/Archive.php`, `src/Content.php`, `src/Customizer.php`, `src/Labels.php`, `src/MetaBoxes.php`, `src/Shortcodes.php`, `src/Widgets/DisplaySponsorsWidget.php`, `src/Widgets.php` (replaces `widgets/load.php`).

- [x] **Step 1: Read the legacy files** (`archive.php`, `content.php`, `labels.php`, `shortcodes.php`, `display-sponsors.php` are not yet seen).
- [x] **Step 2: R1 and R2** for `hyperpress-sponsor.php`. **Before replacing the body**, read it: it hooks the theme's `content_template` filter (`sponsor.php:52`, callback `hyperpress_add_sponsor_content_template`); that hook and callback move into `src/Content.php`. Add `tests/integration/SponsorContentTemplateTest.php` identical in shape to the newsletter one (post type `hyper_sponsor`, class `SponsorContentTemplateTest`), and make it pass in Step 5.
- [x] **Step 3: Write `src/Plugin.php`**: robot's shape with `POST_TYPE = 'hyper_sponsor'`, `TIER = 'hyper_tier'`, `CAPS_OPTION = 'hyperpress_sponsor_caps_version'`, main file `hyperpress-sponsor.php`, `register()` registering the post type and the tier taxonomy, and `boot()` additionally calling `Archive::register()`, `Content::register()`, `Customizer::register()`, `Labels::register()`, `MetaBoxes::register()`, `Shortcodes::register()`, `Widgets::register()` (only the concerns that exist after Step 1) and:

```php
		Banner::register(
			self::POST_TYPE,
			array(
				'type'          => 'sponsor',
				'subtitle_mod'  => 'hyperpress_sponsor_banner_subtitle',
				'color_mod'     => 'hyperpress_hyper_red',
				'archive_title' => __( 'Sponsors', 'hyperpress' ),
				'term_joiner'   => 'in',
			)
		);
```

Definitions:

```php
	public static function definition(): array {
		return array(
			'key'                  => self::POST_TYPE,
			'singular'             => __( 'Sponsor', 'hyperpress' ),
			'plural'               => __( 'Sponsors', 'hyperpress' ),
			'description'          => __( 'Sponsors of Team HYPER', 'hyperpress' ),
			'slug'                 => 'sponsor',
			'archive'              => 'sponsors',
			'menu_position'        => 12,
			'menu_icon'            => 'dashicons-businessman',
			'supports'             => array( 'title', 'thumbnail', 'page-attributes', 'revisions' ),
			'hierarchical'         => false,
			'rewrite_hierarchical' => true,
		);
	}

	public static function tier_definition(): array {
		return array(
			'key'                => self::TIER,
			'singular'           => __( 'Tier', 'hyperpress' ),
			'plural'             => __( 'Tiers', 'hyperpress' ),
			'description'        => __( 'Levels of Sponsorship', 'hyperpress' ),
			'slug'               => null,
			'object_types'       => array( self::POST_TYPE ),
			'public'             => false,
			'publicly_queryable' => false,
			'hierarchical'       => false,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => false,
			'show_admin_column'  => true,
			'meta_box_cb'        => false,
		);
	}
```

- [x] **Step 4: Convert the concern files (R3) with these renames**

| Legacy                                                                               | New                                                                                                                          |
| ------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------- |
| meta box id `information`                                                            | `hyperpress_sponsor_information`                                                                                             |
| fields `sponsor_website`, `sponsor_description`                                      | `hyperpress_sponsor_website`, `hyperpress_sponsor_description` (and every read, including shortcodes, widget, content)       |
| field `tier` (taxonomy field, `lib/meta-boxes.php`)                                  | `hyperpress_sponsor_tier` with `taxonomy => 'hyper_tier'`                                                                    |
| `post_types => ['sponsor']`                                                          | `['hyper_sponsor']`                                                                                                          |
| taxonomy `'tier'`, `'season'` anywhere (queries, archive ordering, `is_tax`, widget) | `'hyper_tier'`, `'hyper_season'`                                                                                             |
| post type `'sponsor'` anywhere                                                       | `'hyper_sponsor'`                                                                                                            |
| `\HYPER_Press_Season\get_sorted_seasons` (used by `lib/labels.php`)                  | `\HyperPress\Season\SortedSeasons::get`                                                                                      |
| Customizer control id `sponsor_banner_subtitle`                                      | `hyperpress_sponsor_banner_subtitle`                                                                                         |
| widget class `HYPERpress_Display_Sponsors_Widget`                                    | `HyperPress\Sponsor\Widgets\DisplaySponsorsWidget`; `id_base` stays `hyperpress_display_sponsors_widget` (already compliant) |

Do not change the `sponsor_description` sanitizing (`lib/meta-boxes.php:46-52`): it is a plugins audit finding. The legacy `banner.php`, `breadcrumbs.php`, `custom-post-type.php`, `custom-taxonomy.php` are replaced by the shared classes and Plugin.

- [x] **Step 5: Delete legacy `lib/` files, run R7** (`<name>`=sponsor, `<old>`=sponsor) and parity for the tier:

Run: `docker compose run --rm php composer test:integration -- --filter 'RegistrationParityTest.*(sponsor|tier)'`
Expected: PASS.

- [x] **Step 6: Commit**

```bash
git add hyperpress-sponsor
git commit -m "refactor(sponsor): migrate to hyper_sponsor and hyper_tier and the shared structure" -- hyperpress-sponsor
```

- 2026-10-19: the sponsor archive is ordered by newest season, then tier (highest first), then menu order and title, in `HyperPress\Sponsor\Ordering`.

---

## Task 4: Migrate `hyperpress-socials`

**Requires Plugins:** none (remove the legacy `meta-box-aio` dependency: socials registers no meta boxes). **Namespace:** `HyperPress\Socials`. **Legacy files:** `socials.php`, `lib/customize.php`, `lib/widgets/{display-socials,load}.php`.

**Target files:** `hyperpress-socials.php`, `src/Plugin.php`, `src/Customizer.php`, `src/Widgets.php`, `src/Widgets/DisplaySocialsWidget.php`.

- [x] **Step 1: Read the legacy files.**
- [x] **Step 2: R1 and R2** for `hyperpress-socials.php` (no `activate`, no Utils use; `Plugin::boot()` only registers `Customizer::register()` and `Widgets::register()`). Header: no `Requires Plugins` line at all.
- [x] **Step 3: Convert `Customizer` with these renames** (setting ids `hyperpress_socials_*` are already compliant and stay):

| Legacy                                                                                                                                                                                 | New                                                                                                                                                                                                                                                   |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| section `socials`                                                                                                                                                                      | `hyperpress_socials`                                                                                                                                                                                                                                  |
| control ids `socials_firstinspires`, `_thebluealliance`, `_facebook`, `_instagram`, `_youtube`, `_twitter`, `_github`, `_snapchat`, `_tiktok`, `_discord`, `_contact`, `_add_calendar` | `hyperpress_socials_firstinspires`, `..._thebluealliance`, `..._facebook`, `..._instagram`, `..._youtube`, `..._twitter`, `..._github`, `..._snapchat`, `..._tiktok`, `..._discord`, `..._contact`, `..._add_calendar` (each equal to its setting id) |
| each control's `section` value                                                                                                                                                         | `hyperpress_socials`                                                                                                                                                                                                                                  |

- [x] **Step 4: Convert the widget**: class `HYPERpress_Display_Socials_Widget` becomes `HyperPress\Socials\Widgets\DisplaySocialsWidget`; `id_base` stays `hyperpress_socials`; `Widgets::register()` hooks `widgets_init` and calls `register_widget( DisplaySocialsWidget::class )`. Leave the widget's `$args['before_widget']` style echoes as they are (plugins audit item).
- [x] **Step 5: Delete legacy `lib/` files, run R7** (`<name>`=socials; no parity case).
- [x] **Step 6: Commit**

```bash
git add hyperpress-socials
git commit -m "refactor(socials): migrate to the shared structure and hyperpress_ customizer ids" -- hyperpress-socials
```

---

## Task 5: Migrate `hyperpress-label` and `hyperpress-student` (placeholders)

**Requires Plugins:** none for both. They have no behavior and stay that way.

- [x] **Step 1: Read `hyperpress-label/label.php` and `hyperpress-student/student.php`.**
- [x] **Step 2: Label.** `git mv hyperpress-label/label.php hyperpress-label/hyperpress-label.php`. Apply R1 header edits (no `Requires Plugins`). Make the Description say it is a placeholder, for example `Placeholder for a future HYPERpress label feature. Does nothing yet.` Body after the header is only `defined( 'ABSPATH' ) || exit;`.
- [x] **Step 3: Student.** `git mv hyperpress-student/student.php hyperpress-student/hyperpress-student.php`. Apply the same header edits, and add the missing `Author` and `Author URI` (copy from another plugin). Description: `Placeholder for a future HYPERpress student feature. Does nothing yet.`
- [x] **Step 4: Verify**

Run: `docker compose run --rm php composer test:unit -- --filter 'PluginHeadersTest.*(label|student)'`
Expected: PASS. Run `composer test:integration -- --filter BootTest`: PASS.

- [x] **Step 5: Commit**

```bash
git add hyperpress-label hyperpress-student
git commit -m "refactor(label,student): rename main files, fix headers, mark as placeholders" -- hyperpress-label hyperpress-student
```

---

## Task 6: Create `hyperpress-shortcodes` (extracted from the theme)

**Requires Plugins:** none. **Namespace:** `HyperPress\Shortcodes`. **Source (read-only here; the theme copies are removed later by the theme plans):** `theme/library/shortcodes/{show-hide-content,date-distance,raw,emphasis,banner}.php`.

Shortcode **tags do not change**: they are stored in post content. Behavior does not change either; defects such as `extract()` are left for the audit. Characterization fixtures are captured from the theme code first, so the move is checked against what the theme actually does.

**Target files:** `hyperpress-shortcodes/hyperpress-shortcodes.php`, `src/Plugin.php`, `src/Shortcodes/TimeRestrict.php` (registers `time-restrict`, `time-restrict-repeat`, `time-restrict-repeat-1`, `-2`, `-3`), `src/Shortcodes/DateDistance.php` (`hyper_date_distance`), `src/Shortcodes/Raw.php` (`raw`), `src/Shortcodes/Emphasis.php` (`hyper_emphasis`), `src/Shortcodes/Banner.php` (`hyper_banner`).

- [x] **Step 1: Let the test container see the theme (read-only)**

Add to the `php` service `volumes` in `compose.yaml`: `- ../theme:/theme:ro`. Then `docker compose up -d --force-recreate php` is not needed (`run --rm` picks it up).

- [x] **Step 2: Read the five theme files** and write down every tag, attribute (with defaults) and output shape. Note any theme function or theme mod a shortcode depends on; a shortcode that depends on a theme function cannot move unchanged, so stop and ask before continuing.

- [x] **Step 3: Write the case list `tests/src/Support/ShortcodeCases.php`**

```php
<?php
namespace HyperPress\Tests\Support;

/** Markup fed to do_shortcode(). Every attribute of every tag must appear in at least one case. */
final class ShortcodeCases {

	public const CASES = array(
		'raw_basic'   => '[raw]<b>x</b>[/raw]',
		'emphasis'    => '[hyper_emphasis]Hello[/hyper_emphasis]',
		// Add one case per attribute and one default case per tag, from Step 2.
		// Time-dependent shortcodes (time-restrict*, hyper_date_distance): use dates far in the past
		// (2000-01-01) and future (2100-01-01) so the result does not change as time passes.
	);
}
```

Complete the array from Step 2 before continuing. The list above is the required shape and two known-valid examples; the real list has at least one case per tag and per attribute.

- [x] **Step 4: Write the capture test `tests/integration/Shortcodes/CaptureLegacyShortcodesTest.php`** (env-gated, like the snapshot from plugins-01-test-toolchain)

```php
<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use HyperPress\Tests\Support\ShortcodeCases;
use WP_UnitTestCase;

final class CaptureLegacyShortcodesTest extends WP_UnitTestCase {

	public const FIXTURE = __DIR__ . '/../../fixtures/legacy-shortcodes.json';

	public function test_capture_output_of_the_theme_shortcodes(): void {
		if ( '1' !== getenv( 'HYPERPRESS_WRITE_SNAPSHOT' ) ) {
			$this->markTestSkipped( 'Set HYPERPRESS_WRITE_SNAPSHOT=1 to regenerate (theme code only).' );
		}

		global $shortcode_tags;
		$before = array_keys( $shortcode_tags );

		foreach ( array( 'show-hide-content', 'date-distance', 'raw', 'emphasis', 'banner' ) as $file ) {
			require_once "/theme/library/shortcodes/$file.php";
		}

		$legacy_tags = array_values( array_diff( array_keys( $shortcode_tags ), $before ) );
		$outputs     = array();
		foreach ( ShortcodeCases::CASES as $name => $markup ) {
			$outputs[ $name ] = do_shortcode( $markup );
		}

		$used = implode( ' ', ShortcodeCases::CASES );
		foreach ( $legacy_tags as $tag ) {
			$this->assertStringContainsString( '[' . $tag, $used, "No case exercises the [$tag] shortcode" );
		}

		file_put_contents(
			self::FIXTURE,
			wp_json_encode( array( 'tags' => $legacy_tags, 'outputs' => $outputs ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"
		);
		$this->assertFileExists( self::FIXTURE );
	}
}
```

- [x] **Step 5: Capture, inspect, commit the fixture**

Run: `docker compose run --rm -e HYPERPRESS_WRITE_SNAPSHOT=1 php composer test:integration -- --filter CaptureLegacyShortcodesTest`
Expected: PASS and `tests/fixtures/legacy-shortcodes.json` written. If a theme file fatals because it needs another theme function, that is the Step 2 stop-and-ask case. Open the fixture: every output should look right (no PHP notices in the text). Fix the case list rather than editing the fixture.

```bash
git add compose.yaml tests/src/Support/ShortcodeCases.php tests/integration/Shortcodes/CaptureLegacyShortcodesTest.php tests/fixtures/legacy-shortcodes.json
git commit -m "test: capture legacy theme shortcode output" -- compose.yaml tests/src/Support/ShortcodeCases.php tests/integration/Shortcodes/CaptureLegacyShortcodesTest.php tests/fixtures/legacy-shortcodes.json
```

- [x] **Step 6: Write the parity test `tests/integration/Shortcodes/ShortcodeParityTest.php`** (fails until the plugin exists)

```php
<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use HyperPress\Tests\Support\ShortcodeCases;
use WP_UnitTestCase;

final class ShortcodeParityTest extends WP_UnitTestCase {

	// Own constant: data providers run before other test classes are autoloaded.
	private const FIXTURE = __DIR__ . '/../../fixtures/legacy-shortcodes.json';

	private static function fixture(): array {
		return json_decode( (string) file_get_contents( self::FIXTURE ), true );
	}

	public static function cases(): array {
		$cases = array();
		foreach ( array_keys( self::fixture()['outputs'] ) as $name ) {
			$cases[ $name ] = array( $name );
		}
		return $cases;
	}

	public function test_every_legacy_tag_is_registered_by_the_plugin(): void {
		foreach ( self::fixture()['tags'] as $tag ) {
			$this->assertTrue( shortcode_exists( $tag ), "[$tag] is not registered" );
		}
	}

	/** @dataProvider cases */
	public function test_output_matches_the_theme( string $name ): void {
		$this->assertSame( self::fixture()['outputs'][ $name ], do_shortcode( ShortcodeCases::CASES[ $name ] ), $name );
	}
}
```

Run it: `docker compose run --rm php composer test:integration -- --filter ShortcodeParityTest`. Expected: FAIL (tags not registered), unless the theme is active in the test environment, which it is not.

- [x] **Step 7: Create the plugin** with the rules from plugins-03-core-plugins (R1 to R5), with these specifics:
  - `hyperpress-shortcodes.php`: header per R1 (`Plugin Name: hyperPress Shortcodes`, a real Description, Author, License and License URI copied from `hyperpress-award`, no `Requires Plugins`), R2 body without `activate`.
  - `src/Plugin.php`: `boot()` adds `add_action( 'init', array( self::class, 'register' ) )`; `register()` calls `Shortcodes\TimeRestrict::register()`, `DateDistance::register()`, `Raw::register()`, `Emphasis::register()`, `Banner::register()`.
  - Each shortcode class: `final class X { public static function register(): void { add_shortcode( '<tag>', array( self::class, 'render' ) ); } public static function render( $atts = array(), $content = null ) { /* body copied unchanged from the theme function */ } }`. Keep `extract()` and the output code exactly as in the theme (audit items). Rename nothing that is a tag or an attribute name.
  - If a theme function has a different signature or extra helper functions (for example in `show-hide-content.php`), move the helpers into the same class as `private static` methods.

- [x] **Step 8: Run**

Run: `docker compose run --rm php composer test:integration -- --filter ShortcodeParityTest`
Expected: PASS for every case. plugins-01-test-toolchain mismatch means the move changed behavior: fix the plugin, never the fixture.
Run: `docker compose run --rm php composer test:unit -- --filter 'PluginHeadersTest.*shortcodes'` PASS; `composer lint:syntax` exit 0.

- [x] **Step 9: Commit**

```bash
git add hyperpress-shortcodes tests/integration/Shortcodes/ShortcodeParityTest.php
git commit -m "feat(shortcodes): add hyperpress-shortcodes (extracted from the theme)" -- hyperpress-shortcodes tests/integration/Shortcodes/ShortcodeParityTest.php
```

---

## Task 7: Create `hyperpress-media` (extracted from the theme)

**Requires Plugins:** none. **Namespace:** `HyperPress\Media`. **Source (read-only here):** `theme/library/supports/{svg,avif}.php`, `theme/library/customize/nggallery.php`, `theme/library/breadcrumbs/nggallery.php`.

**Target files:** `hyperpress-media/hyperpress-media.php`, `src/Plugin.php`, `src/Uploads/Svg.php`, `src/Uploads/Avif.php`, `src/NextGen/Customizer.php`, `src/NextGen/Breadcrumbs.php`.

- [x] **Step 1: Read the four theme files** and list: the filters and priorities used; for the NextGen Customizer file the section id, setting id (`hyperpress_nggallery_photos_page`) and control id; and how the NextGen breadcrumb file detects NextGen (it imports `Imagely\NGG\DataMappers\Album` and `Gallery`).

- [x] **Step 2: Write the failing test `tests/integration/Media/MediaTest.php`**

```php
<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\Breadcrumbs as NextGenBreadcrumbs;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class MediaTest extends WP_UnitTestCase {

	public function test_svg_and_avif_mime_types_are_allowed(): void {
		$mimes = apply_filters( 'upload_mimes', array() );
		$this->assertSame( 'image/svg+xml', $mimes['svg'] ?? null );
		$this->assertSame( 'image/avif', $mimes['avif'] ?? null );
	}

	public function test_nextgen_breadcrumbs_only_hook_when_nextgen_is_present(): void {
		$hooked = (bool) has_filter( 'hyperpress_breadcrumbs_content', array( NextGenBreadcrumbs::class, 'add' ) );
		$this->assertSame( class_exists( 'Imagely\NGG\DataMappers\Album' ), $hooked );
	}

	public function test_nextgen_customizer_setting_keeps_its_id_and_the_control_matches(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNotNull( $manager->get_setting( 'hyperpress_nggallery_photos_page' ) );
		$control = $manager->get_control( 'hyperpress_nggallery_photos_page' );
		$this->assertNotNull( $control, 'Control id must equal the setting id' );
	}
}
```

Run: `docker compose run --rm php composer test:integration -- --filter MediaTest`. Expected: FAIL.

- [x] **Step 3: Create the plugin** with the rules from plugins-03-core-plugins (R1 to R5):
  - `hyperpress-media.php`: header per R1 (`Plugin Name: hyperPress Media`, real Description, no `Requires Plugins`), R2 body without `activate`.
  - `src/Plugin.php`: `boot()` calls `Uploads\Svg::register()`, `Uploads\Avif::register()`, `NextGen\Customizer::register()` and, only `if ( class_exists( 'Imagely\NGG\DataMappers\Album' ) )`, `NextGen\Breadcrumbs::register()`. The `class_exists` check stays because NextGen is a real optional third-party dependency.
  - `Uploads\Svg` and `Uploads\Avif`: the theme filters moved unchanged (hooks, priorities, bodies). The theme's SVG file contains a branch for WordPress 4.7.1 only; move it as is and record it as an audit item, do not delete it here.
  - `NextGen\Customizer`: setting id unchanged; the control id becomes the setting id; the section id becomes `hyperpress_media_nextgen` (update any control that referenced the old section).
  - `NextGen\Breadcrumbs`: `public static function add( $breadcrumbs )` hooked on `hyperpress_breadcrumbs_content` at priority 20, body unchanged; `register()` adds the filter.

- [x] **Step 4: Run**

Run: `docker compose run --rm php composer test:integration -- --filter MediaTest` PASS; `composer test:unit -- --filter 'PluginHeadersTest.*media'` PASS; `composer lint:syntax` exit 0.

- [x] **Step 5: Commit**

```bash
git add hyperpress-media tests/integration/Media/MediaTest.php
git commit -m "feat(media): add hyperpress-media (extracted from the theme)" -- hyperpress-media tests/integration/Media/MediaTest.php
```

---

## Task 8: Checkpoint: all contracts should now pass

- [x] **Step 1: Run both suites**

Run: `docker compose run --rm php composer test:unit; docker compose run --rm php composer test:integration`
Expected: everything PASSES, including `PluginHeadersTest`, `NoLegacyKeysTest`, `KeyConventionTest`, `PublicUrlTest`, `CapabilitiesTest`, `TaxonomyAttachmentTest`, `IdentifierConventionTest`, `RegistrationParityTest`. Skips are only the snapshot writer and non-public URL cases.

- [x] **Step 2: Fix stragglers**

Any failure names the file and identifier. Fix the production code (never the test), re-run, and commit each fix separately with a pathspec. Typical leftovers: a widget `id_base` or Customizer control still differing from its setting id, a stray old key the grep did not catch, a header field.

- [x] **Step 3: Flip this plan's checkboxes, add the `STATUS: COMPLETED` banner, commit the plan**

```bash
git add docs/superpowers/plans/2026-10-02-plugins-04-remaining-plugins.md
git commit -m "docs(plans): complete plugins-04-remaining-plugins" -- docs/superpowers/plans/2026-10-02-plugins-04-remaining-plugins.md
```

## Execution notes

- Final state: unit 121 tests OK; integration 147 tests OK with 4 skipped (snapshot writers and non-public URL cases); `lint:syntax` exit 0. All contract tests (keys, URLs, capabilities, attachment, identifiers, headers, legacy-key scan) are green.
- The theme's shortcodes and media code are mirrored in `hyperpress-shortcodes` (66 characterization cases, fixture captured from the theme code) and `hyperpress-media`. The theme still loads its own copies: do not deploy both with NextGen active until theme-03 removes the theme files, or album/gallery breadcrumbs are appended twice.
- The test image now has ext-intl (needed for the ordinal `suffix` path of `hyper_date_distance`); `composer.json` requires `ext-intl`.
- `content_template` and `archive_template` hooks moved with their templates to `templates/` in newsletter and sponsor (the theme `include`s the returned path). Added `NewsletterContentTemplateTest` and `SponsorContentTemplateTest`.
- Socials: the Customizer section and all 12 control ids now follow the convention. Media: the NextGen control used to sit in the Customizer section "Gallery" (`hyperpress_gallery`), which the theme created with an empty Meta Box entry (`library/metaboxes/gallery.php`). It now has its own section `hyperpress_media_nextgen` ("NextGen Gallery", priority 140, the old position), and the theme's empty "Gallery" entry is removed. (An earlier note here said the section never existed; that was wrong.)
- Sponsor labels now use `SortedSeasons::get`. The theme callers of `get_sorted_seasons` are handled in theme-02-renames-and-breadcrumbs.
- New audit candidates: SVG upload sanitizing and the dead WP 4.7.1 branch (media), unsanitized `$_SERVER['REQUEST_URI']` and unguarded array indexes in NextGen breadcrumbs, widget `update()` reading undefined keys (sponsor), `content_template` callbacks typed `string` while the theme passes a `WP_Post` on the front page (theme-05).
- NextGen breadcrumbs (2026-10-09): the body is no longer unchanged. The positional album/gallery parse looked up the literal `album` in the flat Years > galleries URL `/nggallery/album/<gallery>` and added no crumbs. `parse_path()` now returns ordered segments and `Breadcrumbs::resolve()` resolves each by lookup (see the S29 follow-up in the audit). The "do not deploy both" warning above still applies if the theme still loads its own copy.
- Test-isolation fix: `SeasonCountTest` removed the real `hyper_date_distance` shortcode in tear_down; it now restores it.
- Deferred minors: weekly/monthly/annual `time-restrict-repeat` branches are date-dependent (parity only shows stable output); several thin tests (newsletter archive/color fallback, media gating); existing data is not migrated (dev DB reset by design).
- Update (audit S20): the SVG upload filter was removed from `hyperpress-media`; SVG uploads now come from the Safe SVG plugin, declared as `Requires Plugins: safe-svg`. `Uploads\Svg` no longer exists, and `MediaTest` asserts the SVG MIME type is not added by this plugin.
- Update (audit S30): `Uploads\Avif` was removed too; WordPress core already allows and detects AVIF. `hyperpress-media` now only carries the NextGen integration.

> Note (2026-10-11): `hyperpress-sponsor` now seeds `hyper_tier` with Level 1 to Level 4 and their dollar-range descriptions (`<$500`, `$500-$4999`, `$5000-$9999`, `$10,000+`) through `TermSeeder`, on `init` priority 20 and on activation. The taxonomy is left unlocked on purpose, so tiers stay editable in the admin. WordPress stores the `<` in `<$500` as `&lt;`.

> Note (2026-10-12): all shortcode tags in `hyperpress-shortcodes` were renamed to the `hyperpress_*` prefix, with no aliases: `hyper_emphasis`, `hyper_banner`, `hyper_date_distance`, `hyper_season_count` and the `hyper_*_abbreviation` tags became `hyperpress_*`; `time-restrict`, `time-restrict-repeat` and `-repeat-1|2|3` became `hyperpress_time_restrict`, `hyperpress_time_restrict_repeat` and `hyperpress_time_restrict_repeat_1|2|3`; `raw` became `hyperpress_raw`. The test arrays were realigned and `test_the_shortcode_tags_are_still_registered` is now `test_the_shortcode_tags_are_registered`.
> Note (2026-10-12): `CaptureLegacyShortcodesTest` (Step 4) was deleted: it was a skipped snapshot generator that loaded `/theme/library/shortcodes/*.php`, which no longer exists. The fixture `legacy-shortcodes.json` and the tests that read it were kept; the fixture is now the only record of the legacy output, and its keys still use the old tags.

> Note (2026-10-13): the newsletter and sponsor banner subtitles are no longer theme mods: the `subtitle_mod` entries and the Customizer control ids `hyperpress_newsletter_banner_subtitle` / `hyperpress_sponsor_banner_subtitle` above are gone, along with both plugins' `Customizer` classes and the "Newsletter" and "Sponsors" sections. The subtitles now live on hyperPress > Newsletter and hyperPress > Sponsor (options `hyperpress_newsletter` and `hyperpress_sponsor`, same keys, defaults "Updates on Team HYPER from our Marketing Team" and "Helping Make HYPER Happen") and are read through each plugin's `Settings::banner_subtitle()`. `NewsletterCustomizerTest` was replaced by `Newsletter/NewsletterSubtitleTest`. See `docs/superpowers/plans/2026-10-13-settings-pages-banner-subtitles.md`.

- **Update (2026-10-13):** the Socials Customizer plan above no longer matches the code. The 12 `hyperpress_social_*` settings moved from theme mods to a Meta Box settings page under hyperPress > Social (option `hyperpress_social`, same keys), `Social\Customizer` and the "Socials" section are deleted, and `hyperpress-social` now has `Requires Plugins: hyperpress-utils, meta-box-aio` (it was none, with no Utils use). The Customizer-based `SocialsTest` cases were replaced by `Social/SocialSettingsPageTest` and `Social/SocialReadersTest`. See `docs/superpowers/plans/2026-10-13-settings-pages-social.md`.
- **Update (2026-10-13):** the Media notes above no longer match the code. `NextGen\Customizer`, the Customizer section `hyperpress_media_nextgen` ("NextGen Gallery") and the theme mod `hyperpress_nggallery_photos_page` are gone: the setting is the field `hyperpress_media_photos_page` on hyperPress > Media (option `hyperpress_media`), read through `NextGen\Settings::photos_page()` by `NextGen\Breadcrumbs`. `hyperpress-media` now has `Requires Plugins: hyperpress-utils, meta-box-aio, safe-svg`, and `NextGenCustomizerTest` was replaced by `Media/MediaSettingsPageTest`, `MediaSettingsTest`, `MediaReadersTest` and `NextGenBreadcrumbsTest`. See `docs/superpowers/plans/2026-10-13-settings-pages-media.md`.

> Note (2026-10-14): the `'always_set_archive_title' => true` argument in the newsletter registration above (and in the sponsor, competition, award and robot ones) was dropped; `Utils\Banner` no longer supports it. See the 2026-10-14 notes in `2026-10-02-plugins-02-shared-utils.md`.

Note (2026-10-14): the Posts page colour fallback in the singular banner (`$blog_page_id = get_option( 'page_for_posts' )` above, later `HyperPress\Season\SingularBanner`) is gone: it overrode the type colour of every plugin single. A single now uses the post's own colour, else the type colour. See `2026-10-13-plugins-competition-type-breadcrumbs.md`, Outcome "single plugin banners no longer take the Posts page colour".

- 2026-10-19: the sponsor `archive_template` override (`Archive` class and `templates/archive.php`) was removed: the stub had no loop and rendered an empty `<main>` on `/sponsors/`. The theme's archive loop now lists sponsors through the plugin's `content_template`.
