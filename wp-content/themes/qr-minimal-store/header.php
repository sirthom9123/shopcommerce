<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php if ( qr_minimal_store_is_checkout_flow() ) : ?>
<header class="site-header site-header--checkout">
	<div class="container checkout-header">
		<?php qr_minimal_store_the_brand( 'header' ); ?>
		<p class="checkout-header__secure"><?php echo qr_minimal_store_trust_icon_svg( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG. ?><?php esc_html_e( 'Secure checkout', 'qr-minimal-store' ); ?></p>
		<a class="checkout-header__back" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>"><?php esc_html_e( 'Back to cart', 'qr-minimal-store' ); ?></a>
	</div>
</header>
<?php else : ?>
<header class="site-header site-header--main">
	<?php qr_minimal_store_offer_bar(); ?>
	<div class="container qr-hdr">
		<button class="nav-toggle qr-hdr__toggle" aria-expanded="false" aria-controls="primary-nav">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'qr-minimal-store' ); ?></span>
		</button>
		<?php qr_minimal_store_the_brand( 'header' ); ?>
		<?php qr_minimal_store_header_search(); ?>
		<nav class="qr-hdr__account" aria-label="<?php esc_attr_e( 'Account', 'qr-minimal-store' ); ?>">
			<?php $account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ); ?>
			<a class="qr-hdr__signin" href="<?php echo esc_url( $account_url ); ?>"><?php echo is_user_logged_in() ? esc_html__( 'Account', 'qr-minimal-store' ) : esc_html__( 'Sign in', 'qr-minimal-store' ); ?></a>
			<a class="qr-hdr__cart cart-link" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 4h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.1L21 8H6"/><circle cx="9.5" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
				<span class="qr-hdr__cart-label"><?php esc_html_e( 'Cart', 'qr-minimal-store' ); ?></span>
				<span class="cart-count"><?php echo esc_html( ( function_exists( 'WC' ) && WC()->cart ) ? (string) WC()->cart->get_cart_contents_count() : '0' ); ?></span>
				<span class="screen-reader-text"><?php esc_html_e( 'items', 'qr-minimal-store' ); ?></span>
			</a>
		</nav>
	</div>
	<nav id="primary-nav" class="qr-depts" aria-label="<?php esc_attr_e( 'Departments', 'qr-minimal-store' ); ?>">
		<ul class="container qr-depts__list">
			<?php qr_minimal_store_department_links( 7 ); ?>
			<li class="qr-depts__more"><a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop all products', 'qr-minimal-store' ); ?></a></li>
			<li class="qr-depts__more"><a href="<?php echo esc_url( $account_url ); ?>"><?php echo is_user_logged_in() ? esc_html__( 'My account', 'qr-minimal-store' ) : esc_html__( 'Sign in or create an account', 'qr-minimal-store' ); ?></a></li>
		</ul>
	</nav>
</header>
<?php endif; ?>
<main class="site-main">
