# Plugins 01: Test Toolchain and Contract Tests

> **STATUS: COMPLETED** (2026-10-04). Baseline commit: b95c565. Branch cleanup/convention-and-tests.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Build the Docker-based PHP 8.5 test toolchain for `plugins/`, snapshot the current registrations, and write header-lint and contract tests that FAIL on the current code and pass once the migration plans (plugins-02 to plugins-04) are done.

**Architecture:** Composer project at the `plugins/` root. plugins-01-test-toolchain `php` (8.5-cli) container plus a `db` (MariaDB) container run PHPUnit. plugins-01-test-toolchain `unit` suite needs no WordPress. An `integration` suite boots WordPress through `wp-phpunit` and loads all 14 plugins, with Meta Box stubbed. Shared expectations (plugin list, keys, Requires table) live in one `Convention` class so the header lint and contract tests cannot drift apart.

**Tech Stack:** PHP 8.5, Composer, PHPUnit 9.6, `wp-phpunit/wp-phpunit` ^6.9, `johnpbloch/wordpress-core` ^6.9, `yoast/phpunit-polyfills` ^2, `wp-coding-standards/wpcs`, `phpcompatibility/phpcompatibility-wp`, MariaDB 11.

**Specs:** `docs/superpowers/specs/2026-10-02-plugins-tests-and-audit-design.md` (this plan) and `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md` (the convention being tested).

**Conventions for every task**

- Run everything from `plugins/`. Commands are `docker compose run --rm php <cmd>` unless stated.
- The repo has unrelated staged deletions (`D hyperPress-award/...`). NEVER plain `git commit`. Always `git add <paths>` then `git commit -m "..." -- <paths>` so only the named paths are committed.
- The host has PHP 8.5 and docker only. Do not run composer on the host.
- "Expected FAIL" steps are part of the design: these tests encode the target convention and must fail against the current code.

---

## File Structure

| Path                                             | Responsibility                                                                             |
| ------------------------------------------------ | ------------------------------------------------------------------------------------------ |
| `Dockerfile`                                     | PHP 8.5-cli image with mysqli, git, unzip, composer                                        |
| `compose.yaml`                                   | `php` and `db` services, env for the DB                                                    |
| `composer.json`                                  | Dev dependencies, scripts (`lint`, `test:unit`, `test:integration`), PSR-4 for `tests/src` |
| `phpcs.xml.dist`                                 | WPCS + PHPCompatibilityWP ruleset (non-gating until the migration plans finish)            |
| `phpunit.xml.dist`                               | `unit` and `integration` suites                                                            |
| `.gitignore`                                     | Add `vendor/`, `.phpunit.cache/`, `.phpunit.result.cache`                                  |
| `tests/src/Support/Convention.php`               | Single source of expected plugin list, keys, Requires table, old-to-new key map            |
| `tests/src/Support/PluginHeaders.php`            | Parses the plugin header comment block from a file                                         |
| `tests/bootstrap-unit.php`                       | Composer autoload only                                                                     |
| `tests/bootstrap-integration.php`                | Loads wp-phpunit, Meta Box stub, all plugins in load order                                 |
| `tests/wp-tests-config.php`                      | WordPress test-suite constants from env                                                    |
| `tests/unit/SmokeTest.php`                       | Trivial test proving the toolchain runs                                                    |
| `tests/unit/PluginHeadersTest.php`               | Header lint across all 14 plugins                                                          |
| `tests/unit/NoLegacyKeysTest.php`                | Static scan for hard-coded old post type / taxonomy keys                                   |
| `tests/integration/SnapshotLegacyTest.php`       | Writes `tests/fixtures/legacy-registrations.json` when `HYPERPRESS_WRITE_SNAPSHOT=1`       |
| `tests/fixtures/legacy-registrations.json`       | Snapshot of current post type and taxonomy registrations                                   |
| `tests/integration/KeyConventionTest.php`        | Key prefix, length, exact set                                                              |
| `tests/integration/PublicUrlTest.php`            | Rewrite slugs and archive slugs equal the snapshot                                         |
| `tests/integration/CapabilitiesTest.php`         | Administrator and Editor hold all caps, Subscriber none                                    |
| `tests/integration/TaxonomyAttachmentTest.php`   | Taxonomy to post type attachment                                                           |
| `tests/integration/IdentifierConventionTest.php` | Meta box, customizer and widget identifiers                                                |

---

## Task 1: Docker, Composer and a passing smoke test

**Files:**

- Create: `Dockerfile`, `compose.yaml`, `composer.json`, `phpunit.xml.dist`, `tests/bootstrap-unit.php`, `tests/unit/SmokeTest.php`
- Modify: `.gitignore`

- [x] **Step 1: Write `Dockerfile`**

```dockerfile
FROM php:8.5-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip mariadb-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
```

- [x] **Step 2: Write `compose.yaml`**

```yaml
services:
  php:
    build: .
    working_dir: /app
    volumes:
      - .:/app
      - composer-cache:/root/.composer
    environment:
      WP_DB_HOST: db
      WP_DB_NAME: wordpress_test
      WP_DB_USER: root
      WP_DB_PASSWORD: root
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mariadb:11
    environment:
      MARIADB_ROOT_PASSWORD: root
      MARIADB_DATABASE: wordpress_test
    healthcheck:
      test: ['CMD', 'healthcheck.sh', '--connect', '--innodb_initialized']
      interval: 3s
      timeout: 3s
      retries: 20

volumes:
  composer-cache:
```

- [x] **Step 3: Write `composer.json`**

```json
{
  "name": "hyperpress/plugins",
  "description": "Dev tooling for the HYPERpress plugins",
  "type": "project",
  "license": "GPL-2.0-or-later",
  "require-dev": {
    "phpunit/phpunit": "^9.6",
    "yoast/phpunit-polyfills": "^2.0",
    "wp-phpunit/wp-phpunit": "^6.9",
    "johnpbloch/wordpress-core": "^6.9",
    "wp-coding-standards/wpcs": "^3.1",
    "phpcompatibility/phpcompatibility-wp": "^2.1",
    "dealerdirect/phpcodesniffer-composer-installer": "^1.0"
  },
  "autoload-dev": {
    "psr-4": {
      "HyperPress\\Tests\\": "tests/src/"
    }
  },
  "config": {
    "allow-plugins": {
      "dealerdirect/phpcodesniffer-composer-installer": true
    },
    "sort-packages": true
  },
  "scripts": {
    "lint:syntax": "find hyperpress-* tests -name '*.php' -print0 | xargs -0 -n1 php -d display_errors=stderr -l > /dev/null",
    "lint:cs": "phpcs",
    "lint": ["@lint:syntax", "@lint:cs"],
    "test:unit": "phpunit --testsuite unit",
    "test:integration": "phpunit --testsuite integration"
  }
}
```

