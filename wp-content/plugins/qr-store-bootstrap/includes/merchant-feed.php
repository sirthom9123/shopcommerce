<?php
/**
 * Google Merchant Center feed fixes.
 *
 * 1. Backorder products need an availability date. Google for WooCommerce sends
 *    "backorder" for stock status onbackorder (set by store_sync) but no date, so
 *    Merchant Center rejects them. We send the supplier ETA (`_supplier_eta`, from
 *    the sync) plus the delivery days, or today plus a default lead time when there
 *    is no ETA or the ETA has passed. Stock status itself is never changed.
 * 2. Image file names must be plain ASCII. Supplier image URLs (sideloaded by the
 *    WooCommerce REST API) can contain an en dash or degree sign; every new upload is
 *    now saved under an ASCII name. Tools > Media file names renames existing files.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Days added to the supplier ETA: the store delivers 2-3 working days after stock lands. */
define( 'QR_STORE_BACKORDER_DELIVERY_DAYS', 3 );
/** Lead time used when a backorder product has no (future) supplier ETA. */
define( 'QR_STORE_BACKORDER_DEFAULT_DAYS', 14 );

/* ---------------------------------------------------------------------------
 * 1. availabilityDate for backorder products
 * ------------------------------------------------------------------------- */

add_filter( 'woocommerce_gla_product_attribute_values', 'qr_store_gla_backorder_date', 20, 2 );

/**
 * @param array      $attributes Google product attributes to override.
 * @param WC_Product $product    Simple product or variation being synced.
 * @return array
 */
function qr_store_gla_backorder_date( $attributes, $product ) {
	if ( ! is_array( $attributes ) || ! $product instanceof WC_Product ) {
		return $attributes;
	}
	if ( ! empty( $attributes['availabilityDate'] ) || 'onbackorder' !== $product->get_stock_status() ) {
		return $attributes;
	}
	$date = qr_store_backorder_available_date( $product );
	if ( $date ) {
		$attributes['availabilityDate'] = $date->format( DATE_ATOM );
	}
	return $attributes;
}

/**
 * Date the backorder product can be delivered: supplier ETA + delivery days, or
 * today + default lead time. Never in the past, never over a year ahead (Google limit).
 *
 * @return DateTimeImmutable|null
 */
function qr_store_backorder_available_date( WC_Product $product ) {
	$tz  = wp_timezone();
	$now = new DateTimeImmutable( 'today', $tz );

	$eta = trim( (string) $product->get_meta( '_supplier_eta' ) );
	if ( '' === $eta && $product->get_parent_id() ) {
		$parent = wc_get_product( $product->get_parent_id() );
		$eta    = $parent ? trim( (string) $parent->get_meta( '_supplier_eta' ) ) : '';
	}

	$date = null;
	if ( '' !== $eta ) {
		try {
			$eta_date = new DateTimeImmutable( $eta, $tz );
			if ( $eta_date >= $now ) {
				$date = $eta_date->modify( '+' . QR_STORE_BACKORDER_DELIVERY_DAYS . ' days' );
			}
		} catch ( Exception $e ) {
			$date = null;
		}
	}
	if ( ! $date ) {
		$days = (int) apply_filters( 'qr_store_backorder_default_days', QR_STORE_BACKORDER_DEFAULT_DAYS, $product );
		$date = $now->modify( '+' . max( 1, $days ) . ' days' );
	}

	$max = $now->modify( '+364 days' );
	return $date > $max ? $max : $date;
}

/* ---------------------------------------------------------------------------
 * 2. ASCII file names for uploads
 * ------------------------------------------------------------------------- */

add_filter( 'sanitize_file_name', 'qr_store_ascii_file_name', 20 );

/**
 * "PXN-V10-Ultra-270-900°-3.2nm-–-Wheel.jpg" -> "PXN-V10-Ultra-270-900-3.2nm-Wheel.jpg".
 *
 * @param string $filename Already sanitised by WordPress.
 * @return string
 */
