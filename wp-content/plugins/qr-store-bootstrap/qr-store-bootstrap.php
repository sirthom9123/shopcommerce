<?php
/**
 * Plugin Name: QR Store Bootstrap
 * Description: Foundation bootstrap for the minimalist WooCommerce storefront.
 * Version: 1.0.0
 * Author: QR Store
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QR_STORE_BOOTSTRAP_OPTION', 'qr_store_bootstrap_initialized' );
define( 'QR_STORE_CATALOG_SEEDED_OPTION', 'qr_store_catalog_seeded' );

add_action( 'before_woocommerce_init', 'qr_store_declare_hpos_compatibility' );
function qr_store_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}

add_action( 'admin_notices', 'qr_store_bootstrap_admin_notice' );
function qr_store_bootstrap_admin_notice() {
	if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'QR Store Bootstrap is active. Install and activate WooCommerce to finish store setup.', 'qr-store-bootstrap' );
		echo '</p></div>';
	}
}

add_action( 'admin_init', 'qr_store_maybe_bootstrap' );
function qr_store_maybe_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( ! get_option( QR_STORE_BOOTSTRAP_OPTION ) ) {
		$page_ids = qr_store_create_core_pages();
		qr_store_apply_woocommerce_options( $page_ids );
		qr_store_ensure_permalink_structure();
		qr_store_ensure_product_attributes();

		update_option( QR_STORE_BOOTSTRAP_OPTION, 1 );
	}

	qr_store_seed_starter_catalog();
}

function qr_store_create_core_pages() {
	$pages = array(
		'shop'      => array(
			'title'   => 'Shop',
			'content' => '',
		),
		'cart'      => array(
			'title'   => 'Cart',
			'content' => '[woocommerce_cart]',
		),
		'checkout'  => array(
			'title'   => 'Checkout',
			'content' => '[woocommerce_checkout]',
		),
		'myaccount' => array(
			'title'   => 'My Account',
			'content' => '[woocommerce_my_account]',
		),
	);

	$page_ids = array();

	foreach ( $pages as $key => $config ) {
		$existing = get_page_by_title( $config['title'] );
		if ( $existing && isset( $existing->ID ) ) {
			$page_ids[ $key ] = (int) $existing->ID;
			continue;
		}

		$page_ids[ $key ] = wp_insert_post(
			array(
				'post_title'   => $config['title'],
				'post_content' => $config['content'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);
	}

	return $page_ids;
}

function qr_store_apply_woocommerce_options( $page_ids ) {
	if ( ! empty( $page_ids['shop'] ) ) {
		update_option( 'woocommerce_shop_page_id', (int) $page_ids['shop'] );
	}
	if ( ! empty( $page_ids['cart'] ) ) {
		update_option( 'woocommerce_cart_page_id', (int) $page_ids['cart'] );
	}
	if ( ! empty( $page_ids['checkout'] ) ) {
		update_option( 'woocommerce_checkout_page_id', (int) $page_ids['checkout'] );
	}
	if ( ! empty( $page_ids['myaccount'] ) ) {
		update_option( 'woocommerce_myaccount_page_id', (int) $page_ids['myaccount'] );
	}

	update_option( 'woocommerce_currency', 'ZAR' );
	update_option( 'woocommerce_weight_unit', 'kg' );
	update_option( 'woocommerce_dimension_unit', 'cm' );
	update_option( 'woocommerce_enable_guest_checkout', 'yes' );
}

function qr_store_ensure_permalink_structure() {
	$desired_structure = '/%postname%/';
	$current_structure = get_option( 'permalink_structure' );

	if ( $current_structure !== $desired_structure ) {
		update_option( 'permalink_structure', $desired_structure );
		flush_rewrite_rules();
	}
}

function qr_store_ensure_product_attributes() {
	if ( ! function_exists( 'wc_create_attribute' ) ) {
		return;
	}

	$attributes = array(
		array(
			'name' => 'Brand',
			'slug' => 'brand',
		),
		array(
			'name' => 'Supplier',
			'slug' => 'supplier',
		),
	);

	$existing = wc_get_attribute_taxonomies();
	$existing_slugs = array();

	foreach ( $existing as $attribute ) {
		$existing_slugs[] = $attribute->attribute_name;
	}

	foreach ( $attributes as $attribute ) {
		if ( in_array( $attribute['slug'], $existing_slugs, true ) ) {
			continue;
		}

		wc_create_attribute(
			array(
				'name'         => $attribute['name'],
				'slug'         => $attribute['slug'],
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
	}
}

function qr_store_seed_starter_catalog() {
	if ( get_option( QR_STORE_CATALOG_SEEDED_OPTION ) ) {
		return;
	}

	if ( ! class_exists( 'WC_Product_Simple' ) || ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return;
	}

	$categories = array(
		array(
			'name'        => 'UPS',
			'slug'        => 'ups',
			'description' => 'Uninterruptible power supply systems for home and office.',
		),
		array(
			'name'        => 'CCTV',
			'slug'        => 'cctv',
			'description' => 'Security cameras, DVRs, and smart monitoring gear.',
		),
		array(
			'name'        => 'Peripherals',
			'slug'        => 'peripherals',
			'description' => 'Keyboards, mice, hubs, and daily productivity accessories.',
		),
		array(
			'name'        => 'Smart Home',
			'slug'        => 'smart-home',
			'description' => 'Connected devices for safer and smarter spaces.',
		),
	);

	foreach ( $categories as $category ) {
		if ( term_exists( $category['slug'], 'product_cat' ) ) {
			continue;
		}

		wp_insert_term(
			$category['name'],
			'product_cat',
			array(
				'slug'        => $category['slug'],
				'description' => $category['description'],
			)
		);
	}

	$products = array(
		array(
			'name'        => 'QR UPS 1200VA Line Interactive',
			'sku'         => 'QR-UPS-1200',
			'price'       => '2299',
			'stock'       => 24,
			'category'    => 'ups',
			'description' => 'Reliable backup power with surge protection for routers, PCs, and POS systems.',
		),
		array(
			'name'        => 'QR 4CH Smart CCTV Kit',
			'sku'         => 'QR-CCTV-4CH',
			'price'       => '3499',
			'stock'       => 18,
			'category'    => 'cctv',
			'description' => 'Four-camera setup with night vision, motion alerts, and mobile viewing.',
		),
		array(
			'name'        => 'QR Wireless Productivity Combo',
			'sku'         => 'QR-PER-SET1',
			'price'       => '799',
			'stock'       => 60,
			'category'    => 'peripherals',
			'description' => 'Ergonomic wireless keyboard and mouse combo for everyday office use.',
		),
		array(
			'name'        => 'QR Smart Indoor Plug Twin Pack',
			'sku'         => 'QR-SMART-PLUG2',
			'price'       => '599',
			'stock'       => 40,
			'category'    => 'smart-home',
			'description' => 'Wi-Fi smart plugs with scheduling, timer, and voice assistant support.',
		),
	);

	foreach ( $products as $product_data ) {
		if ( wc_get_product_id_by_sku( $product_data['sku'] ) ) {
			continue;
		}

		$product = new WC_Product_Simple();
		$product->set_name( $product_data['name'] );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_sku( $product_data['sku'] );
		$product->set_regular_price( $product_data['price'] );
		$product->set_short_description( $product_data['description'] );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( (int) $product_data['stock'] );
		$product->set_stock_status( 'instock' );

		$category_term = get_term_by( 'slug', $product_data['category'], 'product_cat' );
		if ( $category_term && ! is_wp_error( $category_term ) ) {
			$product->set_category_ids( array( (int) $category_term->term_id ) );
		}

		$product->save();
	}

	update_option( QR_STORE_CATALOG_SEEDED_OPTION, 1 );
}
