<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QR_STORE_LEGAL_VERSION', '1.0.4' );

require_once __DIR__ . '/legal-content.php';

add_action( 'init', 'qr_store_ensure_legal_pages', 20 );
register_activation_hook( QR_STORE_BOOTSTRAP_FILE, 'qr_store_ensure_legal_pages' );

/**
 * Create or refresh Platform Terms, Returns Policy, and Privacy Policy.
 */
function qr_store_ensure_legal_pages() {
	if ( get_option( 'qr_store_legal_version' ) === QR_STORE_LEGAL_VERSION ) {
		if ( get_option( 'qr_store_legal_flush' ) ) {
			flush_rewrite_rules( false );
			delete_option( 'qr_store_legal_flush' );
		}
		return;
	}

	$ids = array();
	foreach ( qr_store_legal_page_defs() as $key => $def ) {
		$ids[ $key ] = qr_store_legal_locate_page( $def );
	}

	qr_store_legal_apply_options( $ids );

	$ctx = qr_store_legal_context();
	foreach ( qr_store_legal_page_defs() as $key => $def ) {
		qr_store_legal_write_page( $ids[ $key ], $def, $ctx );
	}

	qr_store_legal_enable_checkout_terms_checkbox();

	update_option( 'qr_store_legal_version', QR_STORE_LEGAL_VERSION );
	update_option( 'qr_store_legal_flush', 1 );
}

/**
 * Require agreement to Platform Terms on block checkout.
 */
function qr_store_legal_enable_checkout_terms_checkbox() {
	$page_id = (int) get_option( 'woocommerce_checkout_page_id' );
	if ( $page_id < 1 ) {
		return;
	}

	$post = get_post( $page_id );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$content = (string) $post->post_content;
	if ( false === strpos( $content, 'woocommerce/checkout-terms-block' ) ) {
		return;
	}
	if ( false !== strpos( $content, '"checkbox":true' ) ) {
		return;
	}

	$updated = str_replace(
		'<!-- wp:woocommerce/checkout-terms-block -->',
		'<!-- wp:woocommerce/checkout-terms-block {"checkbox":true} -->',
		$content
	);
	if ( $updated === $content ) {
		return;
	}

	$kses_removed = false;
	if ( function_exists( 'kses_remove_filters' ) ) {
		kses_remove_filters();
		$kses_removed = true;
	}

	wp_update_post(
		array(
			'ID'           => $page_id,
			'post_content' => $updated,
		)
	);

	if ( $kses_removed && function_exists( 'kses_init_filters' ) ) {
		kses_init_filters();
	}
}

/**
 * @return array<string, array<string, mixed>>
 */
function qr_store_legal_page_defs() {
	return array(
		'terms'   => array(
			'title'      => 'Platform Terms',
			'slug'       => 'platform-terms',
			'option'     => 'woocommerce_terms_page_id',
			'alt_slugs'  => array( 'terms', 'terms-and-conditions', 'terms-of-service' ),
			'content_cb' => 'qr_store_legal_html_terms',
		),
		'returns' => array(
			'title'      => 'Returns Policy',
			'slug'       => 'returns-policy',
			'option'     => 'woocommerce_refund_returns_page_id',
			'alt_slugs'  => array( 'refund_returns', 'refund-and-returns-policy', 'returns' ),
			'content_cb' => 'qr_store_legal_html_returns',
		),
		'privacy' => array(
			'title'      => 'Privacy Policy',
			'slug'       => 'privacy-policy',
			'option'     => 'wp_page_for_privacy_policy',
			'alt_slugs'  => array( 'privacy' ),
			'content_cb' => 'qr_store_legal_html_privacy',
		),
	);
}

/**
 * @param array<string, mixed> $def Page definition.
 * @return int
 */
function qr_store_legal_locate_page( $def ) {
	$option_id = (int) get_option( $def['option'] );
	if ( $option_id && get_post_type( $option_id ) === 'page' ) {
		return $option_id;
	}

	$slugs = array_merge( array( $def['slug'] ), $def['alt_slugs'] );
	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			return (int) $page->ID;
		}
	}

	$found = get_posts(
		array(
			'post_type'      => 'page',
			'title'          => $def['title'],
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! empty( $found ) ) {
		return (int) $found[0];
	}

	$id = wp_insert_post(
		array(
			'post_title'  => $def['title'],
			'post_name'   => $def['slug'],
			'post_status' => 'publish',
			'post_type'   => 'page',
			'post_author' => qr_store_legal_author_id(),
		),
		true
	);

	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * @param array<string, int> $ids Page IDs keyed by legal document.
 */
function qr_store_legal_apply_options( $ids ) {
	if ( ! empty( $ids['terms'] ) ) {
		update_option( 'woocommerce_terms_page_id', (int) $ids['terms'] );
	}
	if ( ! empty( $ids['returns'] ) ) {
		update_option( 'woocommerce_refund_returns_page_id', (int) $ids['returns'] );
	}
	if ( ! empty( $ids['privacy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', (int) $ids['privacy'] );
	}

	update_option(
		'woocommerce_checkout_privacy_policy_text',
		'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our [privacy_policy].'
	);
	update_option(
		'woocommerce_registration_privacy_policy_text',
		'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our [privacy_policy].'
	);
}

/**
 * @param int                  $page_id Page ID.
 * @param array<string, mixed> $def     Page definition.
 * @param array<string, string> $ctx    Content tokens.
 */
function qr_store_legal_write_page( $page_id, $def, $ctx ) {
	if ( $page_id < 1 || ! is_callable( $def['content_cb'] ) ) {
		return;
	}

	$html    = call_user_func( $def['content_cb'], $ctx );
	$content = "<!-- wp:html -->\n" . $html . "\n<!-- /wp:html -->";

	$kses_removed = false;
	if ( function_exists( 'kses_remove_filters' ) ) {
		kses_remove_filters();
		$kses_removed = true;
	}

	wp_update_post(
		array(
			'ID'           => $page_id,
			'post_title'   => $def['title'],
			'post_name'    => $def['slug'],
			'post_status'  => 'publish',
			'post_content' => $content,
			'post_type'    => 'page',
		)
	);

	if ( $kses_removed && function_exists( 'kses_init_filters' ) ) {
		kses_init_filters();
	}

	update_post_meta( $page_id, '_qr_legal_key', sanitize_key( $def['slug'] ) );
}

/**
 * @return int
 */
function qr_store_legal_author_id() {
	$admins = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ids',
		)
	);
	return ! empty( $admins ) ? (int) $admins[0] : 1;
}

add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', 'qr_store_legal_terms_checkbox_text' );
function qr_store_legal_terms_checkbox_text( $text ) {
	unset( $text );
	return __( 'I have read and agree to the [terms]', 'qr-store-bootstrap' );
}
