<?php
namespace HyperPress\Tests\Integration\Robot;

use HyperPress\Robot\Plugin;
use HyperPress\Robot\Settings;
use HyperPress\Robot\SettingsPage;
use HyperPress\Tests\Support\ArchiveImageTestCase;

final class RobotArchiveImageTest extends ArchiveImageTestCase {

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
