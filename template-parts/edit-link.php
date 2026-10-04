<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>

<?php
edit_post_link( __( '(Edit)', 'hyperpress' ), '<div class="content"><span class="edit-link">', '</span></div>' );
