<?php
/**
 * Account pages (design board E): Orders as the account home, plain-words order statuses,
 * order detail with progress, Buy again, Request a return, and guest "Track an order"
 * (order number + billing email, rate limited).
 *
 * Account pages are never cached (WooCommerce sends no-cache headers there), so per-user
 * markup is fine here.
 *
 * @package qr-minimal-store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const QR_RETURNS_EMAIL     = 'returns@shop.yehudasolutions.com';
const QR_ORDERS_EMAIL      = 'orders@shop.yehudasolutions.com';
const QR_TRACK_MAX_FAILS   = 5;
const QR_TRACK_WINDOW_SECS = 600;

/**
 * Menu: Orders, Addresses, Account details, Email preferences, Sign out. Dashboard and
 * Downloads are dropped; items added by other plugins stay before Sign out.
 */
add_filter( 'woocommerce_account_menu_items', 'qr_minimal_store_account_menu', 50 );
function qr_minimal_store_account_menu( $items ) {
	unset( $items['dashboard'], $items['downloads'] );
	$labels = array(
		'orders'       => __( 'Orders', 'qr-minimal-store' ),
		'edit-address' => __( 'Addresses', 'qr-minimal-store' ),
		'edit-account' => __( 'Account details', 'qr-minimal-store' ),
		'newsletter'   => __( 'Email preferences', 'qr-minimal-store' ),
	);
	$menu = array();
	foreach ( $labels as $key => $label ) {
		if ( isset( $items[ $key ] ) ) {
			$menu[ $key ] = $label;
			unset( $items[ $key ] );
		}
	}
	$logout = isset( $items['customer-logout'] );
	unset( $items['customer-logout'] );
	$menu += $items;
	if ( $logout ) {
		$menu['customer-logout'] = __( 'Sign out', 'qr-minimal-store' );
	}
	return $menu;
}

/**
 * Short menu labels for the phone chip row.
 *
 * @param string $endpoint Endpoint.
 * @param string $label    Full label.
 * @return string
 */
function qr_minimal_store_account_short_label( $endpoint, $label ) {
	$short = array(
		'edit-account' => __( 'Details', 'qr-minimal-store' ),
		'newsletter'   => __( 'Emails', 'qr-minimal-store' ),
	);
	return isset( $short[ $endpoint ] ) ? $short[ $endpoint ] : $label;
}

/**
 * Current account section; the account home shows Orders.
 *
 * @param string $endpoint Endpoint.
 * @return bool
 */
function qr_minimal_store_account_is_current( $endpoint ) {
	global $wp;
	if ( 'orders' === $endpoint ) {
		$other = array_diff( array_keys( wc_get_account_menu_items() ), array( 'orders' ) );
		foreach ( $other as $key ) {
			if ( isset( $wp->query_vars[ $key ] ) ) {
				return false;
			}
		}
		return ! isset( $wp->query_vars['view-order'] );
	}
	return wc_is_current_account_menu_item( $endpoint );
}

// The sign-in / sign-up tabs from qr-account are replaced by a "Create an account" link (design E).
add_action( 'init', 'qr_minimal_store_account_drop_auth_tabs', 20 );
function qr_minimal_store_account_drop_auth_tabs() {
	if ( class_exists( 'QR_Account' ) ) {
		remove_action( 'woocommerce_before_customer_login_form', array( QR_Account::instance(), 'render_auth_toggle' ) );
	}
}

// Buy again: sent, delivered and cancelled orders (Tshiamo, 27 Oct).
add_filter( 'woocommerce_valid_order_statuses_for_order_again', 'qr_minimal_store_order_again_statuses' );
function qr_minimal_store_order_again_statuses( $statuses ) {
	return array_values( array_unique( array_merge( (array) $statuses, array( 'completed', 'delivered', 'cancelled' ) ) ) );
}

/**
 * Plain-words status and pill style.
 *
 * @param WC_Order $order Order.
 * @return array{label: string, tone: string}
 */