- [x] **Step 4: Write `phpunit.xml.dist`**

Deprecations are reported but not fatal: WordPress core and wp-phpunit may emit PHP 8.5 deprecations we cannot fix. Our own code is covered by PHPCompatibilityWP in `phpcs`.

```xml
<?xml version="1.0"?>
<phpunit
    bootstrap="tests/bootstrap-unit.php"
    colors="true"
    convertDeprecationsToExceptions="false"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    cacheResultFile=".phpunit.result.cache"
>
    <testsuites>
        <testsuite name="unit">
            <directory suffix="Test.php">tests/unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory suffix="Test.php">tests/integration</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Note: the integration suite needs a different bootstrap. Task 4 adds `--bootstrap tests/bootstrap-integration.php` to the `test:integration` script. The default bootstrap stays the unit one.

- [x] **Step 5: Write `tests/bootstrap-unit.php`**

```php
<?php
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
```

- [x] **Step 6: Write the smoke test `tests/unit/SmokeTest.php`**

```php
<?php
namespace HyperPress\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase {
	public function test_runs_on_php_85(): void {
		$this->assertGreaterThanOrEqual( 80500, PHP_VERSION_ID );
	}
}
```

- [x] **Step 7: Append to `.gitignore`**

```
vendor/
.phpunit.cache/
.phpunit.result.cache
```

- [x] **Step 8: Build, install and run**

Run: `docker compose build php && docker compose run --rm php composer install`
Expected: dependencies install with no errors. If Composer reports PHPUnit 9.6 is incompatible with PHP 8.5, change `phpunit/phpunit` to `^10.5` (or `^11`), re-run `composer install`, and update `phpunit.xml.dist` with `vendor/bin/phpunit --migrate-configuration`. Record the version chosen in the commit message.

Run: `docker compose run --rm php composer test:unit`
Expected: `OK (1 test, 1 assertion)`

Run: `docker compose run --rm php composer lint:syntax`
Expected: exit code 0, no output.

- [x] **Step 9: Commit**

```bash
git add Dockerfile compose.yaml composer.json composer.lock phpunit.xml.dist .gitignore tests/bootstrap-unit.php tests/unit/SmokeTest.php
git commit -m "test: add Docker PHP 8.5 toolchain and smoke test" -- Dockerfile compose.yaml composer.json composer.lock phpunit.xml.dist .gitignore tests/bootstrap-unit.php tests/unit/SmokeTest.php
```

---

## Task 2: Coding-standards config (non-gating)

**Files:**

- Create: `phpcs.xml.dist`

- [x] **Step 1: Write `phpcs.xml.dist`**

```xml
<?xml version="1.0"?>
<ruleset name="HYPERpress plugins">
    <description>WordPress coding standards and PHP compatibility for the HYPERpress plugins.</description>

    <file>./hyperpress-award</file>
    <file>./hyperpress-competition</file>
    <file>./hyperpress-countdown</file>
    <file>./hyperpress-label</file>
    <file>./hyperpress-media</file>
    <file>./hyperpress-newsletter</file>
    <file>./hyperpress-program</file>
    <file>./hyperpress-robot</file>
    <file>./hyperpress-season</file>
    <file>./hyperpress-shortcodes</file>
    <file>./hyperpress-socials</file>
    <file>./hyperpress-sponsor</file>
    <file>./hyperpress-student</file>
    <file>./hyperpress-utils</file>

    <exclude-pattern>*/vendor/*</exclude-pattern>
    <exclude-pattern>*/node_modules/*</exclude-pattern>
    <exclude-pattern>*/assets/*</exclude-pattern>
    <exclude-pattern>*/languages/*</exclude-pattern>

    <arg name="extensions" value="php"/>
    <arg name="parallel" value="8"/>
    <arg value="sp"/>

    <config name="minimum_wp_version" value="6.9"/>
    <config name="testVersion" value="8.3-"/>

    <rule ref="WordPress"/>
    <rule ref="PHPCompatibilityWP"/>

    <!-- PSR-4 class files (migration plans) are named after the class, not class-*.php. -->
    <rule ref="WordPress.Files.FileName">
        <properties>
            <property name="strict_class_file_names" value="false"/>
        </properties>
        <exclude name="WordPress.Files.FileName.InvalidClassFileName"/>
        <exclude name="WordPress.Files.FileName.NotHyphenatedLowercase"/>
    </rule>

    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array">
                <element value="hyperpress"/>
            </property>
        </properties>
    </rule>
</ruleset>
```

- [x] **Step 2: Run phpcs to confirm the config loads**

Run: `docker compose run --rm php vendor/bin/phpcs -i`
Expected: output lists `WordPress`, `WordPress-Core`, `PHPCompatibilityWP` among installed standards.

Run: `docker compose run --rm php vendor/bin/phpcs --report=summary`
Expected: the command runs and reports violations in the current code (exit code 1 or 2). This is fine: `lint:cs` is non-gating until the migration plans finish. Record the total error count in the commit message as a baseline.

- [x] **Step 3: Commit**

```bash
git add phpcs.xml.dist
git commit -m "test: add phpcs ruleset (non-gating baseline: N errors)" -- phpcs.xml.dist
```

---

## Task 3: Shared expectations and header lint (expected FAIL)

**Files:**

- Create: `tests/src/Support/Convention.php`, `tests/src/Support/PluginHeaders.php`, `tests/unit/PluginHeadersTest.php`

- [x] **Step 1: Write `tests/src/Support/Convention.php`**

```php
<?php
namespace HyperPress\Tests\Support;

/**
 * Single source of truth for what every plugin must look like after the migration plans.
 */
final class Convention {

	public const PLUGINS = array(
		'hyperpress-award',
		'hyperpress-competition',
		'hyperpress-countdown',
		'hyperpress-label',
		'hyperpress-media',
		'hyperpress-newsletter',
		'hyperpress-program',
		'hyperpress-robot',
		'hyperpress-season',
		'hyperpress-shortcodes',
		'hyperpress-socials',
		'hyperpress-sponsor',
		'hyperpress-student',
		'hyperpress-utils',
	);

	/** Dependencies first. Utils before everything, then season/program/robot. */
	public const LOAD_ORDER = array(
		'hyperpress-utils',
		'hyperpress-season',
		'hyperpress-program',
		'hyperpress-robot',
		'hyperpress-socials',
		'hyperpress-award',
		'hyperpress-competition',
		'hyperpress-countdown',
		'hyperpress-newsletter',
		'hyperpress-sponsor',
		'hyperpress-label',
		'hyperpress-student',
		'hyperpress-shortcodes',
		'hyperpress-media',
	);

