<?php
get_header();
?>
<div class="container page-wrap">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'content-card' ); ?>>
			<h1><?php the_title(); ?></h1>
			<?php the_content(); ?>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
?>