function qr_store_ascii_file_name( $filename ) {
	$filename = (string) $filename;
	if ( ! preg_match( '/[^\x20-\x7E]|%/', $filename ) ) {
		return $filename;
	}
	$raw  = rawurldecode( $filename );
	$ext  = pathinfo( $raw, PATHINFO_EXTENSION );
	$ext  = preg_match( '/^[A-Za-z0-9]{1,10}$/', $ext ) ? '.' . strtolower( $ext ) : '';
	$stem = '' !== $ext ? substr( $raw, 0, -strlen( $ext ) ) : $raw;
	$stem = remove_accents( $stem );
	$stem = preg_replace( '/[^A-Za-z0-9._-]+/', '-', $stem );
	$stem = preg_replace( '/-{2,}/', '-', $stem );
	$stem = trim( $stem, '-.' );
	if ( '' === $stem ) {
		$stem = 'file-' . substr( md5( $filename ), 0, 8 );
	}
	return $stem . $ext;
}

/* ---------------------------------------------------------------------------
 * Tools > Media file names: rename existing non-ASCII media files
 * ------------------------------------------------------------------------- */

add_action( 'admin_menu', 'qr_store_media_names_menu' );
function qr_store_media_names_menu() {
	add_management_page(
		__( 'Media file names', 'qr-store-bootstrap' ),
		__( 'Media file names', 'qr-store-bootstrap' ),
		'manage_woocommerce',
		'qr-media-file-names',
		'qr_store_media_names_page'
	);
}

/**
 * Attachments whose stored file path is not plain ASCII.
 *
 * @return int[]
 */
function qr_store_non_ascii_attachments() {
	global $wpdb;
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
			'_wp_attached_file'
		)
	);
	$ids = array();
	foreach ( $rows as $row ) {
		if ( preg_match( '/[^\x20-\x7E]/', basename( (string) $row->meta_value ) ) ) {
			$ids[] = (int) $row->post_id;
		}
	}
	return $ids;
}

/**
 * Rename one attachment's file and all its generated sizes to ASCII names.
 *
 * @return true|WP_Error
 */
function qr_store_rename_attachment_ascii( $id ) {
	$rel = (string) get_post_meta( $id, '_wp_attached_file', true );
	$up  = wp_get_upload_dir();
	if ( '' === $rel || ! empty( $up['error'] ) ) {
		return new WP_Error( 'qr_no_file', 'No file recorded.' );
	}
	$sub  = dirname( $rel );
	$sub  = '.' === $sub ? '' : $sub . '/';
	$dir  = trailingslashit( $up['basedir'] ) . $sub;
	$meta = wp_get_attachment_metadata( $id );
	$meta = is_array( $meta ) ? $meta : array();

	// Every file on disk that belongs to this attachment: old basename => new basename.
	$names = array( basename( $rel ) );
	if ( ! empty( $meta['original_image'] ) ) {
		$names[] = $meta['original_image'];
	}
	foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
		if ( ! empty( $size['file'] ) ) {
			$names[] = $size['file'];
		}
	}
	$map = array();
	foreach ( array_unique( $names ) as $old ) {
		$new = qr_store_ascii_file_name( $old );
		if ( $new !== $old ) {
			$map[ $old ] = $new;
		}
	}
	if ( ! $map ) {
		return true;
	}
	foreach ( $map as $old => $new ) {
		if ( file_exists( $dir . $new ) ) {
			return new WP_Error( 'qr_exists', sprintf( 'A file named %s already exists; rename by hand.', $new ) );
		}
	}

	$done = array();
	foreach ( $map as $old => $new ) {
		if ( ! file_exists( $dir . $old ) ) {
			continue; // A size that was never generated or already removed.
		}
		if ( ! @rename( $dir . $old, $dir . $new ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			foreach ( $done as $o => $n ) {
				@rename( $dir . $n, $dir . $o ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
			return new WP_Error( 'qr_rename', sprintf( 'Could not rename %s.', $old ) );
		}
		$done[ $old ] = $new;
	}

	$main = basename( $rel );
	if ( isset( $map[ $main ] ) ) {
		update_attached_file( $id, $sub . $map[ $main ] );
		if ( isset( $meta['file'] ) ) {
			$meta['file'] = $sub . $map[ $main ];
		}
	}
	if ( ! empty( $meta['original_image'] ) && isset( $map[ $meta['original_image'] ] ) ) {
		$meta['original_image'] = $map[ $meta['original_image'] ];
	}
	if ( ! empty( $meta['sizes'] ) ) {
		foreach ( $meta['sizes'] as $k => $size ) {
			if ( ! empty( $size['file'] ) && isset( $map[ $size['file'] ] ) ) {
				$meta['sizes'][ $k ]['file'] = $map[ $size['file'] ];
			}
		}
	}
	wp_update_attachment_metadata( $id, $meta );
	clean_attachment_cache( $id );
	qr_store_touch_products_using_image( $id );
	return true;
}

/**
 * Save the products that show this image so Google for WooCommerce re-syncs them
 * with the new image link.
 */
function qr_store_touch_products_using_image( $id ) {
	global $wpdb;
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta}
			 WHERE ( meta_key = '_thumbnail_id' AND meta_value = %s )
			    OR ( meta_key = '_product_image_gallery' AND FIND_IN_SET( %s, meta_value ) )",
			(string) $id,
			(string) $id
		)
	);
	foreach ( array_unique( array_map( 'intval', $ids ) ) as $pid ) {
		$product = wc_get_product( $pid );
		if ( $product ) {
			$product->save();
			if ( $product->get_parent_id() ) {
				$parent = wc_get_product( $product->get_parent_id() );
				if ( $parent ) {
					$parent->save();
				}
			}
		}
	}
}

