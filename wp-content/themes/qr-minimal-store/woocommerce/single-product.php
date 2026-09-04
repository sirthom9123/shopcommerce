<?php
/**
 * WooCommerce single product template override.
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>
<div class="container page-wrap woocommerce-wrap">
	<?php
	/**
	 * Hook: woocommerce_before_main_content.
	 */
	do_action( 'woocommerce_before_main_content' );
	?>

	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<?php wc_get_template_part( 'content', 'single-product' ); ?>
	<?php endwhile; ?>

	<?php
	/**
	 * Hook: woocommerce_after_main_content.
	 */
	do_action( 'woocommerce_after_main_content' );
	?>
</div>
<?php
get_footer( 'shop' );
?>
