<?php
/**
 * Apply the latest data/wc-product-export-*.csv stock quantities to WooCommerce.
 *
 * Usage: php tools/sync-inventory-from-csv.php
 */
define( 'WP_USE_THEMES', false );
require dirname( __DIR__ ) . '/wp-load.php';

if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'QR_Stock_Inventory_Sync' ) ) {
	fwrite( STDERR, "WooCommerce and QR Stock Markers must be active.\n" );
	exit( 1 );
}

QR_Stock_Inventory_Sync::ensure_wc_alerts();
$file = QR_Stock_Inventory_Sync::latest_csv();
if ( '' === $file ) {
	fwrite( STDERR, "No wc-product-export-*.csv found in /data.\n" );
	exit( 1 );
}

$result = QR_Stock_Inventory_Sync::apply( $file );
printf(
	"Synced %s\nUpdated: %d  Missing: %d  Skipped: %d  Low: %d  Out: %d\n",
	$result['file'],
	$result['updated'],
	count( $result['missing'] ),
	$result['skipped'],
	$result['low'],
	$result['out']
);
if ( ! empty( $result['missing'] ) ) {
	echo 'Missing IDs: ' . implode( ', ', $result['missing'] ) . "\n";
}
if ( ! empty( $result['errors'] ) ) {
	fwrite( STDERR, implode( "\n", $result['errors'] ) . "\n" );
	exit( 1 );
}
