<?php
/**
 * Account home = Orders (design board E). Plugins hooked to the dashboard (e.g. the
 * qr-account "confirm your email" notice) still run first.
 *
 * @package qr-minimal-store
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_account_dashboard' );

woocommerce_account_orders( 1 );
