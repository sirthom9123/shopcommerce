<?php
/**
 * Customer delivered order email (plain text).
 *
 * @package QR_Minimal_Store
 */

defined( 'ABSPATH' ) || exit;

$delivered     = function_exists( 'qr_minimal_store_order_date_delivered' ) ? qr_minimal_store_order_date_delivered( $order ) : null;
$returns_email = defined( 'QR_RETURNS_EMAIL' ) ? QR_RETURNS_EMAIL : get_option( 'woocommerce_email_from_address' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'qr-minimal-store' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
if ( $delivered ) {
	/* translators: 1: order number, 2: delivery date */
	echo sprintf( esc_html__( 'Your order #%1$s was delivered on %2$s. We hope you enjoy it.', 'qr-minimal-store' ), esc_html( $order->get_order_number() ), esc_html( wc_format_datetime( $delivered, 'j F Y' ) ) ) . "\n\n";
} else {
	/* translators: %s: order number */
	echo sprintf( esc_html__( 'Your order #%s was delivered. We hope you enjoy it.', 'qr-minimal-store' ), esc_html( $order->get_order_number() ) ) . "\n\n";
}
/* translators: %s: returns email address */
echo sprintf( esc_html__( 'Something not right? Unused items can be returned within 30 days of delivery. Email %s with your order number.', 'qr-minimal-store' ), esc_html( $returns_email ) ) . "\n\n";

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
