# Banner subtitle settings pages Implementation Plan

> **STATUS: ✅ CODE COMPLETE, browser checks pending** (2026-10-13). Tasks 1-5 and Task 6 step 4 are done on `feat/settings-pages`; Task 6 steps 1-3 (three settings pages, banners on the front end and Customizer sections gone, optional dev-data copy) need a person in a browser.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the banner subtitle Customizer settings of `hyperpress-newsletter`, `hyperpress-sponsor` and `hyperpress-competition` to one Meta Box settings page each under the hyperPress menu, delete their Customizer classes, and drop the theme-mod based `subtitle_mod` argument from the shared `Banner` class.

**Architecture:** Each plugin gets the same small set of classes as the Season page (`Settings`, `SettingsPage`, `MetaBoxes`), with one textarea field. The shared `HyperPress\Utils\Banner::register()` learns a `subtitle` callable argument (and a `sanitize_subtitle()` helper), each plugin passes its `Settings::banner_subtitle`, and once all three have switched the `subtitle_mod` path is removed. The banner colours stay theme mods (`hyperpress_hyper_*`, Customizer colours).

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box (stubbed in tests), PHPUnit through `docker compose run --rm php composer ...` from `plugins/`.

**Spec:** `docs/superpowers/specs/2026-10-12-settings-pages-design.md` (rows "Newsletter", "Sponsor", "Competition"). Closest model: `docs/superpowers/plans/2026-10-12-settings-pages-season.md`; earlier plans' "Outcome / execution notes" list the rulings that still apply.

## Global Constraints

- Meta Box field ids and option array keys are `hyperpress_<plugin>_<field>` (`IdentifierConventionTest`): options `hyperpress_newsletter`, `hyperpress_sponsor`, `hyperpress_competition`; fields `hyperpress_newsletter_banner_subtitle`, `hyperpress_sponsor_banner_subtitle`, `hyperpress_competition_banner_subtitle`; box ids `hyperpress_<plugin>_box`; page ids equal the option names.
- Settings pages attach with `parent => AdminMenu::SLUG`, `manage_options`, `style => no-boxes`; read with `get_option` (Meta Box is not installed in tests). No fallback reads of the old theme mods, no migration code.
- Defaults (from the old Customizer settings): newsletter `Updates on Team HYPER from our Marketing Team`, sponsor `Helping Make HYPER Happen`, competition `''`. `Settings::default_option( $fallback, $option, $passed_default )` (filter `default_option_<option>`, parameter named `$fallback`) supplies them while the option row is absent; the accessor `banner_subtitle(): string` reads `get_option( self::OPTION )` with no explicit default: option row missing gives the defaults; row present but key missing gives `''` (Meta Box drops the key of an empty field, so the editor cleared the subtitle); a non-array option or a non-string value gives the default.
- Subtitle is saved through `wp_kses_post` (the old sanitizer) and still shown through the allow-list `wp_kses` plus `do_shortcode` in `themes/hyperpress-foundation/template-parts/banner.php` (unchanged).
- Classes loaded by unit tests use the guard `defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`. `Banner` keeps its unit-testable pure `apply()`.
- Season's `Sanitizers::banner_subtitle` is left alone.
- Tooling runs in Docker only, from `plugins/` (and `themes/hyperpress-foundation/` for the theme tests): `docker compose run --rm php composer lint|test:unit|test:integration` (append `-- --filter <Name>`). Lint stays at 0; check its exit code explicitly. Never read `.env*` files.
- Commit with explicit paths: `git add <paths>` then `git commit -m "..." -- <paths>`. Tick each task's boxes in this file in the same commit as its code.

## Review Focus

- The option holds a non-array value, or the key holds a non-string: `banner_subtitle()` returns the default, no warnings.
- Hostile markup or shortcodes in the subtitle: stored through `wp_kses_post`, and the banner template still strips and runs `do_shortcode` on output.
- A season archive of the plugin's post type: `Banner::apply()` joins the subtitle with the season name (`<subtitle> in <Season>`) exactly as before when the subtitle is non-empty.
- An empty competition subtitle (the default `''`) shows no subtitle and no stray joiner on a season archive.
- Nothing else still passes or reads `subtitle_mod` after Task 5.

## File Structure

- `plugins/hyperpress-utils/src/Banner.php` (modified): `subtitle` callable argument, `sanitize_subtitle()`; Task 5 removes `subtitle_mod`.
- For each of newsletter, sponsor, competition: `src/Settings.php`, `src/SettingsPage.php` (new), `src/MetaBoxes.php` (add `boxes()`; delete any empty Customizer section-holder box), `src/Plugin.php` (wiring, `Banner::register` argument), `src/Customizer.php` deleted.

---

### Task 1: Banner accepts a subtitle callable

