<?php
namespace HyperPress\Tests\Integration\Award;

use HyperPress\Award\Plugin;
use HyperPress\Award\Settings;
use HyperPress\Award\SettingsPage;
use HyperPress\Tests\Support\ArchiveImageTestCase;

final class AwardArchiveImageTest extends ArchiveImageTestCase {

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
}