	public const REQUIRES_PHP      = '8.3';
	public const REQUIRES_AT_LEAST = '6.9';

	/** Expected `Requires Plugins` per plugin (order-insensitive). */
	public const REQUIRES_PLUGINS = array(
		'hyperpress-award'       => array( 'hyperpress-utils', 'hyperpress-season' ),
		'hyperpress-competition' => array( 'hyperpress-utils', 'hyperpress-season', 'hyperpress-robot', 'meta-box-aio' ),
		'hyperpress-countdown'   => array( 'hyperpress-utils', 'meta-box-aio' ),
		'hyperpress-newsletter'  => array( 'hyperpress-utils', 'hyperpress-season', 'meta-box-aio' ),
		'hyperpress-robot'       => array( 'hyperpress-utils', 'hyperpress-season' ),
		'hyperpress-sponsor'     => array( 'hyperpress-utils', 'hyperpress-season', 'meta-box-aio' ),
		'hyperpress-season'      => array( 'hyperpress-utils', 'meta-box-aio' ),
		'hyperpress-program'     => array( 'hyperpress-utils', 'meta-box-aio' ),
		'hyperpress-socials'     => array(),
		'hyperpress-label'       => array(),
		'hyperpress-student'     => array(),
		'hyperpress-shortcodes' => array(),
		'hyperpress-media'      => array(),
		'hyperpress-utils'       => array(),
	);

	/** New post type key => owning plugin folder. */
	public const POST_TYPES = array(
		'hyper_award'       => 'hyperpress-award',
		'hyper_competition' => 'hyperpress-competition',
		'hyper_countdown'   => 'hyperpress-countdown',
		'hyper_newsletter'  => 'hyperpress-newsletter',
		'hyper_robot'       => 'hyperpress-robot',
		'hyper_sponsor'     => 'hyperpress-sponsor',
	);

	/** New taxonomy key => owning plugin folder. */
	public const TAXONOMIES = array(
		'hyper_season'   => 'hyperpress-season',
		'hyper_program'  => 'hyperpress-program',
		'hyper_district' => 'hyperpress-competition',
		'hyper_week'     => 'hyperpress-competition',
		'hyper_tier'     => 'hyperpress-sponsor',
	);

	/** Old registered key => new registered key (post types and taxonomies). */
	public const OLD_TO_NEW = array(
		'award'       => 'hyper_award',
		'competition' => 'hyper_competition',
		'countdown'   => 'hyper_countdown',
		'newsletter'  => 'hyper_newsletter',
		'robot'       => 'hyper_robot',
		'sponsor'     => 'hyper_sponsor',
		'season'      => 'hyper_season',
		'program'     => 'hyper_program',
		'district'    => 'hyper_district',
		'week'        => 'hyper_week',
		'tier'        => 'hyper_tier',
	);

	/** Taxonomies with a fixed attachment. Season and program are theme-mod driven (default: attached to nothing). */
	public const TAXONOMY_OBJECT_TYPES = array(
		'hyper_district' => array( 'hyper_competition' ),
		'hyper_week'     => array( 'hyper_competition' ),
		'hyper_tier'     => array( 'hyper_sponsor' ),
	);

	public const THEME_DRIVEN_TAXONOMIES = array( 'hyper_season', 'hyper_program' );

	public const KEY_MAX_LENGTH = 20;

	public const IDENTIFIER_PATTERN = '/^hyperpress_[a-z0-9_]+$/';

	public static function root(): string {
		return dirname( __DIR__, 3 );
	}

	public static function main_file( string $plugin ): string {
		return self::root() . '/' . $plugin . '/' . $plugin . '.php';
	}
}
```

- [x] **Step 2: Write `tests/src/Support/PluginHeaders.php`**

```php
<?php
namespace HyperPress\Tests\Support;

final class PluginHeaders {

	/**
	 * Parse the first docblock of a plugin file into field => value.
	 *
	 * @return array<string,string>
	 */
	public static function parse( string $file ): array {
		$source = (string) file_get_contents( $file, false, null, 0, 8192 );
		if ( ! preg_match( '/\/\*(.*?)\*\//s', $source, $block ) ) {
			return array();
		}
		$fields = array();
		foreach ( preg_split( '/\R/', $block[1] ) as $line ) {
			if ( preg_match( '/^[\s*]*([A-Za-z][A-Za-z0-9 ]*?):\s*(.*?)\s*$/', $line, $m ) ) {
				$fields[ $m[1] ] = $m[2];
			}
		}
		return $fields;
	}

	/** @return string[] */
	public static function requires_plugins( array $headers ): array {
		if ( empty( $headers['Requires Plugins'] ) ) {
			return array();
		}
		$list = array_map( 'trim', explode( ',', $headers['Requires Plugins'] ) );
		sort( $list );
		return array_values( array_filter( $list ) );
	}
}
```

- [x] **Step 3: Write `tests/unit/PluginHeadersTest.php`**

```php
<?php
namespace HyperPress\Tests\Unit;

use HyperPress\Tests\Support\Convention;
use HyperPress\Tests\Support\PluginHeaders;
use PHPUnit\Framework\TestCase;

final class PluginHeadersTest extends TestCase {

	/** @return array<string,array{string}> */
	public static function plugins(): array {
		$cases = array();
		foreach ( Convention::PLUGINS as $plugin ) {
			$cases[ $plugin ] = array( $plugin );
		}
		return $cases;
	}

	/** @dataProvider plugins */
	public function test_main_file_is_named_after_folder( string $plugin ): void {
		$this->assertFileExists( Convention::main_file( $plugin ), "$plugin/$plugin.php must exist" );
	}

	/** @dataProvider plugins */
	public function test_required_fields_are_present_and_non_empty( string $plugin ): void {
		$headers = PluginHeaders::parse( Convention::main_file( $plugin ) );
		foreach ( array( 'Plugin Name', 'Description', 'Author', 'License', 'License URI', 'Text Domain', 'Domain Path' ) as $field ) {
			$this->assertNotEmpty( $headers[ $field ] ?? '', "$plugin: header '$field' missing or empty" );
		}
	}

	/** @dataProvider plugins */
	public function test_no_github_updater_header( string $plugin ): void {
		$headers = PluginHeaders::parse( Convention::main_file( $plugin ) );
		$this->assertArrayNotHasKey( 'GitHub Plugin URI', $headers, "$plugin: GitHub Plugin URI must be removed" );
	}

	/** @dataProvider plugins */
	public function test_php_and_wordpress_floors( string $plugin ): void {
		$headers = PluginHeaders::parse( Convention::main_file( $plugin ) );
		$this->assertSame( Convention::REQUIRES_PHP, $headers['Requires PHP'] ?? null, "$plugin: Requires PHP" );
		$this->assertSame( Convention::REQUIRES_AT_LEAST, $headers['Requires at least'] ?? null, "$plugin: Requires at least" );
	}

