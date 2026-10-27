<?php
/**
 * Customer email sent when an order is marked Delivered.
 * Settings: WooCommerce → Settings → Emails → Delivered order.
 *
 * @package QR_Minimal_Store
 */

defined( 'ABSPATH' ) || exit;

class QR_Email_Customer_Delivered_Order extends WC_Email {

	public function __construct() {
		$this->id             = 'customer_delivered_order';
		$this->customer_email = true;
		$this->title          = __( 'Delivered order', 'qr-minimal-store' );
		$this->description    = __( 'Sent to the customer when an order is marked Delivered.', 'qr-minimal-store' );
		$this->email_group    = 'order-updates';
		$this->template_base  = get_template_directory() . '/woocommerce/';
		$this->template_html  = 'emails/customer-delivered-order.php';
		$this->template_plain = 'emails/plain/customer-delivered-order.php';
		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		add_action( 'woocommerce_order_status_delivered_notification', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();
	}

	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                         = $order;
			$this->recipient                      = $this->object->get_billing_email();
			$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}'] = $this->object->get_order_number();
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	public function get_default_subject() {
		return __( 'Your {site_title} order #{order_number} was delivered', 'qr-minimal-store' );
	}

	public function get_default_heading() {
		return __( 'Your order was delivered', 'qr-minimal-store' );
	}

	public function get_default_additional_content() {
		/* translators: %s: orders email address */
		return sprintf( __( 'Thanks for shopping with us. Questions about your order? Email %s.', 'qr-minimal-store' ), defined( 'QR_ORDERS_EMAIL' ) ? QR_ORDERS_EMAIL : '{store_email}' );
	}

	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}
}
