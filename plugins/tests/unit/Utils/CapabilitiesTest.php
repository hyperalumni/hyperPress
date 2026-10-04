<?php
namespace HyperPress\Tests\Unit\Utils;

use HyperPress\Utils\Capabilities;
use PHPUnit\Framework\TestCase;
use stdClass;

final class CapabilitiesTest extends TestCase {
	public function test_meta_caps_and_read_are_excluded_and_values_are_unique(): void {
		$cap                     = new stdClass();
		$cap->edit_post          = 'edit_hyper_award';
		$cap->read_post          = 'read_hyper_award';
		$cap->delete_post        = 'delete_hyper_award';
		$cap->edit_posts         = 'edit_hyper_awards';
		$cap->edit_others_posts  = 'edit_others_hyper_awards';
		$cap->publish_posts      = 'publish_hyper_awards';
		$cap->read_private_posts = 'read_private_hyper_awards';
		$cap->read               = 'read';
		$cap->create_posts       = 'edit_hyper_awards';

		$this->assertSame(
			array( 'edit_hyper_awards', 'edit_others_hyper_awards', 'publish_hyper_awards', 'read_private_hyper_awards' ),
			Capabilities::primitive_caps( $cap )
		);
	}

	public function test_roles_are_administrator_and_editor(): void {
		$this->assertSame( array( 'administrator', 'editor' ), Capabilities::ROLES );
	}
}