	/** @dataProvider plugins */
	public function test_requires_plugins_matches_table( string $plugin ): void {
		$headers  = PluginHeaders::parse( Convention::main_file( $plugin ) );
		$expected = Convention::REQUIRES_PLUGINS[ $plugin ];
		sort( $expected );
		$this->assertSame( $expected, PluginHeaders::requires_plugins( $headers ), "$plugin: Requires Plugins" );
	}

	public function test_requires_table_covers_every_plugin(): void {
		$this->assertEqualsCanonicalizing( Convention::PLUGINS, array_keys( Convention::REQUIRES_PLUGINS ) );
		$this->assertEqualsCanonicalizing( Convention::PLUGINS, Convention::LOAD_ORDER );
	}
}
```

- [x] **Step 4: Run and confirm the expected failures**

Run: `docker compose run --rm php composer test:unit`
Expected: `SmokeTest` passes, `test_requires_table_covers_every_plugin` passes, and the per-plugin tests FAIL (main files are still `award.php` etc., `GitHub Plugin URI` present, Requires Plugins absent). Save the failure count for the commit message.

- [x] **Step 5: Commit**

```bash
git add tests/src tests/unit/PluginHeadersTest.php
git commit -m "test: add header lint and shared Convention expectations (failing until the migrations)" -- tests/src tests/unit/PluginHeadersTest.php
```

---

## Task 4: Integration bootstrap (WordPress, Meta Box stub, all plugins)

**Files:**

- Create: `tests/wp-tests-config.php`, `tests/bootstrap-integration.php`, `tests/integration/BootTest.php`
- Modify: `composer.json` (script), `phpunit.xml.dist` (per-suite note only, no change needed if script passes `--bootstrap`)

- [x] **Step 1: Write `tests/wp-tests-config.php`**

```php
<?php
// Constants for the WordPress test suite. Values come from the compose.yaml environment.
define( 'ABSPATH', dirname( __DIR__ ) . '/vendor/johnpbloch/wordpress-core/' );

define( 'DB_NAME', getenv( 'WP_DB_NAME' ) ?: 'wordpress_test' );
define( 'DB_USER', getenv( 'WP_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_DB_PASSWORD' ) ?: 'root' );
define( 'DB_HOST', getenv( 'WP_DB_HOST' ) ?: 'db' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'HYPERpress Tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );

$table_prefix = 'wptests_';
```

- [x] **Step 2: Write `tests/bootstrap-integration.php`**

Main files are discovered by header, not by name, so the same bootstrap works before and after the main-file rename.

```php
<?php
use HyperPress\Tests\Support\Convention;

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );
define( 'WP_PHPUNIT__TESTS_CONFIG', __DIR__ . '/wp-tests-config.php' );

$hyperpress_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';

require_once $hyperpress_tests_dir . '/includes/functions.php';

/**
 * Meta Box is not installed in tests. Stub the one function plugins call.
 * Tests inspect the `rwmb_meta_boxes` filter output directly.
 */
if ( ! function_exists( 'rwmb_meta' ) ) {
	function rwmb_meta( $key, $args = array(), $post_id = null ) { // phpcs:ignore
		// Tests set $GLOBALS['hyperpress_test_rwmb'][ $key ] to simulate stored meta.
		return $GLOBALS['hyperpress_test_rwmb'][ $key ] ?? '';
	}
}

/**
 * Find a plugin's main file: the root-level PHP file carrying a "Plugin Name:" header.
 * Works before and after the main file is renamed to <folder>.php.
 */
function hyperpress_tests_find_main_file( string $plugin ): string {
	$dir       = Convention::root() . '/' . $plugin;
	$canonical = $dir . '/' . $plugin . '.php';
	if ( is_file( $canonical ) ) {
		return $canonical;
	}
	foreach ( glob( $dir . '/*.php' ) as $file ) {
		if ( false !== strpos( (string) file_get_contents( $file, false, null, 0, 4096 ), 'Plugin Name:' ) ) {
			return $file;
		}
	}
	throw new RuntimeException( "No main file found for $plugin" );
}

/**
 * WordPress only computes `rewrite` args when a permalink structure exists (or in admin).
 * The test suite has none by default, so every post type would report rewrite === false.
 * Force pretty permalinks before init so URL contracts are meaningful.
 */
tests_add_filter(
	'pre_option_permalink_structure',
	static function (): string {
		return '/%postname%/';
	}
);

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		foreach ( Convention::LOAD_ORDER as $plugin ) {
			// hyperpress-shortcodes and hyperpress-media are created later (plugins-04-remaining-plugins); skip folders that do not exist yet.
			if ( ! is_dir( Convention::root() . '/' . $plugin ) ) {
				continue;
			}
			require_once hyperpress_tests_find_main_file( $plugin );
		}
	}
);

