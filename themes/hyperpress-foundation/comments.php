<?php
/**
 * The template for displaying comments
 *
 * The area of the page that contains both current comments
 * and the comment form.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

// Do not list comments (or the form) on a post whose password has not been entered.
if ( post_password_required() ) {
	?>
	<section id="comments">
		<div class="notice">
			<p class="bottom"><?php esc_html_e( 'This post is password protected. Enter the password to view comments.', 'hyperpress' ); ?></p>
		</div>
	</section>
	<?php
	return;
}

if ( have_comments() ) :
?>
	<section id="comments">
		<?php

		wp_list_comments(
			array(
				'walker'            => new HyperPress_Theme_Comments(),
				'max_depth'         => '',
				'style'             => 'ol',
				'callback'          => null,
				'end-callback'      => null,
				'type'              => 'all',
				'reply_text'        => __( 'Reply', 'hyperpress' ),
				'page'              => '',
				'per_page'          => '',
				'avatar_size'       => 48,
				'reverse_top_level' => null,
				'reverse_children'  => '',
				'format'            => 'html5',
				'short_ping'        => false,
				'echo'              => true,
				'moderation'        => __( 'Your comment is awaiting moderation.', 'hyperpress' ),
			)
		);

		?>
		<?php
			hyperpress_the_comments_pagination();
	 	?>
	</section>
<?php
endif;
?>

<?php
if ( comments_open() ) :
?>
<section id="respond">
	<?php
		comment_form(
			array(
				'class_submit' => 'button',
			)
		);
	?>
</section>
<?php
	endif; // If you delete this the sky will fall on your head.
