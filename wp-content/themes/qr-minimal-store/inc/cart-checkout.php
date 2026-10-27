<?php
/**
 * Cart and checkout (WooCommerce Blocks).
 *
 * Minimal checkout header, numbered steps, free-delivery progress, Payfast method list,
 * "Continue to Payfast · R{total}" button and assurances.
 *
 * The Blocks frontend keeps static HTML placed next to an inner block (it is parsed into
 * static elements). Elements with a data-qr-* attribute are filled by cart-checkout.js
 * from the wc/store/cart data store, so they follow the cart as it changes. The cart and
 * checkout pages are never page-cached, but nothing here is per-user PHP markup anyway.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkout form page (not order received / order pay).
 */
function qr_minimal_store_is_checkout_flow() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return false;
	}
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return false;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
		return false;
	}
	return true;
}

function qr_minimal_store_is_cart_flow() {
	return function_exists( 'is_cart' ) && is_cart();
}

/**
 * Free-delivery rule from qr-shipping. Null when qr-shipping is not active.
 *
 * @return array|null
 */
function qr_minimal_store_free_delivery_rule() {
	if ( ! defined( 'QR_SHIPPING_FREE_THRESHOLD' ) ) {
		return null;
	}
	return array(
		'threshold' => (float) QR_SHIPPING_FREE_THRESHOLD,
		'fee'       => defined( 'QR_SHIPPING_PAID_INCL' ) ? (float) QR_SHIPPING_PAID_INCL : 0.0,
	);
}

/**
 * True when Payfast is the only gateway the shopper can pick.
 */
function qr_minimal_store_payfast_only() {
	if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return false;
	}
	$gateways = WC()->payment_gateways()->get_available_payment_gateways();
	return 1 === count( $gateways ) && isset( $gateways['payfast'] );
}

/**
 * Methods the shopper picks from on the Payfast screen. Only methods confirmed on the
 * store's Payfast account; add more through the filter once confirmed.
 *
 * @return array[]
 */
function qr_minimal_store_payfast_methods() {
	return apply_filters(
		'qr_minimal_store_payfast_methods',
		array(
			array(
				'name' => __( 'Card', 'qr-minimal-store' ),
				'note' => __( 'Visa, Mastercard', 'qr-minimal-store' ),
			),
			array(
				'name' => __( 'Instant EFT', 'qr-minimal-store' ),
				'note' => __( 'From your bank', 'qr-minimal-store' ),
			),
			array(
				'name' => __( 'Mobicred', 'qr-minimal-store' ),
				'note' => __( 'Instalments, subject to approval', 'qr-minimal-store' ),
			),
			array(
				'name' => __( 'Store cards', 'qr-minimal-store' ),
				'note' => __( 'RCS, subject to approval', 'qr-minimal-store' ),
			),
		)
	);
}