require $hyperpress_tests_dir . '/includes/bootstrap.php';
```

- [x] **Step 3: Write `tests/integration/BootTest.php`**

```php
<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class BootTest extends WP_UnitTestCase {
	public function test_wordpress_boots_with_plugins_loaded(): void {
		$this->assertTrue( did_action( 'init' ) > 0 );
		$this->assertTrue( function_exists( 'rwmb_meta' ) );
	}
}
```

- [x] **Step 4: Point the integration script at its bootstrap**

In `composer.json` change:

```json
"test:integration": "phpunit --testsuite integration --bootstrap tests/bootstrap-integration.php"
```

- [x] **Step 5: Run**

Run: `docker compose run --rm php composer test:integration`
Expected: WordPress installs into the test DB, `OK (1 test, 2 assertions)`. If the WordPress test-suite bootstrap complains about a missing `WP_TESTS_CONFIG_FILE_PATH`, confirm the `define` lines above run before the `require` of `includes/bootstrap.php`. If any plugin fatals while loading, note which plugin and why in the commit message and stop: the migration plans must handle it, but the toolchain has to boot first. (plugins-01-test-toolchain fatal from a `function_exists` or load-order issue can be fixed in `LOAD_ORDER`.)

- [x] **Step 6: Commit**

```bash
git add tests/wp-tests-config.php tests/bootstrap-integration.php tests/integration/BootTest.php composer.json
git commit -m "test: boot WordPress with all plugins in the integration suite" -- tests/wp-tests-config.php tests/bootstrap-integration.php tests/integration/BootTest.php composer.json
```

---

## Task 5: Snapshot the current registrations

Must run BEFORE any migration-plan change. The snapshot is the reference for URL parity and attachment checks.

**Files:**

- Create: `tests/integration/SnapshotLegacyTest.php`, `tests/fixtures/legacy-registrations.json`

- [x] **Step 1: Write `tests/integration/SnapshotLegacyTest.php`**

The test is a no-op unless `HYPERPRESS_WRITE_SNAPSHOT=1`, so normal runs never rewrite the fixture.

```php
<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class SnapshotLegacyTest extends WP_UnitTestCase {

	public const FIXTURE = __DIR__ . '/../fixtures/legacy-registrations.json';

	public function test_write_snapshot_of_current_registrations(): void {
		if ( '1' !== getenv( 'HYPERPRESS_WRITE_SNAPSHOT' ) ) {
			$this->markTestSkipped( 'Set HYPERPRESS_WRITE_SNAPSHOT=1 to regenerate (pre-Plan-B code only).' );
		}

		$snapshot = array(
			'post_types' => array(),
			'taxonomies' => array(),
		);

		foreach ( get_post_types( array( '_builtin' => false ), 'objects' ) as $key => $pt ) {
			$snapshot['post_types'][ $key ] = array(
				'public'              => $pt->public,
				'publicly_queryable'  => $pt->publicly_queryable,
				'hierarchical'        => $pt->hierarchical,
				'exclude_from_search' => $pt->exclude_from_search,
				'show_in_rest'        => $pt->show_in_rest,
				'has_archive'         => $pt->has_archive,
				'rewrite'             => $pt->rewrite,
				'query_var'           => $pt->query_var,
				'menu_position'       => $pt->menu_position,
				'menu_icon'           => $pt->menu_icon,
				'description'         => $pt->description,
				'supports'            => array_keys( get_all_post_type_supports( $key ) ),
				'labels'              => (array) $pt->labels,
			);
		}

		foreach ( get_taxonomies( array( '_builtin' => false ), 'objects' ) as $key => $tax ) {
			$snapshot['taxonomies'][ $key ] = array(
				'object_type'        => $tax->object_type,
				'public'             => $tax->public,
				'publicly_queryable' => $tax->publicly_queryable,
				'hierarchical'       => $tax->hierarchical,
				'show_in_rest'       => $tax->show_in_rest,
				'show_tagcloud'      => $tax->show_tagcloud,
				'show_in_quick_edit' => $tax->show_in_quick_edit,
				'show_admin_column'  => $tax->show_admin_column,
				'rewrite'            => $tax->rewrite,
				'query_var'          => $tax->query_var,
				'cap'                => (array) $tax->cap,
				'labels'             => (array) $tax->labels,
			);
		}

		ksort( $snapshot['post_types'] );
		ksort( $snapshot['taxonomies'] );

		$this->assertEqualsCanonicalizing(
			array_keys( Convention::OLD_TO_NEW ),
			array_merge( array_keys( $snapshot['post_types'] ), array_keys( $snapshot['taxonomies'] ) ),
			'Snapshot keys differ from Convention::OLD_TO_NEW. Fix OLD_TO_NEW to match the real legacy keys.'
		);

		file_put_contents( self::FIXTURE, wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
		$this->assertFileExists( self::FIXTURE );
	}
}
```

- [x] **Step 2: Generate the snapshot**

Run: `docker compose run --rm -e HYPERPRESS_WRITE_SNAPSHOT=1 php composer test:integration -- --filter SnapshotLegacyTest`
Expected: PASS and `tests/fixtures/legacy-registrations.json` exists.

If the key-set assertion fails, the message shows the real legacy keys. Correct `Convention::OLD_TO_NEW` (and `POST_TYPES`/`TAXONOMIES` if a plugin registers something unexpected) and re-run until it passes. Do not edit the assertion.

- [x] **Step 3: Read the fixture and sanity check it**

Run: `docker compose run --rm php php -r 'echo json_encode(array_keys(json_decode(file_get_contents("tests/fixtures/legacy-registrations.json"), true)["post_types"])), "\n";'`
Expected: a list of the six legacy post type keys. Open the file and confirm `has_archive` and `rewrite.slug` are populated for each public post type and that `season`'s and `program`'s `object_type` is `[]` (verified in source: `get_theme_mod( 'hyperpress_season_post_types', [] )`). If either is anything else, add that to the commit message and stop: the specs assume `[]`.

- [x] **Step 4: Re-run without the env var to confirm it skips**

Run: `docker compose run --rm php composer test:integration -- --filter SnapshotLegacyTest`
Expected: 1 skipped, fixture unchanged (`git status --short tests/fixtures` shows nothing new beyond the untracked file).

- [x] **Step 5: Commit**

```bash
git add tests/integration/SnapshotLegacyTest.php tests/fixtures/legacy-registrations.json
git commit -m "test: snapshot legacy post type and taxonomy registrations" -- tests/integration/SnapshotLegacyTest.php tests/fixtures/legacy-registrations.json
```

---

## Task 6: Key convention and public URL contracts (expected FAIL)

**Files:**

- Create: `tests/integration/KeyConventionTest.php`, `tests/integration/PublicUrlTest.php`

- [x] **Step 1: Write `tests/integration/KeyConventionTest.php`**

```php
<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class KeyConventionTest extends WP_UnitTestCase {

	public function test_registered_post_types_are_exactly_the_expected_set(): void {
		$actual = array_keys( get_post_types( array( '_builtin' => false ) ) );
		$this->assertEqualsCanonicalizing( array_keys( Convention::POST_TYPES ), $actual );
	}

	public function test_registered_taxonomies_are_exactly_the_expected_set(): void {
		$actual = array_keys( get_taxonomies( array( '_builtin' => false ) ) );
		$this->assertEqualsCanonicalizing( array_keys( Convention::TAXONOMIES ), $actual );
	}

	public function test_keys_follow_prefix_and_length_rules(): void {
		$keys = array_merge( array_keys( Convention::POST_TYPES ), array_keys( Convention::TAXONOMIES ) );
		foreach ( $keys as $key ) {
			$this->assertMatchesRegularExpression( '/^hyper_[a-z0-9_]+$/', $key, "$key format" );
			$this->assertLessThanOrEqual( Convention::KEY_MAX_LENGTH, strlen( $key ), "$key length" );
		}
	}
}
```

- [x] **Step 2: Write `tests/integration/PublicUrlTest.php`**

Public URLs must not change. Compare the new registration to the snapshot through `OLD_TO_NEW`.

```php
<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class PublicUrlTest extends WP_UnitTestCase {

	private static function snapshot(): array {
		return json_decode( (string) file_get_contents( SnapshotLegacyTest::FIXTURE ), true );
	}

	public static function post_types(): array {
		$cases = array();
		foreach ( self::snapshot()['post_types'] as $old => $_ ) {
			$cases[ $old ] = array( $old, Convention::OLD_TO_NEW[ $old ] );
		}
		return $cases;
	}

	public static function taxonomies(): array {
		$cases = array();
		foreach ( self::snapshot()['taxonomies'] as $old => $_ ) {
			$cases[ $old ] = array( $old, Convention::OLD_TO_NEW[ $old ] );
		}
		return $cases;
	}

	/** @dataProvider post_types */
	public function test_post_type_urls_unchanged( string $old, string $new ): void {
		$legacy = self::snapshot()['post_types'][ $old ];
		if ( ! $legacy['publicly_queryable'] ) {
			$this->markTestSkipped( "$old has no public URLs" );
		}
		$pt = get_post_type_object( $new );

		$this->assertNotNull( $pt, "$new is not registered" );
		$this->assertSame( $legacy['has_archive'], $pt->has_archive, "$new archive slug" );
		$this->assertSame( $legacy['rewrite']['slug'] ?? null, $pt->rewrite['slug'] ?? null, "$new rewrite slug" );
		$this->assertSame( $legacy['public'], $pt->public, "$new public" );
		$this->assertSame( $legacy['publicly_queryable'], $pt->publicly_queryable, "$new publicly_queryable" );
	}

	/** @dataProvider taxonomies */
	public function test_taxonomy_urls_unchanged( string $old, string $new ): void {
		$legacy = self::snapshot()['taxonomies'][ $old ];
		if ( ! $legacy['publicly_queryable'] ) {
			$this->markTestSkipped( "$old has no public URLs" );
		}
		$tax = get_taxonomy( $new );

		$this->assertNotFalse( $tax, "$new is not registered" );
		$this->assertSame( $legacy['rewrite']['slug'] ?? null, $tax->rewrite['slug'] ?? null, "$new rewrite slug" );
		$this->assertSame( $legacy['hierarchical'], $tax->hierarchical, "$new hierarchical" );
	}
}
```

- [x] **Step 3: Run and confirm expected failures**

Run: `docker compose run --rm php composer test:integration -- --filter 'KeyConventionTest|PublicUrlTest'`
Expected: FAIL. The key tests fail because old keys are registered; the URL tests fail with "hyper_* is not registered". Non-public types (countdown, the tier taxonomy) are reported as skipped, not failed. Also confirm in the fixture that `rewrite` is an array (not `false`) for `award`: if it is `false`, the permalink filter from Task 4 is not applied before `init` and must be fixed first.

- [x] **Step 4: Commit**

```bash
git add tests/integration/KeyConventionTest.php tests/integration/PublicUrlTest.php
git commit -m "test: add key convention and public URL contracts (failing until the migrations)" -- tests/integration/KeyConventionTest.php tests/integration/PublicUrlTest.php
```

---

## Task 7: Capabilities and taxonomy attachment contracts (expected FAIL)

**Files:**

- Create: `tests/integration/CapabilitiesTest.php`, `tests/integration/TaxonomyAttachmentTest.php`

- [x] **Step 1: Write `tests/integration/CapabilitiesTest.php`**

The capability set comes from the registered post type object, so the test does not hard-code names. Granting happens on the version-gated `init` check (plugins-02-shared-utils), which has already fired by the time the test runs.

```php
<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class CapabilitiesTest extends WP_UnitTestCase {

	public static function post_types(): array {
		$cases = array();
		foreach ( array_keys( Convention::POST_TYPES ) as $key ) {
			$cases[ $key ] = array( $key );
		}
		return $cases;
	}

	/** @return string[] Primitive capabilities that are not meta caps. */
	private function primitive_caps( string $post_type ): array {
		$pt = get_post_type_object( $post_type );
		$this->assertNotNull( $pt, "$post_type is not registered" );
		$caps = (array) $pt->cap;
		// Meta caps are mapped, not stored on roles.
		unset( $caps['edit_post'], $caps['read_post'], $caps['delete_post'] );
		return array_values( array_unique( $caps ) );
	}

	/** @dataProvider post_types */
	public function test_capabilities_are_specific_to_the_post_type( string $post_type ): void {
		$pt = get_post_type_object( $post_type );
		$this->assertNotNull( $pt, "$post_type is not registered" );
		$this->assertTrue( $pt->map_meta_cap, "$post_type must set map_meta_cap" );
		$this->assertSame( "edit_{$post_type}s", $pt->cap->edit_posts, "$post_type edit_posts cap" );
	}

	/** @dataProvider post_types */
	public function test_administrator_and_editor_hold_every_capability( string $post_type ): void {
		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			foreach ( $this->primitive_caps( $post_type ) as $cap ) {
				$this->assertTrue( $role->has_cap( $cap ), "$role_name lacks $cap for $post_type" );
			}
		}
	}

	/** @dataProvider post_types */
	public function test_subscriber_holds_none( string $post_type ): void {
		$role = get_role( 'subscriber' );
		foreach ( $this->primitive_caps( $post_type ) as $cap ) {
			if ( 'read' === $cap ) {
				continue;
			}
			$this->assertFalse( $role->has_cap( $cap ), "subscriber must not hold $cap for $post_type" );
		}
	}
}
```

Note: `edit_{$post_type}s` assumes the plural is the key plus `s`. the migration plans define `capability_type => array( 'hyper_award', 'hyper_awards' )`, which satisfies this for all six keys. If a plan task picks a different plural, update this assertion in the same commit.

- [x] **Step 2: Write `tests/integration/TaxonomyAttachmentTest.php`**

```php
<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class TaxonomyAttachmentTest extends WP_UnitTestCase {

	public static function fixed(): array {
		$cases = array();
		foreach ( Convention::TAXONOMY_OBJECT_TYPES as $taxonomy => $types ) {
			$cases[ $taxonomy ] = array( $taxonomy, $types );
		}
		return $cases;
	}

	/** @dataProvider fixed */
	public function test_fixed_attachment( string $taxonomy, array $expected ): void {
		$tax = get_taxonomy( $taxonomy );
		$this->assertNotFalse( $tax, "$taxonomy is not registered" );
		$this->assertEqualsCanonicalizing( $expected, $tax->object_type, "$taxonomy object types" );
	}

	public function test_theme_driven_taxonomies_attach_to_nothing_by_default(): void {
		foreach ( Convention::THEME_DRIVEN_TAXONOMIES as $taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			$this->assertNotFalse( $tax, "$taxonomy is not registered" );
			$this->assertSame( array(), $tax->object_type, "$taxonomy must not attach to any post type (incl. 'post') by default" );
		}
	}
}
```

- [x] **Step 3: Run and confirm expected failures**

Run: `docker compose run --rm php composer test:integration -- --filter 'CapabilitiesTest|TaxonomyAttachmentTest'`
Expected: FAIL ("hyper_* is not registered").

- [x] **Step 4: Commit**

```bash
git add tests/integration/CapabilitiesTest.php tests/integration/TaxonomyAttachmentTest.php
git commit -m "test: add capability and taxonomy attachment contracts (failing until the migrations)" -- tests/integration/CapabilitiesTest.php tests/integration/TaxonomyAttachmentTest.php
```

---

## Task 8: Identifier convention and legacy-key scan

**Files:**

- Create: `tests/integration/IdentifierConventionTest.php`, `tests/unit/NoLegacyKeysTest.php`

- [x] **Step 1: Write `tests/integration/IdentifierConventionTest.php`**

Covers Meta Box ids (through the `rwmb_meta_boxes` filter), customizer settings/controls/sections/panels, and widget id bases.

```php
<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class IdentifierConventionTest extends WP_UnitTestCase {

	private function assert_identifier( string $id, string $context ): void {
		$this->assertMatchesRegularExpression( Convention::IDENTIFIER_PATTERN, $id, "$context: '$id'" );
	}

	public function test_meta_box_and_field_ids(): void {
		$boxes = apply_filters( 'rwmb_meta_boxes', array() );
		$this->assertNotEmpty( $boxes, 'No meta boxes were registered through rwmb_meta_boxes' );

		foreach ( $boxes as $box ) {
			$this->assert_identifier( (string) ( $box['id'] ?? '' ), 'meta box id' );
			foreach ( $box['fields'] ?? array() as $field ) {
				$this->assert_identifier( (string) ( $field['id'] ?? '' ), 'meta field id in ' . $box['id'] );
			}
			foreach ( (array) ( $box['post_types'] ?? array() ) as $post_type ) {
				$this->assertArrayHasKey( $post_type, Convention::POST_TYPES + array( 'post' => '', 'page' => '' ), "box {$box['id']} targets '$post_type'" );
			}
		}
	}

	public function test_customizer_identifiers(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		// Every HYPERpress setting already carries the hyperpress_ prefix in the legacy code, so that
		// prefix identifies "ours" both before and after the migration. Core settings never match.
		$our_settings = array_filter(
			$manager->settings(),
			static fn( $s ) => str_starts_with( $s->id, 'hyperpress_' )
		);
		$this->assertNotEmpty( $our_settings, 'No HYPERpress customizer settings were registered' );

		foreach ( $our_settings as $setting ) {
			$this->assert_identifier( $setting->id, 'customizer setting' );
		}

		// Controls bound to one of our settings: id must equal the setting id, section must follow the
		// convention unless it is a WordPress core section.
		$core_sections = array( 'colors', 'title_tagline', 'header_image', 'background_image', 'static_front_page', 'custom_css' );
		$used          = array();
		foreach ( $manager->controls() as $control ) {
			$setting_id = isset( $control->settings['default'] ) ? $control->settings['default']->id : '';
			if ( ! isset( $our_settings[ $setting_id ] ) ) {
				continue;
			}
			$this->assertSame( $setting_id, $control->id, "control '{$control->id}' must equal its setting id '$setting_id'" );
			$used[ $control->section ] = true;
		}
		foreach ( array_keys( $used ) as $section_id ) {
			if ( ! in_array( $section_id, $core_sections, true ) ) {
				$this->assert_identifier( (string) $section_id, 'customizer section used by a HYPERpress control' );
			}
		}
		foreach ( $manager->panels() as $panel ) {
			if ( str_starts_with( $panel->id, 'hyperpress' ) ) {
				$this->assert_identifier( $panel->id, 'customizer panel' );
			}
		}
	}

	public function test_widget_id_bases(): void {
		global $wp_widget_factory;
		$ours = 0;
		foreach ( $wp_widget_factory->widgets as $class => $widget ) {
			if ( ! str_starts_with( $class, 'HyperPress' ) && ! str_starts_with( $class, 'HYPER_Press' ) ) {
				continue;
			}
			++$ours;
			$this->assert_identifier( $widget->id_base, "widget $class" );
		}
		$this->assertGreaterThan( 0, $ours, 'No HYPERpress widgets found' );
	}

	public function test_script_and_style_handles(): void {
		do_action( 'wp_enqueue_scripts' );
		foreach ( array( wp_scripts(), wp_styles() ) as $registry ) {
			$this->assertArrayNotHasKey( 'timecircles', $registry->registered, 'Handle "timecircles" must be renamed to hyperpress-countdown-timecircles' );
		}
	}
}
```

Caveat: the control/setting matching is intentionally strict. If this test errors on the customizer internals rather than on the convention (for example a control with several settings), fix the test logic in this task, not the convention. The widget and script-handle assertions are simpler and should be exact.

- [x] **Step 2: Write `tests/unit/NoLegacyKeysTest.php`**

plugins-01-test-toolchain line is flagged when it uses a registration or lookup function and contains a quoted legacy key. Slug lines (`'slug' => 'award'`) do not match the function list, so public URLs are untouched.

```php
<?php
namespace HyperPress\Tests\Unit;

