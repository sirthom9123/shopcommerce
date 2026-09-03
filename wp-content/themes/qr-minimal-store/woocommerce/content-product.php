<?php
/**
 * Product card template override.
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'minimal-product-card', $product ); ?>>
	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>
	<div class="minimal-product-card__media">
		<?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?>
	</div>
	<div class="minimal-product-card__body">
		<?php do_action( 'woocommerce_shop_loop_item_title' ); ?>
		<?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>
		<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
	</div>
</li>