function qr_minimal_store_order_status( WC_Order $order ) {
	$map = array(
		'pending'    => array( __( 'Awaiting payment', 'qr-minimal-store' ), 'wait' ),
		'on-hold'    => array( __( 'Awaiting payment check', 'qr-minimal-store' ), 'wait' ),
		'processing' => array( __( 'Paid, packing', 'qr-minimal-store' ), 'active' ),
		'completed'  => array( __( 'Sent to courier', 'qr-minimal-store' ), 'active' ),
		'delivered'  => array( __( 'Delivered', 'qr-minimal-store' ), 'done' ),
		'cancelled'  => array( __( 'Cancelled', 'qr-minimal-store' ), 'closed' ),
		'refunded'   => array( __( 'Refunded', 'qr-minimal-store' ), 'closed' ),
		'failed'     => array( __( 'Payment failed', 'qr-minimal-store' ), 'closed' ),
	);
	$status = $order->get_status();
	$row    = isset( $map[ $status ] ) ? $map[ $status ] : array( wc_get_order_status_name( $status ), 'closed' );
	return array(
		'label' => $row[0],
		'tone'  => $row[1],
	);
}

/**
 * Courier and tracking number, only when one was added to the order. Nothing is made up:
 * the default reads the WooCommerce Shipment Tracking extension's data if it is installed;
 * other sources can use the filter.
 *
 * @param WC_Order $order Order.
 * @return array{courier: string, number: string, url: string}|null
 */
function qr_minimal_store_order_tracking( WC_Order $order ) {
	$tracking = null;
	$items    = $order->get_meta( '_wc_shipment_tracking_items' );
	if ( is_array( $items ) && $items ) {
		$last = end( $items );
		if ( ! empty( $last['tracking_number'] ) ) {
			$tracking = array(
				'courier' => (string) ( ! empty( $last['custom_tracking_provider'] ) ? $last['custom_tracking_provider'] : ( $last['tracking_provider'] ?? '' ) ),
				'number'  => (string) $last['tracking_number'],
				'url'     => (string) ( $last['custom_tracking_link'] ?? '' ),
			);
		}
	}
	return apply_filters( 'qr_minimal_store_order_tracking', $tracking, $order );
}

/**
 * Four-step progress: Order placed, Paid, Sent to courier, Delivered (estimate until marked Delivered).
 * Empty for cancelled, refunded and failed orders.
 *
 * @param WC_Order $order Order.
 * @return array<int, array{label: string, sub: string, done: bool}>
 */
function qr_minimal_store_order_steps( WC_Order $order ) {
	$status = $order->get_status();
	if ( in_array( $status, array( 'cancelled', 'refunded', 'failed' ), true ) ) {
		return array();
	}
	$date   = static fn( $d ) => $d ? wc_format_datetime( $d, 'j M' ) : '';
	$paid      = $order->get_date_paid() || in_array( $status, array( 'processing', 'completed', 'delivered' ), true );
	$delivered = 'delivered' === $status;
	$sent      = $delivered || 'completed' === $status;
	$sent_date = $order->get_date_completed() ? $order->get_date_completed() : ( $delivered ? null : $order->get_date_modified() );
	$method = $order->get_payment_method_title();
	if ( $paid ) {
		$paid_sub = trim( $method . ( $order->get_date_paid() ? ', ' . $date( $order->get_date_paid() ) : '' ), ', ' );
	} else {
		$paid_sub = 'on-hold' === $status ? __( 'Checking your payment', 'qr-minimal-store' ) : __( 'Awaiting payment', 'qr-minimal-store' );
	}
	return array(
		array(
			'label' => __( 'Order placed', 'qr-minimal-store' ),
			'sub'   => $date( $order->get_date_created() ),
			'done'  => true,
		),
		array(
			'label' => __( 'Paid', 'qr-minimal-store' ),
			'sub'   => $paid_sub,
			'done'  => $paid,
		),
		array(
			'label' => __( 'Sent to courier', 'qr-minimal-store' ),
			'sub'   => $sent ? $date( $sent_date ) : '',
			'done'  => $sent,
		),
		array(
			'label' => __( 'Delivered', 'qr-minimal-store' ),
			'sub'   => $delivered ? $date( qr_minimal_store_order_date_delivered( $order ) ) : __( '2–3 working days after sending', 'qr-minimal-store' ),
			'done'  => $delivered,
		),
	);
}

/**
 * One sentence on where the order is.
 *
 * @param WC_Order $order Order.
 * @return string
 */
