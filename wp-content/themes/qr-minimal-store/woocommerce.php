<?php
// 3D Printing landing page (board B).
if ( function_exists( 'qr_minimal_store_is_3d_landing' ) && qr_minimal_store_is_3d_landing() ) {
	get_template_part( 'template-parts/landing-3d-printing' );
	return;
}
// Product listings (shop, categories, brands, product search) use the board E template.
if ( function_exists( 'qr_minimal_store_is_product_listing' ) && qr_minimal_store_is_product_listing() ) {
	wc_get_template( 'archive-product.php' );
	return;
}
get_header();
?>
<div class="container page-wrap woocommerce-wrap">
	<?php woocommerce_content(); ?>
</div>
<?php
get_footer();
?>
