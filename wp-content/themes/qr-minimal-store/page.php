<?php
get_header();
?>
<?php if ( function_exists( 'is_account_page' ) && is_account_page() ) : ?>
<div class="container qr-account-page">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php the_content(); ?>
	<?php endwhile; ?>
</div>
<?php else : ?>
<div class="container page-wrap">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'content-card' ); ?>>
			<h1><?php the_title(); ?></h1>
			<?php the_content(); ?>
		</article>
	<?php endwhile; ?>
</div>
<?php endif; ?>
<?php
get_footer();
?>
