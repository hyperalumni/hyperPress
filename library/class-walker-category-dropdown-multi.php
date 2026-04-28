<?php
class Walker_Category_Dropdown_Multi extends Walker_Category_Checklist {
	// TODO: override Walker_Category_Checklist to create a multi-select dropdown

	public function start_lvl( &$output, $depth = 0, $args = array() ) {}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {}

	public function start_el( &$output, $data_object, $depth = 0, $args = array(), $current_object_id = 0 ) {}

	public function end_el( &$output, $data_object, $depth = 0, $args = array() ) {}
}
