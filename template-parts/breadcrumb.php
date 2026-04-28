<?php
$breadcrumbs = apply_filters( 'hyperpress_breadcrumbs_content', [
	[
		"title"   => 'Home',
		"url"     => home_url(),
		"classes" => [ 'li' => [ 'item-home' ], 'bread' => [ 'bread-link bread-home' ] ],
	],
] );
?>
<ul id="breadcrumbs" class="breadcrumbs">
	<?php foreach ( $breadcrumbs as $breadcrumb ) : ?>
		<li class="item <?php echo implode( " ", $breadcrumb["classes"]["li"] ); ?>">
			<?php if ( ! empty( $breadcrumb["url"] ) ): ?>
				<a
					class="breadcrumb <?php echo implode( " ", $breadcrumb["classes"]["bread"] ); ?>"
					title="<?php echo $breadcrumb["title"]; ?>"
					href="<?php echo $breadcrumb["url"]; ?>"><?php echo $breadcrumb["title"]; ?></a>
			<?php else : ?>
				<span class="<?php echo implode( " ", $breadcrumb["classes"]["bread"] ); ?>"
							title="<?php echo $breadcrumb["title"]; ?>"><?php echo $breadcrumb["title"]; ?></span>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