function qr_store_media_names_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'qr-store-bootstrap' ) );
	}
	$results = array();
	if ( isset( $_POST['qr_rename_media'] ) ) {
		check_admin_referer( 'qr_rename_media' );
		foreach ( qr_store_non_ascii_attachments() as $id ) {
			$before         = basename( (string) get_post_meta( $id, '_wp_attached_file', true ) );
			$res            = qr_store_rename_attachment_ascii( $id );
			$results[ $id ] = is_wp_error( $res )
				? $before . ': ' . $res->get_error_message()
				: $before . ' -> ' . basename( (string) get_post_meta( $id, '_wp_attached_file', true ) );
		}
	}
	$ids = qr_store_non_ascii_attachments();
	echo '<div class="wrap"><h1>' . esc_html__( 'Media file names', 'qr-store-bootstrap' ) . '</h1>';
	echo '<p>' . esc_html__( 'Google Merchant Center rejects image file names with characters such as an en dash or degree sign. New uploads already get plain names; this renames existing files (and their sizes) and re-saves the products that use them so Google re-syncs.', 'qr-store-bootstrap' ) . '</p>';
	if ( $results ) {
		echo '<h2>' . esc_html__( 'Result', 'qr-store-bootstrap' ) . '</h2><ul>';
		foreach ( $results as $id => $line ) {
			echo '<li>#' . (int) $id . ' ' . esc_html( $line ) . '</li>';
		}
		echo '</ul>';
	}
	if ( ! $ids ) {
		echo '<p><strong>' . esc_html__( 'All media file names are plain ASCII.', 'qr-store-bootstrap' ) . '</strong></p></div>';
		return;
	}
	echo '<h2>' . esc_html__( 'Files to rename', 'qr-store-bootstrap' ) . '</h2><ul>';
	foreach ( $ids as $id ) {
		$name = basename( (string) get_post_meta( $id, '_wp_attached_file', true ) );
		echo '<li>#' . (int) $id . ' ' . esc_html( $name ) . ' &rarr; ' . esc_html( qr_store_ascii_file_name( $name ) ) . '</li>';
	}
	echo '</ul><form method="post">';
	wp_nonce_field( 'qr_rename_media' );
	echo '<p><button type="submit" name="qr_rename_media" value="1" class="button button-primary">' . esc_html__( 'Rename these files', 'qr-store-bootstrap' ) . '</button></p></form></div>';
}
