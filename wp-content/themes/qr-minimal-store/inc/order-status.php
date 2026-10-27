<?php
/**
 * Order statuses after payment (Tshiamo, 27 Oct 2026):
 * Processing (paid, packing) → Completed, shown as "Sent to courier" (WooCommerce's
 * "on its way" email) → Delivered (custom status, own email).
 * Completed stays WooCommerce's own status so reports, reviews and plugins keep working.
 *
 * @package QR_Minimal_Store
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'qr_minimal_store_register_delivered_status' );
function qr_minimal_store_register_delivered_status() {
	register_post_status(
		'wc-delivered',
		array(
			'label'                     => _x( 'Delivered', 'Order status', 'qr-minimal-store' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of orders */
			'label_count'               => _n_noop( 'Delivered <span class="count">(%s)</span>', 'Delivered <span class="count">(%s)</span>', 'qr-minimal-store' ),
		)
	);
}

// Status list: Completed is labelled "Sent to courier"; Delivered comes right after it.
add_filter( 'wc_order_statuses', 'qr_minimal_store_order_statuses' );
function qr_minimal_store_order_statuses( $statuses ) {
	$out = array();
	foreach ( $statuses as $key => $label ) {
		$out[ $key ] = 'wc-completed' === $key ? _x( 'Sent to courier', 'Order status', 'qr-minimal-store' ) : $label;
		if ( 'wc-completed' === $key ) {
			$out['wc-delivered'] = _x( 'Delivered', 'Order status', 'qr-minimal-store' );
		}
	}
	if ( ! isset( $out['wc-delivered'] ) ) {
		$out['wc-delivered'] = _x( 'Delivered', 'Order status', 'qr-minimal-store' );
	}
	return $out;
}

// Delivered orders are paid orders (stock, customer totals, reports).
add_filter( 'woocommerce_order_is_paid_statuses', 'qr_minimal_store_add_delivered' );
add_filter( 'woocommerce_reports_order_statuses', 'qr_minimal_store_add_delivered' );
function qr_minimal_store_add_delivered( $statuses ) {
	if ( is_array( $statuses ) && ! in_array( 'delivered', $statuses, true ) ) {
		$statuses[] = 'delivered';
	}
	return $statuses;
}

// Remember when the order was delivered (the progress bar shows the date).
add_action( 'woocommerce_order_status_delivered', 'qr_minimal_store_mark_delivered_date', 5, 2 );
function qr_minimal_store_mark_delivered_date( $order_id, $order = null ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
	if ( $order ) {
		$order->update_meta_data( '_qr_date_delivered', time() );
		$order->save_meta_data();
	}
}

/**
 * Date the order was delivered, or null.
 *
 * @param WC_Order $order Order.
 * @return WC_DateTime|null
 */
function qr_minimal_store_order_date_delivered( WC_Order $order ) {
	$ts = (int) $order->get_meta( '_qr_date_delivered' );
	if ( ! $ts ) {
		return $order->has_status( 'delivered' ) ? $order->get_date_modified() : null;
	}
	$date = new WC_DateTime( '@' . $ts );
	$date->setTimezone( new DateTimeZone( wc_timezone_string() ) );
	return $date;
}

/* ---------- wp-admin: bulk actions, row buttons, colours ---------- */

add_filter( 'bulk_actions-woocommerce_page_wc-orders', 'qr_minimal_store_order_bulk_actions', 20 );
add_filter( 'bulk_actions-edit-shop_order', 'qr_minimal_store_order_bulk_actions', 20 );
function qr_minimal_store_order_bulk_actions( $actions ) {
	$out = array();
	foreach ( $actions as $key => $label ) {
		$out[ $key ] = 'mark_completed' === $key ? __( 'Change status to sent to courier', 'qr-minimal-store' ) : $label;
		if ( 'mark_completed' === $key ) {
			$out['mark_delivered'] = __( 'Change status to delivered', 'qr-minimal-store' );
		}
	}
	return $out;
}

// Orders list row buttons: processing → "Sent to courier", completed → "Delivered".
add_filter( 'woocommerce_admin_order_actions', 'qr_minimal_store_admin_order_actions', 10, 2 );
function qr_minimal_store_admin_order_actions( $actions, $order ) {
	if ( isset( $actions['complete'] ) ) {
		$actions['complete']['name'] = __( 'Sent to courier', 'qr-minimal-store' );
	}
	if ( $order->has_status( 'completed' ) ) {
		$actions['delivered'] = array(
			'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=delivered&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
			'name'   => __( 'Delivered', 'qr-minimal-store' ),
			'action' => 'delivered',
		);
	}
	return $actions;
}

add_action( 'admin_head', 'qr_minimal_store_order_status_admin_css' );
function qr_minimal_store_order_status_admin_css() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'edit-shop_order', 'shop_order' ), true ) ) {
		return;
	}
	echo '<style>
.order-status.status-completed{background:#c8d7e1;color:#2e4453}
.order-status.status-delivered{background:#c6e1c6;color:#2c4700}
.wc-action-button-delivered::after{font-family:dashicons!important;content:"\f147"!important}
</style>';
}

/* ---------- Delivered email ---------- */

add_filter( 'woocommerce_email_actions', 'qr_minimal_store_delivered_email_action' );
function qr_minimal_store_delivered_email_action( $actions ) {
	$actions[] = 'woocommerce_order_status_delivered';
	return $actions;
}

add_filter( 'woocommerce_email_classes', 'qr_minimal_store_delivered_email_class' );
function qr_minimal_store_delivered_email_class( $emails ) {
	require_once __DIR__ . '/class-qr-email-customer-delivered-order.php';
	$emails['QR_Email_Customer_Delivered_Order'] = new QR_Email_Customer_Delivered_Order();
	return $emails;
}
