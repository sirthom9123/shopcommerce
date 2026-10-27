<?php
/**
 * Your orders (design board E): one card per order with a plain-words status, View order,
 * Buy again (completed and cancelled) and Pay now (unpaid). WooCommerce's "find my past
 * guest orders" prompt shows as a note under the list.
 *
 * @package qr-minimal-store
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;
?>
<h1 class="qr-acct__title"><?php esc_html_e( 'Your orders', 'qr-minimal-store' ); ?></h1>

<?php if ( $has_orders ) : ?>
	<ul class="qr-orders">
		<?php foreach ( $customer_orders->orders as $qr_customer_order ) : ?>
			<?php
			$qr_order = wc_get_order( $qr_customer_order );
			if ( ! $qr_order ) {
				continue;
			}
			$qr_status = qr_minimal_store_order_status( $qr_order );
			$qr_again  = qr_minimal_store_order_again_url( $qr_order );
			$qr_thumb  = qr_minimal_store_order_thumb( $qr_order );
			?>
			<li class="qr-orders__card">
				<span class="qr-orders__thumb"><?php echo $qr_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?></span>
				<div class="qr-orders__body">
					<p class="qr-orders__head">
						<span class="qr-orders__no">
							<?php
							/* translators: %s: order number */
							echo esc_html( sprintf( __( 'Order #%s', 'qr-minimal-store' ), $qr_order->get_order_number() ) );
							?>
						</span>
						<span class="qr-pill qr-pill--<?php echo esc_attr( $qr_status['tone'] ); ?>"><?php echo esc_html( $qr_status['label'] ); ?></span>
					</p>
					<p class="qr-orders__items"><?php echo esc_html( qr_minimal_store_order_items_text( $qr_order ) ); ?></p>
					<p class="qr-orders__meta"><?php echo esc_html( wc_format_datetime( $qr_order->get_date_created(), 'j M Y' ) ); ?> · <?php echo wp_kses_post( wc_price( $qr_order->get_total(), array( 'currency' => $qr_order->get_currency() ) ) ); ?></p>
				</div>
				<div class="qr-orders__actions">
					<?php if ( $qr_order->needs_payment() ) : ?>
						<a class="qr-btn qr-btn--sm" href="<?php echo esc_url( $qr_order->get_checkout_payment_url() ); ?>"><?php esc_html_e( 'Pay now', 'qr-minimal-store' ); ?><span class="screen-reader-text">: <?php echo esc_html( '#' . $qr_order->get_order_number() ); ?></span></a>
					<?php endif; ?>
					<a class="qr-btn qr-btn--sm qr-btn--outline" href="<?php echo esc_url( $qr_order->get_view_order_url() ); ?>"><?php esc_html_e( 'View order', 'qr-minimal-store' ); ?><span class="screen-reader-text"> <?php echo esc_html( '#' . $qr_order->get_order_number() ); ?></span></a>
					<?php if ( $qr_again ) : ?>
						<a class="qr-btn qr-btn--sm qr-btn--text" href="<?php echo esc_url( $qr_again ); ?>"><?php esc_html_e( 'Buy again', 'qr-minimal-store' ); ?><span class="screen-reader-text">: <?php echo esc_html( '#' . $qr_order->get_order_number() ); ?></span></a>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

	<?php if ( 1 < $customer_orders->max_num_pages ) : ?>
		<nav class="qr-orders__pages" aria-label="<?php esc_attr_e( 'More orders', 'qr-minimal-store' ); ?>">
			<?php if ( 1 !== $current_page ) : ?>
				<a class="qr-btn qr-btn--sm qr-btn--outline" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>"><?php esc_html_e( 'Newer orders', 'qr-minimal-store' ); ?></a>
			<?php endif; ?>
			<?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
				<a class="qr-btn qr-btn--sm qr-btn--outline" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Older orders', 'qr-minimal-store' ); ?></a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
<?php else : ?>
	<div class="qr-orders__empty">
		<p><?php esc_html_e( 'No orders yet.', 'qr-minimal-store' ); ?></p>
		<a class="qr-btn" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php esc_html_e( 'Browse products', 'qr-minimal-store' ); ?></a>
	</div>
<?php endif; ?>

<div class="qr-acct__guest">
	<?php do_action( 'woocommerce_before_account_orders', $has_orders ); ?>
</div>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
