<?php
/**
 * Plugin Name: QR Store — Meta Pixel
 * Description: Facebook / Meta Pixel base code plus WooCommerce standard events (ViewContent, AddToCart, InitiateCheckout, Purchase, Search).
 * Version: 1.0.0
 * Author: Yehuda Solutions
 * Requires at least: 6.6
 * Requires Plugins: woocommerce
 * WC requires at least: 9.0
 * WC tested up to: 11.1
 * Text Domain: qr-meta-pixel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QR_META_PIXEL_VERSION', '1.0.0' );
define( 'QR_META_PIXEL_FILE', __FILE__ );
define( 'QR_META_PIXEL_PATH', plugin_dir_path( __FILE__ ) );
define( 'QR_META_PIXEL_URL', plugin_dir_url( __FILE__ ) );
define( 'QR_META_PIXEL_OPTION', 'qr_meta_pixel_id' );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

require_once QR_META_PIXEL_PATH . 'includes/class-qr-meta-pixel.php';
require_once QR_META_PIXEL_PATH . 'includes/class-qr-meta-pixel-admin.php';

add_action(
	'plugins_loaded',
	static function () {
		QR_Meta_Pixel::instance()->init();
		if ( is_admin() ) {
			QR_Meta_Pixel_Admin::instance()->init();
		}
	}
);
