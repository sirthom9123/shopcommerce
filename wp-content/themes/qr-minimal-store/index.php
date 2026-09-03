<?php
get_header();
?>
<div class="container page-wrap">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'content-card' ); ?>>
				<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No content found.', 'qr-minimal-store' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
?>
