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
<header class="site-header">
	<div class="site-header__utility">
		<div class="container utility-row">
			<p><?php esc_html_e( 'Call us: +27 00 000 0000', 'qr-minimal-store' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>"><?php esc_html_e( 'My Account', 'qr-minimal-store' ); ?></a>
		</div>
	</div>
	<div class="container site-header__bar">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
		<div class="header-search"><?php qr_minimal_store_product_search_bar(); ?></div>
		<div class="header-actions">
			<a class="cart-link" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>">
				<?php esc_html_e( 'Cart', 'qr-minimal-store' ); ?>
				<?php if ( function_exists( 'WC' ) && WC()->cart ) : ?>
					<span class="cart-count"><?php echo esc_html( (string) WC()->cart->get_cart_contents_count() ); ?></span>
				<?php endif; ?>
			</a>
		</div>
	</div>
	<div class="site-header__category-strip">
		<div class="container category-strip">
			<nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary Menu', 'qr-minimal-store' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'fallback_cb'    => 'wp_page_menu',
						'menu_class'     => 'site-nav__menu',
					)
				);
				?>
			</nav>
			<?php qr_minimal_store_render_category_links( 6 ); ?>
		</div>
	</div>
</header>
<main class="site-main">