add_action( 'wp_enqueue_scripts', 'qr_minimal_store_cart_checkout_assets', 25 );
function qr_minimal_store_cart_checkout_assets() {
	$is_cart     = qr_minimal_store_is_cart_flow();
	$is_checkout = qr_minimal_store_is_checkout_flow();
	if ( ! $is_cart && ! $is_checkout ) {
		return;
	}

	$file = get_template_directory() . '/assets/js/cart-checkout.js';
	wp_enqueue_script(
		'qr-minimal-store-cart-checkout',
		get_template_directory_uri() . '/assets/js/cart-checkout.js',
		array( 'wp-data', 'wp-hooks', 'wp-i18n', 'wc-blocks-data-store', 'wc-blocks-checkout' ),
		file_exists( $file ) ? (string) filemtime( $file ) : wp_get_theme()->get( 'Version' ),
		true
	);

	$rule = qr_minimal_store_free_delivery_rule();
	wp_localize_script(
		'qr-minimal-store-cart-checkout',
		'qrCartCheckout',
		array(
			'freeDelivery'     => $rule,
			'pricesIncludeTax' => function_exists( 'wc_tax_enabled' ) && wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_cart' ),
			'shopUrl'          => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
			'proceedLabel'     => __( 'Go to checkout', 'qr-minimal-store' ),
			// WooCommerce Blocks strings (domain "woocommerce") shown with store wording.
			'labels'           => array(
				'Cart totals'         => __( 'Order summary', 'qr-minimal-store' ),
				'Add coupons'         => __( 'Have a coupon?', 'qr-minimal-store' ),
				'Estimated total'     => __( 'Total', 'qr-minimal-store' ),
				'Contact information' => __( 'Contact', 'qr-minimal-store' ),
				'Shipping address'    => __( 'Delivery address', 'qr-minimal-store' ),
				'Shipping options'    => __( 'Delivery', 'qr-minimal-store' ),
				'Payment options'     => __( 'Payment', 'qr-minimal-store' ),
				'Phone'               => __( 'Mobile number (for the courier)', 'qr-minimal-store' ),
				'Phone (optional)'    => __( 'Mobile number (for the courier, optional)', 'qr-minimal-store' ),
			),
			'i18n'             => array(
				/* translators: %s: amount still needed, e.g. R2 039,90 */
				'addMore'      => __( 'Add %s more for free delivery.', 'qr-minimal-store' ),
				/* translators: 1: delivery fee, 2: free-delivery threshold */
				'feeNote'      => __( 'Delivery is %1$s under %2$s.', 'qr-minimal-store' ),
				'unlocked'     => __( 'Free delivery unlocked.', 'qr-minimal-store' ),
				/* translators: %s: free-delivery threshold */
				'unlockedNote' => __( 'Your cart is %s or more.', 'qr-minimal-store' ),
				/* translators: 1: free-delivery threshold, 2: amount still needed */
				'away'         => __( 'Free from %1$s. You are %2$s away.', 'qr-minimal-store' ),
				'addSomething' => __( 'Add something', 'qr-minimal-store' ),
				'item'         => __( '1 item', 'qr-minimal-store' ),
				/* translators: %d: number of items in the cart */
				'items'        => __( '%d items', 'qr-minimal-store' ),
			),
		)
	);
}

/**
 * Phone label in the Blocks address form comes from PHP (wcSettings), so it is renamed
 * here too. Checkout page only.
 */
add_action( 'wp', 'qr_minimal_store_checkout_label_filters' );
function qr_minimal_store_checkout_label_filters() {
	if ( qr_minimal_store_is_checkout_flow() ) {
		add_filter( 'gettext_woocommerce', 'qr_minimal_store_checkout_gettext', 10, 2 );
	}
}

function qr_minimal_store_checkout_gettext( $translation, $text ) {
	if ( 'Phone' === $text ) {
		return __( 'Mobile number (for the courier)', 'qr-minimal-store' );
	}
	if ( 'Phone (optional)' === $text ) {
		return __( 'Mobile number (for the courier, optional)', 'qr-minimal-store' );
	}
	return $translation;
}

add_filter( 'body_class', 'qr_minimal_store_checkout_body_class' );
function qr_minimal_store_checkout_body_class( $classes ) {
	if ( qr_minimal_store_is_checkout_flow() ) {
		$classes[] = 'qr-checkout-focus';
		if ( qr_minimal_store_payfast_only() ) {
			$classes[] = 'qr-payfast-only';
		}
	}
	return $classes;
}

/**
 * Cart page heading: "Your cart · N items" (count filled by cart-checkout.js).
 */
add_filter( 'the_title', 'qr_minimal_store_cart_title', 10, 2 );
function qr_minimal_store_cart_title( $title, $post_id = 0 ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() || ! qr_minimal_store_is_cart_flow() ) {
		return $title;
	}
	if ( (int) $post_id !== (int) wc_get_page_id( 'cart' ) ) {
		return $title;
	}
	return esc_html__( 'Your cart', 'qr-minimal-store' ) . '<span class="qr-cart-count" data-qr-cart-count></span>';
}