use HyperPress\Tests\Support\Convention;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class NoLegacyKeysTest extends TestCase {

	private const CONTEXT = '/(register_post_type|register_taxonomy|is_singular|is_post_type_archive|is_tax|get_post_type|get_terms|wp_get_object_terms|get_the_terms|post_types|\'post_type\'|\'taxonomy\'|object_type|WP_Query)/';

	public function test_no_hard_coded_legacy_keys(): void {
		$old   = array_map( 'preg_quote', array_keys( Convention::OLD_TO_NEW ) );
		$quote = '/[\'"](' . implode( '|', $old ) . ')[\'"]/';

		$offenders = array();
		foreach ( Convention::PLUGINS as $plugin ) {
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( Convention::root() . '/' . $plugin, RecursiveDirectoryIterator::SKIP_DOTS ) );
			foreach ( $iterator as $file ) {
				if ( 'php' !== $file->getExtension() || str_contains( $file->getPathname(), '/vendor/' ) ) {
					continue;
				}
				foreach ( file( $file->getPathname() ) as $n => $line ) {
					if ( preg_match( self::CONTEXT, $line ) && preg_match( $quote, $line ) ) {
						$offenders[] = str_replace( Convention::root() . '/', '', $file->getPathname() ) . ':' . ( $n + 1 ) . '  ' . trim( $line );
					}
				}
			}
		}

		$this->assertSame( array(), $offenders, "Legacy keys still hard-coded:\n" . implode( "\n", $offenders ) );
	}
}
```

- [x] **Step 3: Run both**

Run: `docker compose run --rm php composer test:unit -- --filter NoLegacyKeysTest`
Expected: FAIL, listing offending `file:line` entries. Skim the list: any entry that is a genuine false positive (for example the WordPress built-in `'post'`/`'page'` are not in the old-key set, so they cannot appear). If a line is a real false positive, tighten `CONTEXT` rather than adding an allowlist.

Run: `docker compose run --rm php composer test:integration -- --filter IdentifierConventionTest`
Expected: FAIL on the old ids (`competition-information`, `tier`, `timecircles`, and the like). If a test instead ERRORS (exception, not an assertion), fix the test mechanics in this task until it fails only for convention reasons.

- [x] **Step 4: Commit**

```bash
git add tests/integration/IdentifierConventionTest.php tests/unit/NoLegacyKeysTest.php
git commit -m "test: add identifier convention and legacy-key scan (failing until the migrations)" -- tests/integration/IdentifierConventionTest.php tests/unit/NoLegacyKeysTest.php
```

---

## Task 9: Baseline run and handoff record

**Files:** none created.

- [x] **Step 1: Run both suites and record the baseline**

Run: `docker compose run --rm php composer test:unit; docker compose run --rm php composer test:integration`
Expected: failures ONLY in the contract and header tests from Tasks 3, 6, 7 and 8. `SmokeTest`, `BootTest`, `test_requires_table_covers_every_plugin` and the snapshot skip pass. Any failure outside that set is a tooling defect to fix before plugins-02-shared-utils starts.

- [x] **Step 2: Confirm the gating commands**

Run: `docker compose run --rm php composer lint:syntax`
Expected: exit 0.

- [x] **Step 3: Note the baseline at the bottom of this plan**

Append a short "Execution notes" section recording: PHPUnit version actually used, failing-test counts per suite, the phpcs baseline error count, and any legacy keys or season `object_type` surprises found in Task 5. Flip all checkboxes above and add the banner `> **STATUS: COMPLETED** (date). Baseline commit: <hash>.`

- [x] **Step 4: Commit**

```bash
git add docs/superpowers/plans/2026-10-02-plugins-01-test-toolchain.md
git commit -m "docs(plans): record plugins-01-test-toolchain execution notes" -- docs/superpowers/plans/2026-10-02-plugins-01-test-toolchain.md
```

---

## Self-review against the spec

| Spec 2 requirement                                                                   | Task                                        |
| ------------------------------------------------------------------------------------ | ------------------------------------------- |
| Docker php (8.5) + db, composer, phpunit, wpcs, phpcompat                            | 1, 2                                        |
| `php -l` gates from the start, phpcs non-gating                                      | 1, 2                                        |
| Unit bootstrap / integration bootstrap, Meta Box stubbed                             | 1, 4                                        |
| Deprecations from vendor code must not fail the run                                  | 1 (`convertDeprecationsToExceptions=false`) |
| Header lint (no GitHub URI, fields, Requires Plugins exact, main file name)          | 3                                           |
| Contracts: keys, URLs, capabilities, attachment, identifiers, no hard-coded old keys | 6, 7, 8                                     |
| Contracts fail on current code                                                       | 3, 6, 7, 8                                  |

Not in this plan, by design:

- Registrar, capability, breadcrumb and banner **unit/behavior tests** need the Utils classes, so they are written test-first inside plugins-02-shared-utils.
- The "`Requires Plugins` equals the table" check is in the header lint (Task 3).
- Audit tests belong to plugins-06-audit.

## Execution notes

- PHPUnit 9.6.37 works on PHP 8.5 (no fallback needed). Compose file is `compose.yaml`. WordPress core is at `vendor/johnpbloch/wordpress-core/` (the installer is not a dependency); the dead composer `extra` config was removed.
- Baseline, unit suite: 73 tests, 2 pass (SmokeTest, `test_requires_table_covers_every_plugin`), 71 red by design (56 errors because the main files do not exist under the new names yet, 15 failures).
- Baseline, integration suite: 42 tests, 36 red by design, 3 skipped (snapshot writer, non-public URL cases), the rest pass (BootTest and constants-only checks).
- `composer lint:syntax`: exit 0. phpcs baseline: 1465 errors, 27 warnings (non-gating). The `hyperpress-media` and `hyperpress-shortcodes` `<file>` entries are left out of `phpcs.xml.dist` until those plugins exist (add them in plugins-04).
- Snapshot findings: `season` and `program` `object_type` is `[]`; `tier` rewrite has a stray `"0"` key and `with_front` true (compare rewrite by key); countdown supports `slug`.
- Test-code fixes made while implementing: `PublicUrlTest` has its own `FIXTURE` constant; `NoLegacyKeysTest` is comment-blind and skips missing plugin dirs; id-less heading/divider/custom_html/button Meta Box fields are skipped.
- Deferred minors: `Convention.php` array arrow alignment may trip phpcs; `BootTest` is weak; the multi-line legacy-key path has no positive test.

> Note (2026-10-11): a sixth taxonomy, `hyper_competition_type` (owned by `hyperpress-competition`, attached to `hyper_competition`), now exists, so `Convention::TAXONOMIES` and `Convention::TAXONOMY_OBJECT_TYPES` list it too; the five-taxonomy lists above are the original state.

> Note (2026-10-12): PHPCompatibility was upgraded from 9.3.5 / phpcompatibility-wp 2.1.8 to `php-compatibility` 10.0.0-alpha2, `phpcompatibility-wp` 3.0.0-alpha2 and `phpcompatibility-paragonie` 2.0.0-alpha2 (pinned `@alpha`, the only line that understands PHP 8.x syntax). The 9.x sniffs reported nothing for PHP 8.0+ features, so `testVersion` `8.3-` never actually guarded the floor. Running `--runtime-set testVersion 7.4-7.4` now flags union types, `mixed` and the bare `catch`; `8.0-8.0` is clean. The code's real minimum is PHP 8.0; "Requires PHP 8.3" stays as a deliberate policy floor.

> Note (2026-10-12): `hyper_season` was removed from `Convention::THEME_DRIVEN_TAXONOMIES` (now only `hyper_program`), because season's post types come from the Season settings page and default to `array( 'post' )`. So `hyper_season` attaches to `post` by default, and `test_theme_driven_taxonomies_attach_to_nothing_by_default` no longer applies to it (`TaxonomyAttachmentTest` covers the new default). See `docs/superpowers/plans/2026-10-12-settings-pages-season.md`.

> Note (2026-10-12): `Convention::THEME_DRIVEN_TAXONOMIES` was removed entirely: `hyper_program` was its last entry, and program's post types now come from the Program settings page with the default `array( 'post' )`. The constant, the loop in `TaxonomyAttachmentTest` that used it and `test_theme_driven_taxonomies_attach_to_nothing_by_default` no longer exist; `TaxonomyAttachmentTest` covers the new default. See `docs/superpowers/plans/2026-10-12-settings-pages-program.md`.
