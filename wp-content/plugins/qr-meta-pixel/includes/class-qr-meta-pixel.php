<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QR_Meta_Pixel {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	private $queued_events = array();

	/**
	 * @var WC_Order|null
	 */
	private $purchase_order = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'wp_head', array( $this, 'print_base_code' ), 1 );
		add_action( 'wp_body_open', array( $this, 'print_noscript' ), 1 );
		add_action( 'wp', array( $this, 'queue_contextual_events' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_events_script' ) );
	}

	/**
	 * Digit-only Pixel ID from wp-config constant or the stored option.
	 */
	public static function pixel_id() {
		$id = '';
		if ( defined( 'QR_META_PIXEL_ID' ) && QR_META_PIXEL_ID ) {
			$id = (string) QR_META_PIXEL_ID;
		} else {
			$id = (string) get_option( QR_META_PIXEL_OPTION, '' );
		}

		return preg_replace( '/[^0-9]/', '', $id );
	}

	public static function is_configured() {
		return strlen( self::pixel_id() ) >= 10;
	}

	private function should_print() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}
		if ( is_customize_preview() ) {
			return false;
		}
		if ( ! self::is_configured() ) {
			return false;
		}
		if ( is_user_logged_in() && current_user_can( 'manage_woocommerce' ) && 'yes' === get_option( 'qr_meta_pixel_skip_staff', 'yes' ) ) {
			return false;
		}

		return true;
	}

	public function print_base_code() {
		if ( ! $this->should_print() ) {
			return;
		}

		$pixel_id = self::pixel_id();
		$init     = array( $pixel_id );
		$am       = $this->advanced_matching();
		if ( ! empty( $am ) ) {
			$init[] = $am;
		}

		$events = $this->queued_events;
		?>
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', <?php echo wp_json_encode( $init[0] ); ?><?php echo isset( $init[1] ) ? ', ' . wp_json_encode( $init[1] ) : ''; ?>);
fbq('track', 'PageView');
<?php foreach ( $events as $event ) : ?>
fbq('track', <?php echo wp_json_encode( $event['name'] ); ?><?php echo ! empty( $event['params'] ) ? ', ' . wp_json_encode( $event['params'] ) : ''; ?>);
<?php endforeach; ?>
</script>
<!-- End Meta Pixel Code -->
		<?php

		if ( $this->purchase_order ) {
			$this->purchase_order->update_meta_data( '_qr_meta_pixel_purchase', '1' );
			$this->purchase_order->save();
			$this->purchase_order = null;
		}
	}

	public function print_noscript() {
		if ( ! $this->should_print() ) {
			return;
		}

		$pixel_id = self::pixel_id();
		$src      = 'https://www.facebook.com/tr?id=' . rawurlencode( $pixel_id ) . '&ev=PageView&noscript=1';
		echo '<noscript><img height="1" width="1" style="display:none" src="' . esc_url( $src ) . '" alt="" /></noscript>' . "\n";
	}

	public function queue_contextual_events() {
		if ( ! $this->should_print() || ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			$product = wc_get_product( get_the_ID() );
			if ( $product ) {
				$this->queued_events[] = array(
					'name'   => 'ViewContent',
					'params' => $this->product_params( $product ),
				);
			}
			return;
		}

		if ( function_exists( 'is_page_template' ) && is_page_template( 'page-spring-bonanza.php' ) ) {
			$this->queued_events[] = array(
				'name'   => 'ViewContent',
				'params' => array(
					'content_name'     => 'Spring Bonanza',
					'content_category' => 'spring-bonanza-2026',
					'content_type'     => 'product_group',
					'currency'         => get_woocommerce_currency(),
				),
			);
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() && ! is_wc_endpoint_url( 'order-pay' ) ) {
			$this->queued_events[] = array(
				'name'   => 'InitiateCheckout',
				'params' => $this->cart_params(),
			);
		}

		if ( is_search() ) {
			$query = get_search_query();
			if ( '' !== $query ) {
				$this->queued_events[] = array(
					'name'   => 'Search',
					'params' => array(
						'search_string' => $query,
					),
				);
			}
		}

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			$order_id = absint( get_query_var( 'order-received' ) );
			if ( $order_id ) {
				$this->queue_purchase( $order_id );
			}
		}
	}

	/**
	 * @param int $order_id Order ID.
	 */
	public function queue_purchase( $order_id ) {
		if ( ! $this->should_print() ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( $order->get_meta( '_qr_meta_pixel_purchase' ) ) {
			return;
		}

		$contents = array();
		$ids      = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$pid     = $product ? (string) $product->get_id() : (string) $item->get_product_id();
			if ( '' === $pid ) {
				continue;
			}
			$ids[]      = $pid;
			$contents[] = array(
				'id'       => $pid,
				'quantity' => (int) $item->get_quantity(),
			);
		}

		$this->queued_events[] = array(
			'name'   => 'Purchase',
			'params' => array(
				'content_ids'  => $ids,
				'content_type' => 'product',
				'contents'     => $contents,
				'value'        => (float) $order->get_total(),
				'currency'     => $order->get_currency(),
			),
		);

		$this->purchase_order = $order;
	}

	public function enqueue_events_script() {
		if ( ! $this->should_print() ) {
			return;
		}

		$path = QR_META_PIXEL_PATH . 'assets/js/events.js';
		wp_enqueue_script(
			'qr-meta-pixel-events',
			QR_META_PIXEL_URL . 'assets/js/events.js',
			array( 'jquery' ),
			file_exists( $path ) ? (string) filemtime( $path ) : QR_META_PIXEL_VERSION,
			true
		);

		wp_localize_script(
			'qr-meta-pixel-events',
			'qrMetaPixel',
			array(
				'currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'ZAR',
			)
		);
	}

	/**
	 * @param WC_Product $product Product.
	 * @return array<string, mixed>
	 */
	private function product_params( $product ) {
		$price = (float) $product->get_price();

		return array(
			'content_name' => $product->get_name(),
			'content_ids'  => array( (string) $product->get_id() ),
			'content_type' => 'product',
			'value'        => $price,
			'currency'     => get_woocommerce_currency(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function cart_params() {
		$contents = array();
		$ids      = array();
		$value    = 0.0;

		if ( WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				$pid = isset( $item['variation_id'] ) && $item['variation_id'] ? (int) $item['variation_id'] : (int) $item['product_id'];
				if ( $pid < 1 ) {
					continue;
				}
				$qty        = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
				$ids[]      = (string) $pid;
				$contents[] = array(
					'id'       => (string) $pid,
					'quantity' => $qty,
				);
			}
			$value = (float) WC()->cart->get_total( 'edit' );
		}

		return array(
			'content_ids'  => $ids,
			'content_type' => 'product',
			'contents'     => $contents,
			'value'        => $value,
			'currency'     => get_woocommerce_currency(),
			'num_items'    => count( $contents ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function advanced_matching() {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$user = wp_get_current_user();
		$data = array();

		if ( $user && is_email( $user->user_email ) ) {
			$data['em'] = strtolower( $user->user_email );
		}
		if ( $user && $user->first_name ) {
			$data['fn'] = strtolower( $user->first_name );
		}
		if ( $user && $user->last_name ) {
			$data['ln'] = strtolower( $user->last_name );
		}

		if ( function_exists( 'WC' ) && WC()->customer ) {
			$phone = WC()->customer->get_billing_phone();
			if ( $phone ) {
				$data['ph'] = preg_replace( '/\D+/', '', $phone );
			}
			$city = WC()->customer->get_billing_city();
			if ( $city ) {
				$data['ct'] = strtolower( $city );
			}
			$country = WC()->customer->get_billing_country();
			if ( $country ) {
				$data['country'] = strtolower( $country );
			}
		}

		return $data;
	}
}
