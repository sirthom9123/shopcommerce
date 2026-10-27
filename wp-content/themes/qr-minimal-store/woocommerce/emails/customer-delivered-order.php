<?php
/**
 * Customer delivered order email (theme template, same structure as WooCommerce's completed order email).
 *
 * @package QR_Minimal_Store
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$delivered                  = function_exists( 'qr_minimal_store_order_date_delivered' ) ? qr_minimal_store_order_date_delivered( $order ) : null;
$returns_email              = defined( 'QR_RETURNS_EMAIL' ) ? QR_RETURNS_EMAIL : get_option( 'woocommerce_email_from_address' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'qr-minimal-store' ), esc_html( $order->get_billing_first_name() ) );
} else {
	esc_html_e( 'Hi,', 'qr-minimal-store' );
}
?>
</p>
<p>
<?php
if ( $delivered ) {
	/* translators: 1: order number, 2: delivery date */
	printf( esc_html__( 'Your order #%1$s was delivered on %2$s. We hope you enjoy it.', 'qr-minimal-store' ), esc_html( $order->get_order_number() ), esc_html( wc_format_datetime( $delivered, 'j F Y' ) ) );
} else {
	/* translators: %s: order number */
	printf( esc_html__( 'Your order #%s was delivered. We hope you enjoy it.', 'qr-minimal-store' ), esc_html( $order->get_order_number() ) );
}
?>
</p>
<p>
<?php
/* translators: %s: returns email address (link) */
printf( esc_html__( 'Something not right? Unused items can be returned within 30 days of delivery. Email %s with your order number.', 'qr-minimal-store' ), '<a href="mailto:' . esc_attr( $returns_email ) . '">' . esc_html( $returns_email ) . '</a>' );
?>
</p>
<?php if ( $email_improvements_enabled ) : ?>
	<p><?php esc_html_e( 'Here is what was delivered:', 'qr-minimal-store' ); ?></p>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * User-defined additional content, set in the email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
