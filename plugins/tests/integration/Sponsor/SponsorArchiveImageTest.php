<?php
namespace HyperPress\Tests\Integration\Sponsor;

use HyperPress\Sponsor\Plugin;
use HyperPress\Sponsor\Settings;
use HyperPress\Sponsor\SettingsPage;
use HyperPress\Tests\Support\ArchiveImageTestCase;

final class SponsorArchiveImageTest extends ArchiveImageTestCase {

	protected function post_type(): string {
		return Plugin::POST_TYPE;
	}

	protected function option(): string {
		return Settings::OPTION;
	}

	protected function image_key(): string {
		return Settings::BANNER_IMAGE;
	}

	protected function subtitle_key(): string {
		return Settings::BANNER_SUBTITLE;
	}

	protected function settings_page_id(): string {
		return SettingsPage::ID;
	}

	protected function make_archive_paginate(): void {
		add_filter( 'hyperpress_sponsor_archive_per_page', static fn() => 1 );
	}
}
