<?php
namespace HyperPress\Tests\Integration\Utils;

use DOMDocument;
use DOMElement;
use HyperPress\Utils\Controls\CategoryDropdownControl;
use HyperPress\Utils\Controls\PostTypeDropdownControl;
use ReflectionMethod;
use WP_Customize_Control;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class ControlsRenderTest extends WP_UnitTestCase {

	/** @var array<int,\LibXMLError> */
	private array $xml_errors = array();

	private function manager( string $id, $default ): WP_Customize_Manager {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		$manager->add_setting( $id, array( 'default' => $default ) );
		return $manager;
	}

	private function render( WP_Customize_Control $control ): string {
		$errors = array();
		set_error_handler(
			static function ( int $no, string $msg ) use ( &$errors ): bool {
				$errors[] = $msg;
				return true;
			}
		);
		ob_start();
		( new ReflectionMethod( $control, 'render_content' ) )->invoke( $control );
		$html = ob_get_clean();
		restore_error_handler();
		$this->assertSame( array(), $errors, 'rendering must not raise PHP warnings' );
		return $html;
	}

	private function select( string $html ): DOMElement {
		$previous = libxml_use_internal_errors( true );
		libxml_clear_errors();
		$doc = new DOMDocument();
		$doc->loadHTML( '<html><body>' . $html . '</body></html>' );
		$this->xml_errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$selects = $doc->getElementsByTagName( 'select' );
		$this->assertSame( 1, $selects->length, 'exactly one select is rendered' );
		$select = $selects->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $select );
		return $select;
	}

	/** @return list<string> */
	private function selected_values( DOMElement $select ): array {
		$values = array();
		foreach ( $select->getElementsByTagName( 'option' ) as $option ) {
			if ( $option->hasAttribute( 'selected' ) ) {
				$values[] = $option->getAttribute( 'value' );
			}
		}
		return $values;
	}

	private function assert_well_formed(): void {
		$messages = array_map( static fn( $e ) => trim( $e->message ), $this->xml_errors );
		$this->assertSame( array(), $messages, 'the markup parses without errors' );
	}

	public function test_post_type_control_has_real_ids_and_preselects_values(): void {
		$id      = 'hyperpress_test_post_types';
		$manager = $this->manager( $id, array( 'post', 'page' ) );
		$control = new PostTypeDropdownControl( $manager, $id, array( 'settings' => $id, 'label' => 'Types' ) );
		$html    = $this->render( $control );
		$select  = $this->select( $html );

		$this->assertSame( $id, $select->getAttribute( 'id' ) );
		$this->assertSame( $id, $select->getAttribute( 'name' ) );
		$this->assertStringContainsString( 'for="' . $id . '"', $html );
		$this->assertSame( $id, $select->getAttribute( 'data-customize-setting-link' ) );
		$this->assertEqualsCanonicalizing( array( 'post', 'page' ), $this->selected_values( $select ) );
		$this->assert_well_formed();
	}

	public function test_post_type_control_does_not_select_unlisted_types(): void {
		$id      = 'hyperpress_test_post_types';
		$manager = $this->manager( $id, array( 'post' ) );
		$control = new PostTypeDropdownControl( $manager, $id, array( 'settings' => $id ) );
		$select  = $this->select( $this->render( $control ) );

		$this->assertSame( array( 'post' ), $this->selected_values( $select ) );
	}

	public function test_category_control_has_own_id_valid_tag_and_preselects_values(): void {
		$one = self::factory()->category->create( array( 'name' => 'One' ) );
		$two = self::factory()->category->create( array( 'name' => 'Two' ) );
		$ids = array( $one, $two );
		foreach ( $ids as $term_id ) {
			self::factory()->post->create( array( 'post_category' => array( $term_id ) ) );
		}

		$id      = 'hyperpress_test_categories';
		$manager = $this->manager( $id, $ids );
		$control = new CategoryDropdownControl( $manager, $id, array( 'settings' => $id, 'label' => 'Cats' ) );
		$html    = $this->render( $control );
		$select  = $this->select( $html );

		$this->assertStringContainsString( '<select multiple size="10" data-customize-setting-link="', $html );
		$this->assertSame( $id, $select->getAttribute( 'data-customize-setting-link' ) );
		$this->assertSame( $id, $select->getAttribute( 'name' ) );
		$this->assertNotSame( '', $select->getAttribute( 'id' ) );
		$this->assertNotSame( 'cat', $select->getAttribute( 'id' ) );
		$this->assertEqualsCanonicalizing( array( (string) $one, (string) $two ), $this->selected_values( $select ) );
		$this->assert_well_formed();
	}
}