/**
 * Cart line items: stock and delivery time under the product name.
 * Backorder items keep the WooCommerce "Available on backorder" badge.
 */
add_filter( 'woocommerce_get_item_data', 'qr_minimal_store_cart_item_delivery', 10, 2 );
function qr_minimal_store_cart_item_delivery( $item_data, $cart_item ) {
	$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
	if ( ! $product instanceof WC_Product || ! $product->is_in_stock() ) {
		return $item_data;
	}
	if ( $product->is_on_backorder( isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1 ) ) {
		return $item_data;
	}
	$value       = __( 'In stock · delivery in 2–3 working days', 'qr-minimal-store' );
	$item_data[] = array(
		'key'     => __( 'Availability', 'qr-minimal-store' ),
		'value'   => $value,
		'display' => esc_html( $value ),
	);
	return $item_data;
}

function qr_minimal_store_free_delivery_placeholder( $variant ) {
	if ( null === qr_minimal_store_free_delivery_rule() ) {
		return '';
	}
	return sprintf(
		'<div class="qr-free-delivery qr-free-delivery--%1$s" data-qr-free-delivery="%1$s" aria-live="polite"></div>',
		esc_attr( $variant )
	);
}

function qr_minimal_store_no_vat_note() {
	if ( function_exists( 'wc_tax_enabled' ) && wc_tax_enabled() ) {
		return '';
	}
	return '<p class="qr-summary-note">' . esc_html__( 'No VAT added', 'qr-minimal-store' ) . '</p>';
}

/**
 * @param string[] $lines Assurance lines (plain text).
 */
function qr_minimal_store_assurances_markup( $lines ) {
	$html = '<ul class="qr-assurances">';
	foreach ( $lines as $line ) {
		$html .= '<li>' . esc_html( $line ) . '</li>';
	}
	return $html . '</ul>';
}

// Cart: free-delivery progress above the line items.
add_filter( 'render_block_woocommerce/cart-line-items-block', 'qr_minimal_store_cart_progress_block' );
function qr_minimal_store_cart_progress_block( $content ) {
	return qr_minimal_store_free_delivery_placeholder( 'cart' ) . $content;
}

// Cart: "Continue shopping" under the items (after the qr-upsells card, priority 10).
add_filter( 'render_block_woocommerce/cart-line-items-block', 'qr_minimal_store_cart_continue_block', 20 );
function qr_minimal_store_cart_continue_block( $content ) {
	return $content . sprintf(
		'<p class="qr-cart-continue"><a href="%1$s">&larr; %2$s</a></p>',
		esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ),
		esc_html__( 'Continue shopping', 'qr-minimal-store' )
	);
}

// Delivery option: show the delivery time under the rate (display only, not saved on the order).
add_filter( 'woocommerce_package_rates', 'qr_minimal_store_rate_delivery_time', 100 );
function qr_minimal_store_rate_delivery_time( $rates ) {
	foreach ( $rates as $rate ) {
		if ( $rate instanceof WC_Shipping_Rate && method_exists( $rate, 'set_delivery_time' ) && 'local_pickup' !== $rate->get_method_id() && '' === $rate->get_delivery_time() ) {
			$rate->set_delivery_time( __( '2–3 working days, nationwide', 'qr-minimal-store' ) );
		}
	}
	return $rates;
}

// Cart: "No VAT added" under the total.
add_filter( 'render_block_woocommerce/cart-order-summary-block', 'qr_minimal_store_cart_summary_note_block' );
function qr_minimal_store_cart_summary_note_block( $content ) {
	return $content . qr_minimal_store_no_vat_note();
}