function qr_minimal_store_order_status_text( WC_Order $order ) {
	switch ( $order->get_status() ) {
		case 'pending':
			return __( 'We have not received payment for this order yet.', 'qr-minimal-store' );
		case 'on-hold':
			return __( 'We are checking your payment and will email you as soon as it is confirmed.', 'qr-minimal-store' );
		case 'processing':
			return __( 'Paid. We are packing your order and will email you when it goes to the courier.', 'qr-minimal-store' );
		case 'completed':
			$sent = $order->get_date_completed() ? $order->get_date_completed() : $order->get_date_modified();
			/* translators: %s: date the order went to the courier */
			return sprintf( __( 'Sent to the courier on %s. Delivery in 2–3 working days.', 'qr-minimal-store' ), wc_format_datetime( $sent, 'j M' ) );
		case 'delivered':
			$on = qr_minimal_store_order_date_delivered( $order );
			/* translators: %s: delivery date */
			return $on ? sprintf( __( 'Delivered on %s. Unused items can be returned within 30 days.', 'qr-minimal-store' ), wc_format_datetime( $on, 'j M' ) ) : __( 'Delivered. Unused items can be returned within 30 days.', 'qr-minimal-store' );
		case 'cancelled':
			return __( 'This order was cancelled.', 'qr-minimal-store' );
		case 'refunded':
			return __( 'This order was refunded.', 'qr-minimal-store' );
		case 'failed':
			return __( 'The payment for this order did not go through.', 'qr-minimal-store' );
	}
	return '';
}

/**
 * "Xiaomi Smart Air Fryer 6.5L" / "Mouse + Keyboard" / "A + B and 2 more".
 *
 * @param WC_Order $order Order.
 * @return string
 */
function qr_minimal_store_order_items_text( WC_Order $order ) {
	$names = array();
	foreach ( $order->get_items() as $item ) {
		$qty     = (int) $item->get_quantity();
		$names[] = $item->get_name() . ( $qty > 1 ? ' × ' . $qty : '' );
	}
	if ( count( $names ) > 2 ) {
		/* translators: 1: first two item names, 2: number of other items */
		return sprintf( __( '%1$s and %2$d more', 'qr-minimal-store' ), implode( ' + ', array_slice( $names, 0, 2 ) ), count( $names ) - 2 );
	}
	return implode( ' + ', $names );
}

/**
 * Image of the order's first product.
 *
 * @param WC_Order $order Order.
 * @param string   $size  Image size.
 * @return string
 */
function qr_minimal_store_order_thumb( WC_Order $order, $size = 'woocommerce_gallery_thumbnail' ) {
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( $product ) {
			return $product->get_image( $size, array( 'alt' => '' ) );
		}
	}
	return '';
}

/**
 * Buy again link for the signed-in owner (WooCommerce "Order again"), or "".
 *
 * @param WC_Order $order Order.
 * @return string
 */
function qr_minimal_store_order_again_url( WC_Order $order ) {
	$statuses = apply_filters( 'woocommerce_valid_order_statuses_for_order_again', array( 'completed', 'delivered' ) );
	if ( ! is_user_logged_in() || (int) $order->get_customer_id() !== get_current_user_id() || ! $order->has_status( $statuses ) ) {
		return '';
	}
	return wp_nonce_url( add_query_arg( 'order_again', $order->get_id(), wc_get_cart_url() ), 'woocommerce-order_again' );
}

/**
 * Request a return: mailto with the order number, for orders sent to the courier or delivered.
 *
 * @param WC_Order $order Order.
 * @return string
 */
function qr_minimal_store_order_return_url( WC_Order $order ) {
	if ( ! $order->has_status( array( 'completed', 'delivered' ) ) ) {
		return '';
	}
	/* translators: %s: order number */
	$subject = sprintf( __( 'Return request: order #%s', 'qr-minimal-store' ), $order->get_order_number() );
	return 'mailto:' . QR_RETURNS_EMAIL . '?subject=' . rawurlencode( $subject );
}

/**
 * Order detail (account "View order" and the Track an order result).
 *
 * @param WC_Order $order   Order.
 * @param string   $context 'account' or 'track'.
 */