**Files:**
- Modify: `plugins/hyperpress-utils/src/Banner.php`
- Test: `plugins/tests/integration/Utils/BannerRegisterTest.php`, `plugins/tests/integration/Utils/BannerSanitizeTest.php` (new; `sanitize_subtitle` needs `wp_kses_post`, which the unit bootstrap does not provide)

**Interfaces:**
- Produces: `Banner::register( string $post_type, array $args )` where `$args` may carry `subtitle` (a callable returning string) instead of `subtitle_mod`; when `subtitle` is present it is called to fill `$ctx['subtitle']`, otherwise `get_theme_mod( $args['subtitle_mod'] )` as today. `Banner::sanitize_subtitle( mixed $value ): string` returns `wp_kses_post( (string) $value )` for scalars and `''` otherwise.

- [x] **Step 1: Write failing tests.** `BannerRegisterTest`: registering with `'subtitle' => static fn() => 'From callable'` and no `subtitle_mod` makes the banner of a singular post of the registered type carry that subtitle; a registered type with `subtitle_mod` still works. `BannerSanitizeTest` (integration, in `plugins/tests/integration/Utils/BannerSanitizeTest.php`, because `wp_kses_post` is not available in the unit bootstrap): `sanitize_subtitle` keeps `<em>x</em>` and removes `<script>`, returns `''` for `array( 'x' )`, `null` and an object.
- [x] **Step 2:** Run `composer test:integration -- --filter BannerSanitize` and `composer test:integration -- --filter BannerRegister`. Expected: FAIL.
- [x] **Step 3:** Implement to the interface; update the docblock of `register()`.
- [x] **Step 4:** Run the full `composer test:unit`, `test:integration` and `lint`. Expected: pass.
- [x] **Step 5:** Commit `feat(utils): let Banner take a subtitle callable`.

### Task 2: Newsletter settings page

**Files:**
- Create: `plugins/hyperpress-newsletter/src/Settings.php`, `SettingsPage.php`
- Modify: `plugins/hyperpress-newsletter/src/MetaBoxes.php` (add `boxes()` and register it), `src/Plugin.php` (boot wiring, `Banner::register` gets `'subtitle' => array( Settings::class, 'banner_subtitle' )` instead of `subtitle_mod`, no `Customizer::register()`)
- Delete: `plugins/hyperpress-newsletter/src/Customizer.php`
- Test: create `plugins/tests/integration/Newsletter/NewsletterSettingsPageTest.php`, `NewsletterSettingsTest.php`; rewrite `plugins/tests/integration/NewsletterCustomizerTest.php` (rename to `Newsletter/NewsletterSubtitleTest.php`); check `NewsletterBannerTest.php` and `NewsletterBannerFilter` tests for the theme mod