// Cart: assurances at the end of the summary (after the qr-payment-badges credit line, priority 10).
add_filter( 'render_block_woocommerce/cart-accepted-payment-methods-block', 'qr_minimal_store_cart_assurances_block', 20 );
function qr_minimal_store_cart_assurances_block( $content ) {
	return $content . qr_minimal_store_assurances_markup(
		array(
			__( 'Secure payment processed by Payfast', 'qr-minimal-store' ),
			__( '30-day returns on unused items', 'qr-minimal-store' ),
			__( 'Genuine products with local warranty', 'qr-minimal-store' ),
		)
	);
}

// Checkout: free-delivery nudge under the delivery option (after the qr-shipping policy text).
add_filter( 'render_block_woocommerce/checkout-shipping-methods-block', 'qr_minimal_store_checkout_nudge_block', 20 );
function qr_minimal_store_checkout_nudge_block( $content ) {
	return $content . qr_minimal_store_free_delivery_placeholder( 'checkout' );
}

// Checkout: what the shopper can choose on the Payfast screen.
add_filter( 'render_block_woocommerce/checkout-payment-block', 'qr_minimal_store_checkout_payfast_block' );
function qr_minimal_store_checkout_payfast_block( $content ) {
	if ( ! qr_minimal_store_payfast_only() ) {
		return $content;
	}
	$items = '';
	foreach ( qr_minimal_store_payfast_methods() as $method ) {
		$items .= sprintf(
			'<li><strong>%1$s</strong><span>%2$s</span></li>',
			esc_html( $method['name'] ),
			esc_html( $method['note'] )
		);
	}
	$logo = '';
	if ( file_exists( WP_PLUGIN_DIR . '/woocommerce-payfast-gateway/assets/images/icon.png' ) ) {
		$logo = sprintf( '<img class="qr-payfast-methods__logo" src="%s" alt="Payfast" height="24">', esc_url( plugins_url( 'woocommerce-payfast-gateway/assets/images/icon.png' ) ) );
	}
	// Payfast is the only gateway, so its radio row is hidden (qr-payfast-only) and this card stands in for it.
	return $content . sprintf(
		'<div class="qr-payfast-methods"><div class="qr-payfast-methods__head"><strong>%1$s</strong>%2$s</div><p class="qr-payfast-methods__lead">%3$s</p><ul class="qr-payfast-methods__list">%4$s</ul></div>',
		esc_html__( 'Pay securely with Payfast', 'qr-minimal-store' ),
		$logo,
		esc_html__( 'You choose how to pay on the next screen:', 'qr-minimal-store' ),
		$items
	);
}

// Checkout: "Continue to Payfast · R{total}" (core button with price) and the redirect note.
add_filter( 'render_block_woocommerce/checkout-actions-block', 'qr_minimal_store_checkout_actions_block' );
function qr_minimal_store_checkout_actions_block( $content ) {
	if ( ! qr_minimal_store_payfast_only() || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $content;
	}
	$tags = new WP_HTML_Tag_Processor( $content );
	if ( ! $tags->next_tag() ) {
		return $content;
	}
	$tags->add_class( 'is-style-with-price' );
	$tags->set_attribute( 'data-place-order-button-label', __( 'Continue to Payfast', 'qr-minimal-store' ) );
	$tags->set_attribute( 'data-price-separator', '·' );
	return $tags->get_updated_html()
		. '<p class="qr-checkout-redirect-note">' . esc_html__( 'You are sent to Payfast to pay, then back here for your confirmation.', 'qr-minimal-store' ) . '</p>';
}

// Checkout: "No VAT added" and assurances under the order summary.
add_filter( 'render_block_woocommerce/checkout-order-summary-block', 'qr_minimal_store_checkout_summary_block' );
function qr_minimal_store_checkout_summary_block( $content ) {
	return $content . qr_minimal_store_no_vat_note() . qr_minimal_store_assurances_markup(
		array(
			__( '30-day returns on unused items', 'qr-minimal-store' ),
			__( 'Genuine products with local warranty', 'qr-minimal-store' ),
			__( 'Questions? orders@shop.yehudasolutions.com', 'qr-minimal-store' ),
		)
	);
}