function qr_minimal_store_order_detail( WC_Order $order, $context = 'account' ) {
	$status   = qr_minimal_store_order_status( $order );
	$steps    = qr_minimal_store_order_steps( $order );
	$tracking = qr_minimal_store_order_tracking( $order );
	$again    = qr_minimal_store_order_again_url( $order );
	$return   = qr_minimal_store_order_return_url( $order );
	$notes    = $order->get_customer_order_notes();
	$back     = 'track' === $context ? wc_get_page_permalink( 'myaccount' ) : wc_get_account_endpoint_url( 'orders' );
	$address  = $order->get_formatted_shipping_address();
	$address  = $address ? $address : $order->get_formatted_billing_address();
	$placed   = wc_format_datetime( $order->get_date_created(), 'j M Y' );
	$method   = $order->get_payment_method_title();
	?>
	<div class="qr-order">
		<div class="qr-order__main">
			<a class="qr-order__back" href="<?php echo esc_url( $back ); ?>">&larr; <?php echo 'track' === $context ? esc_html__( 'Back', 'qr-minimal-store' ) : esc_html__( 'Your orders', 'qr-minimal-store' ); ?></a>
			<h1 class="qr-order__title">
				<?php
				/* translators: %s: order number */
				echo esc_html( sprintf( __( 'Order #%s', 'qr-minimal-store' ), $order->get_order_number() ) );
				?>
				<span class="qr-pill qr-pill--<?php echo esc_attr( $status['tone'] ); ?>"><?php echo esc_html( $status['label'] ); ?></span>
			</h1>
			<p class="qr-order__meta">
				<?php
				if ( $order->is_paid() && $method ) {
					/* translators: 1: order date, 2: payment method */
					echo esc_html( sprintf( __( 'Placed %1$s · paid with %2$s', 'qr-minimal-store' ), $placed, $method ) );
				} else {
					/* translators: %s: order date */
					echo esc_html( sprintf( __( 'Placed %s', 'qr-minimal-store' ), $placed ) );
				}
				?>
			</p>

			<?php if ( $steps ) : ?>
				<ol class="qr-steps" aria-label="<?php esc_attr_e( 'Order progress', 'qr-minimal-store' ); ?>">
					<?php foreach ( $steps as $step ) : ?>
						<li class="qr-steps__item<?php echo $step['done'] ? ' is-done' : ''; ?>">
							<span class="qr-steps__bar" aria-hidden="true"></span>
							<span class="qr-steps__label"><?php echo esc_html( $step['label'] ); ?><span class="screen-reader-text"><?php echo $step['done'] ? esc_html__( ' (done)', 'qr-minimal-store' ) : esc_html__( ' (not yet)', 'qr-minimal-store' ); ?></span></span>
							<?php if ( '' !== $step['sub'] ) : ?>
								<span class="qr-steps__sub"><?php echo esc_html( $step['sub'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<div class="qr-order__status">
				<p><?php echo esc_html( qr_minimal_store_order_status_text( $order ) ); ?></p>
				<?php if ( $tracking ) : ?>
					<p class="qr-order__tracking">
						<?php
						echo esc_html(
							'' !== $tracking['courier']
								/* translators: 1: courier name, 2: tracking number */
								? sprintf( __( 'Courier: %1$s · tracking number %2$s', 'qr-minimal-store' ), $tracking['courier'], $tracking['number'] )
								/* translators: %s: tracking number */
								: sprintf( __( 'Tracking number: %s', 'qr-minimal-store' ), $tracking['number'] )
						);
						if ( '' !== $tracking['url'] ) {
							echo ' <a href="' . esc_url( $tracking['url'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Track the parcel', 'qr-minimal-store' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'qr-minimal-store' ) . '</span></a>';
						}
						?>
					</p>
				<?php endif; ?>
				<?php if ( $order->needs_payment() && 'account' === $context ) : ?>
					<a class="qr-btn" href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>"><?php esc_html_e( 'Pay now', 'qr-minimal-store' ); ?></a>
				<?php endif; ?>
			</div>

			<?php if ( $notes ) : ?>
				<section class="qr-order__updates">
					<h2><?php esc_html_e( 'Updates from us', 'qr-minimal-store' ); ?></h2>
					<ol>
						<?php foreach ( $notes as $note ) : ?>
							<li>
								<span class="qr-order__update-date"><?php echo esc_html( date_i18n( 'j M Y, H:i', strtotime( $note->comment_date ) ) ); ?></span>
								<?php echo wp_kses_post( make_clickable( wpautop( wptexturize( $note->comment_content ) ) ) ); ?>
							</li>
						<?php endforeach; ?>
					</ol>
				</section>
			<?php endif; ?>

			<ul class="qr-order__items">
				<?php foreach ( $order->get_items() as $item ) : ?>
					<?php
					$product = $item->get_product();
					$link    = $product && $product->is_visible() ? $product->get_permalink() : '';
					?>
					<li class="qr-order__item">
						<span class="qr-order__thumb"><?php echo $product ? $product->get_image( 'woocommerce_gallery_thumbnail', array( 'alt' => '' ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?></span>
						<span class="qr-order__item-text">
							<span class="qr-order__item-name">
								<?php if ( $link ) : ?>
									<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $item->get_name() ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $item->get_name() ); ?>
								<?php endif; ?>
							</span>
							<span class="qr-order__item-qty">
								<?php
								/* translators: %d: quantity */
								echo esc_html( sprintf( __( 'Qty %d', 'qr-minimal-store' ), $item->get_quantity() ) );
								?>
							</span>
						</span>
						<span class="qr-order__item-price"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $again || $return ) : ?>
				<div class="qr-order__actions">
					<?php if ( $again ) : ?>
						<a class="qr-btn" href="<?php echo esc_url( $again ); ?>"><?php esc_html_e( 'Buy again', 'qr-minimal-store' ); ?></a>
					<?php endif; ?>
					<?php if ( $return ) : ?>
						<a class="qr-btn qr-btn--outline" href="<?php echo esc_url( $return ); ?>"><?php esc_html_e( 'Request a return', 'qr-minimal-store' ); ?></a>
					<?php endif; ?>
				</div>
				<?php if ( $return ) : ?>
					<p class="qr-order__note">
						<?php
						/* translators: %s: returns email address */
						echo esc_html( sprintf( __( 'Returns: unused items within 30 days of delivery. The button opens an email to %s with your order number filled in.', 'qr-minimal-store' ), QR_RETURNS_EMAIL ) );
						?>
					</p>
				<?php endif; ?>
			<?php endif; ?>

		</div>

		<aside class="qr-order__aside" aria-label="<?php esc_attr_e( 'Delivery and totals', 'qr-minimal-store' ); ?>">
			<?php if ( $address ) : ?>
				<div>
					<h2 class="qr-order__aside-title"><?php esc_html_e( 'Delivery address', 'qr-minimal-store' ); ?></h2>
					<address><?php echo wp_kses_post( $address ); ?></address>
				</div>
			<?php endif; ?>
			<dl class="qr-order__totals">
				<div><dt><?php esc_html_e( 'Subtotal', 'qr-minimal-store' ); ?></dt><dd><?php echo wp_kses_post( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ); ?></dd></div>
				<?php if ( $order->get_total_discount() > 0 ) : ?>
					<div><dt><?php esc_html_e( 'Discount', 'qr-minimal-store' ); ?></dt><dd>&minus;<?php echo wp_kses_post( wc_price( $order->get_total_discount(), array( 'currency' => $order->get_currency() ) ) ); ?></dd></div>
				<?php endif; ?>
				<?php foreach ( $order->get_fees() as $fee ) : ?>
					<div><dt><?php echo esc_html( $fee->get_name() ); ?></dt><dd><?php echo wp_kses_post( wc_price( $fee->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></dd></div>
				<?php endforeach; ?>
				<?php if ( $order->get_shipping_method() ) : ?>
					<div><dt><?php esc_html_e( 'Delivery', 'qr-minimal-store' ); ?></dt><dd><?php echo (float) $order->get_shipping_total() > 0 ? wp_kses_post( wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) ) : esc_html__( 'Free', 'qr-minimal-store' ); ?></dd></div>
				<?php endif; ?>
				<?php if ( wc_tax_enabled() && (float) $order->get_total_tax() > 0 ) : ?>
					<div><dt><?php esc_html_e( 'VAT', 'qr-minimal-store' ); ?></dt><dd><?php echo wp_kses_post( wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ) ); ?></dd></div>
				<?php endif; ?>
				<div class="qr-order__total"><dt><?php esc_html_e( 'Total', 'qr-minimal-store' ); ?></dt><dd><?php echo wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></dd></div>
				<?php if ( $order->get_total_refunded() > 0 ) : ?>
					<div><dt><?php esc_html_e( 'Refunded', 'qr-minimal-store' ); ?></dt><dd>&minus;<?php echo wp_kses_post( wc_price( $order->get_total_refunded(), array( 'currency' => $order->get_currency() ) ) ); ?></dd></div>
				<?php endif; ?>
			</dl>
			<?php if ( ! wc_tax_enabled() ) : ?>
				<p class="qr-order__vat"><?php esc_html_e( 'No VAT added', 'qr-minimal-store' ); ?></p>
			<?php endif; ?>
			<p class="qr-order__help">
				<?php esc_html_e( 'Questions about this order?', 'qr-minimal-store' ); ?>
				<?php /* translators: %s: order number */ ?>
				<a href="<?php echo esc_url( 'mailto:' . QR_ORDERS_EMAIL . '?subject=' . rawurlencode( sprintf( __( 'Order #%s', 'qr-minimal-store' ), $order->get_order_number() ) ) ); ?>"><?php echo esc_html( QR_ORDERS_EMAIL ); ?></a>
			</p>
		</aside>
	</div>
	<?php
}

/* ---------- Track an order (signed out) ---------- */

/**
 * Rate-limit key: a keyed hash of the visitor's IP. The raw IP is never stored (POPIA).
 *
 * @return string
 */
function qr_minimal_store_track_limit_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return 'qr_track_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
}

/**
 * Handle a Track an order submission (POST to the account page).
 *
 * @return array{order: WC_Order|null, error: string, number: string, email: string}|null Null when nothing was submitted.
 */
function qr_minimal_store_track_order_result() {
	static $result = false;
	if ( false !== $result ) {
		return $result;
	}
	$result = null;
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['qr_track_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked below.
		return $result;
	}
	$number = sanitize_text_field( wp_unslash( $_POST['qr_track_order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$email  = isset( $_POST['qr_track_email'] ) ? sanitize_email( wp_unslash( $_POST['qr_track_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$result = array(
		'order'  => null,
		'error'  => '',
		'number' => $number,
		'email'  => $email,
	);
	if ( ! isset( $_POST['qr_track_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['qr_track_nonce'] ) ), 'qr_track_order' ) ) {
		$result['error'] = __( 'This form expired. Please try again.', 'qr-minimal-store' );
		return $result;
	}
	$key   = qr_minimal_store_track_limit_key();
	$fails = (int) get_transient( $key );
	if ( $fails >= QR_TRACK_MAX_FAILS ) {
		/* translators: %s: orders email address */
		$result['error'] = sprintf( __( 'Too many tries. Please wait 10 minutes, or email %s with your order number.', 'qr-minimal-store' ), QR_ORDERS_EMAIL );
		return $result;
	}
	$id    = absint( apply_filters( 'woocommerce_shortcode_order_tracking_order_id', ltrim( $number, '#' ) ) );
	$order = $id && is_email( $email ) ? wc_get_order( $id ) : false;
	if ( $order instanceof WC_Order && ! $order instanceof WC_Order_Refund && '' !== $order->get_billing_email()
		&& hash_equals( strtolower( $order->get_billing_email() ), strtolower( $email ) ) ) {
		$result['order'] = $order;
		return $result;
	}
	set_transient( $key, $fails + 1, QR_TRACK_WINDOW_SECS );
	$result['error'] = __( 'We could not find an order with that number and email. Check both against your order confirmation email.', 'qr-minimal-store' );
	return $result;
}

// Track results are personal: never cache them.
add_action( 'template_redirect', 'qr_minimal_store_track_no_cache' );
function qr_minimal_store_track_no_cache() {
	if ( isset( $_POST['qr_track_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		nocache_headers();
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
}

/* ---------- Password pages: heading (the account page prints no title) ---------- */

add_action( 'woocommerce_before_lost_password_form', 'qr_minimal_store_lost_password_title' );
function qr_minimal_store_lost_password_title() {
	echo '<h1 class="qr-auth__title">' . esc_html__( 'Forgot your password?', 'qr-minimal-store' ) . '</h1>';
}

add_action( 'woocommerce_before_reset_password_form', 'qr_minimal_store_reset_password_title' );
function qr_minimal_store_reset_password_title() {
	echo '<h1 class="qr-auth__title">' . esc_html__( 'Choose a new password', 'qr-minimal-store' ) . '</h1>';
}

add_action( 'woocommerce_before_lost_password_confirmation_message', 'qr_minimal_store_lost_password_sent_title' );
function qr_minimal_store_lost_password_sent_title() {
	echo '<h1 class="qr-auth__title">' . esc_html__( 'Check your inbox', 'qr-minimal-store' ) . '</h1>';
}