**Interfaces:**
- Produces: `Settings::OPTION = 'hyperpress_newsletter'`, `Settings::BANNER_SUBTITLE = 'hyperpress_newsletter_banner_subtitle'`, `Settings::defaults(): array`, `Settings::default_option( $fallback, $option, $passed_default )`, `Settings::banner_subtitle(): string`; `SettingsPage::ID = 'hyperpress_newsletter'`, `register()`, `pages( array ): array` (title `Newsletter`); `MetaBoxes::boxes( array ): array` with box `hyperpress_newsletter_box` and one `textarea` field (`raw` true, `sanitize_callback` `array( Banner::class, 'sanitize_subtitle' )`, label "Newsletter Banner Subtitle" and the old control's description, taken from the deleted `Customizer.php`).

- [x] **Step 1: Write failing tests.** `NewsletterSettingsPageTest`: `pages()` values; `boxes()` field id, type, `raw`, callback, `std` equal to the default text. `NewsletterSettingsTest`: nothing saved gives the default; saved value comes back; saved array without the key gives the default; non-array option and non-string value give the default; explicit default passed to `get_option` wins. `NewsletterSubtitleTest` (from the old Customizer test): the default shows on a newsletter archive/singular banner until a value is saved; a saved value replaces it; the Customizer has no setting `hyperpress_newsletter_banner_subtitle` and no section `hyperpress_newsletter`; hostile markup is removed by the sanitizer.
- [x] **Step 2:** Run `composer test:integration -- --filter Newsletter`. Expected: FAIL.
- [x] **Step 3:** Implement the classes, rewire `Plugin::boot()`, delete `Customizer.php`; migrate any other test that sets `hyperpress_newsletter_banner_subtitle` as a theme mod (grep `plugins/tests` and `themes/*/tests`) to `update_option( 'hyperpress_newsletter', array( ... ) )`.
- [x] **Step 4:** Run the full `composer test:integration`, `test:unit` and `lint` from `plugins/`. Expected: pass.
- [x] **Step 5:** Commit `feat(newsletter): move the banner subtitle to a settings page`.

### Task 3: Sponsor settings page

Same as Task 2 for `hyperpress-sponsor`: option `hyperpress_sponsor`, field `hyperpress_sponsor_banner_subtitle`, box `hyperpress_sponsor_box`, page title `Sponsor`, default `Helping Make HYPER Happen`, label "Sponsor Banner Subtitle" and description from the deleted Customizer.

**Files:**
- Create: `plugins/hyperpress-sponsor/src/Settings.php`, `SettingsPage.php`
- Modify: `plugins/hyperpress-sponsor/src/MetaBoxes.php` (add `boxes()`; delete any empty `'panel' => ''` Customizer section-holder box and keep the rest), `src/Plugin.php` (wiring; `Banner::register` gets the `subtitle` callable; no `Customizer::register()`)
- Delete: `plugins/hyperpress-sponsor/src/Customizer.php`
- Test: create `plugins/tests/integration/Sponsor/SponsorSettingsPageTest.php`, `SponsorSettingsTest.php`; modify `plugins/tests/integration/SponsorSettingsTest.php` (its banner subtitle test at ~line 37 moves into the new files; the website/description tests stay), and `SponsorWidgetTest`/banner tests if they set the theme mod

**Interfaces:** Produces the same shapes as Task 2 under `HyperPress\Sponsor`.

- [x] **Step 1: Write failing tests** mirroring Task 2 (page and box values, accessor defaults and edge cases, default shown until saved, saved value replaces it, no Customizer setting or section `hyperpress_sponsor`, box id `hyperpress_sponsor_box` present, no empty holder box).
- [x] **Step 2:** Run `composer test:integration -- --filter Sponsor`. Expected: FAIL.
- [x] **Step 3:** Implement, rewire, delete `Customizer.php`, migrate the other tests.
- [x] **Step 4:** Run the full `composer test:integration`, `test:unit` and `lint` from `plugins/`. Expected: pass.
- [x] **Step 5:** Commit `feat(sponsor): move the banner subtitle to a settings page`.

### Task 4: Competition settings page

Same for `hyperpress-competition`: option `hyperpress_competition`, field `hyperpress_competition_banner_subtitle`, box `hyperpress_competition_box`, page title `Competition`, default `''`, label "Banner subtitle" and description "Shown under the title on the competitions pages." (from the deleted Customizer). After this task the plugin has no Customizer class at all.

**Files:**
- Create: `plugins/hyperpress-competition/src/Settings.php`, `SettingsPage.php`
- Modify: `plugins/hyperpress-competition/src/MetaBoxes.php` (add `boxes()`), `src/Plugin.php` (wiring; `subtitle` callable; remove `Customizer::register()`)
- Delete: `plugins/hyperpress-competition/src/Customizer.php`
- Test: create `plugins/tests/integration/Competition/CompetitionSettingsPageTest.php`, `CompetitionSubtitleSettingTest.php`; modify `CompetitionSettingsTest.php` (the test at ~line 23 `test_banner_subtitle_setting_and_matching_control_exist` is replaced), `CompetitionMapApiKeyTest.php` (its assertion that the subtitle Customizer setting still exists becomes an assertion that the Customizer has no competition section or settings)

**Interfaces:** Same shapes as Task 2 under `HyperPress\Competition`. The existing `HyperPress\Competition\Settings` name must not collide: grep the plugin for an existing `Settings` class first and, if one exists, name the new class `SubtitleSettings` (decide and record it in the report).

- [x] **Step 1: Write failing tests** mirroring Task 2, plus: with the default `''` a competition season archive shows no subtitle and no joiner; with a saved subtitle `Banner::apply` joins `<subtitle> in <Season>` (use the existing season-archive banner test as the model).
- [x] **Step 2:** Run `composer test:integration -- --filter Competition`. Expected: FAIL.
- [x] **Step 3:** Implement, rewire, delete `Customizer.php`, migrate the other tests.
- [x] **Step 4:** Run the full `composer test:integration`, `test:unit` and `lint` from `plugins/`, and the theme's `composer test:integration` and `lint`. Expected: pass.
- [x] **Step 5:** Commit `feat(competition): move the banner subtitle to a settings page`.

### Task 5: Remove `subtitle_mod`

**Files:**
- Modify: `plugins/hyperpress-utils/src/Banner.php` (remove the `subtitle_mod` path; `subtitle` callable becomes required), `plugins/tests/unit/Utils/BannerTest.php`, `plugins/tests/integration/Utils/BannerRegisterTest.php`, `plugins/tests/integration/SeasonGuardsTest.php` (~lines 105, 131: the gizmo post type registrations)

**Interfaces:** `Banner::register()` args: `type`, `subtitle` (callable), `color_mod`, `archive_title` (required); `term_joiner`, `always_set_archive_title` optional. Grep `plugins/` for any remaining `subtitle_mod` first.

- [x] **Step 1: Write failing tests.** Update the gizmo registrations in the three test files to pass `'subtitle' => static fn() => ...` instead of `subtitle_mod`; add a test that registering without `subtitle` throws `InvalidArgumentException` (or the project's convention for a missing required arg: look at how `TaxonomyRegistrar::build` signals it and match it).
- [x] **Step 2:** Run the three test files. Expected: FAIL.
- [x] **Step 3:** Remove the `subtitle_mod` code and update the docblock.
- [x] **Step 4:** Run the full `composer test:integration`, `test:unit` and `lint` from `plugins/`, and the theme's `composer test:integration`, `test:unit` and `lint`. Expected: pass.
- [x] **Step 5:** Commit `refactor(utils): drop the subtitle_mod banner argument`.

### Task 6: Verify in a browser and close out

**Files:** this plan; completed plans that mention changed files.

- [ ] **Step 1 (manual):** hyperPress menu lists Newsletter, Sponsor and Competition. Open each: the field shows the default text (Competition empty), edit, save, reload; the value stays. Clearing the field and saving leaves the site subtitle empty (the default text shows only until the page is first saved).
- [ ] **Step 2 (manual):** the banners on a newsletter page, a sponsors page and a competition page show the saved subtitle; on a season archive of each type it reads "<subtitle> in Season N". The Customizer no longer has "Newsletter", "Sponsors" or "Competitions" sections.
- [ ] **Step 3 (dev data, optional):** after `cd docker/<site> && docker compose --profile local run --rm wpcli db export -`, copy the old values once: `wpcli eval 'foreach ( array( "newsletter", "sponsor", "competition" ) as $p ) { $k = "hyperpress_{$p}_banner_subtitle"; update_option( "hyperpress_{$p}", array( $k => get_theme_mod( $k, "" ) ) ); }'`. Old text may still use removed `[hyper_*]` shortcode tags; update them to `[hyperpress_*]`.
- [x] **Step 4:** Grep `docs/superpowers/plans/`, `specs/` and `audit/` for `hyperpress_newsletter_banner_subtitle`, `hyperpress_sponsor_banner_subtitle`, `hyperpress_competition_banner_subtitle`, `subtitle_mod`, `NewsletterCustomizerTest`; append a dated note under "Outcome / execution notes" of each completed plan whose statement became untrue; leave the rest.
- [ ] **Step 5:** Tick the boxes, add `> **STATUS: ✅ COMPLETED** (date, commit range)` at the top and commit `docs(plans): complete banner subtitle settings pages`.

## Outcome / execution notes

- Note (2026-10-13) (a): the first design returned the default when the key was missing, and the final review found that a cleared field (Meta Box drops the key of an empty field but keeps the option row) could then never be emptied. The accessors now return `''` when the option row exists without the key; the default text shows only while the option row is absent.
- Note (2026-10-13): the field descriptions were written for the new fields (the old Customizer controls had none except competition's), with the shortcode and markup wording copied from Season. They say "bold, italic", while the display allow-list is strong/em/br/span[class], so `<b>` and `<i>` are removed at display; the wording was changed in the final fix wave.
- Note (2026-10-13): no migration code. Until the optional wpcli copy (Task 6 step 3) runs on an existing site, the old theme-mod subtitles are ignored and the defaults show; the old text may contain removed `[hyper_*]` shortcode tags.
- Note (2026-10-13): `Banner::register()` now throws `InvalidArgumentException` at registration when `subtitle` is missing or not callable, so any third-party caller still passing `subtitle_mod` breaks loudly (none in this repo).
- Note (2026-10-13): test notes: the sponsor accessor test class is `SponsorSubtitleSettingTest`, to avoid clashing with the existing `SponsorSettingsTest`; competition had no season-archive banner test, so `CompetitionSubtitleTest` calls `Banner::apply` directly plus a real-filter test; filtered runs of `SeasonGuardsTest` show a known `trim(): WP_Error` order dependence (the full suite passes).
- Note (2026-10-13): deferred minors from the reviews: a few loose assertions, and the `SeasonGuardsTest` gizmo fixtures use a `get_theme_mod` closure where a literal would do.
- Note (2026-10-13): found in the owner's browser test: clearing the Newsletter or Sponsor Banner Subtitle and saving brought the default text back. Meta Box redisplays a field's `std` when the stored key is missing, and it drops the key of a cleared field, so the form showed the default and the next save wrote it back, while the accessors treated the field as cleared. A field whose default is delivered by the `default_option` filter must not also set `std`; the `std` was removed from the newsletter, sponsor and Front Page subtitle fields (competition's `std` is `''`, harmless). The tests now assert the fields have no `std` key.
