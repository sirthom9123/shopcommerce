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
			<p><?php esc_html_e( 'Welcome to our electronics store', 'qr-minimal-store' ); ?></p>
			<nav class="utility-nav" aria-label="<?php esc_attr_e( 'Utility navigation', 'qr-minimal-store' ); ?>">
				<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop', 'qr-minimal-store' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>"><?php esc_html_e( 'My Account', 'qr-minimal-store' ); ?></a>
			</nav>
		</div>
	</div>
	<div class="header-main">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<span class="brand__name"><?php bloginfo( 'name' ); ?></span><span class="brand__dot" aria-hidden="true">.</span>
		</a>
		<nav class="header-main__navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'qr-minimal-store' ); ?>">
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
		<div class="header-search"><?php qr_minimal_store_product_search_bar(); ?></div>
		<div class="header-actions">
			<a class="header-action" href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>">
				<span aria-hidden="true">♡</span><span class="header-action__label"><?php esc_html_e( 'Account', 'qr-minimal-store' ); ?></span>
			</a>
			<a class="header-action cart-link" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>">
				<span aria-hidden="true">🛒</span><span class="header-action__label"><?php esc_html_e( 'Cart', 'qr-minimal-store' ); ?></span>
				<?php if ( function_exists( 'WC' ) && WC()->cart ) : ?>
					<span class="cart-count"><?php echo esc_html( (string) WC()->cart->get_cart_contents_count() ); ?></span>
				<?php endif; ?>
			</a>
		</div>
	</div>
	<nav class="site-header__category-strip" aria-label="<?php esc_attr_e( 'Product categories', 'qr-minimal-store' ); ?>">
		<div class="container category-navigation">
			<strong><?php esc_html_e( 'Browse Categories', 'qr-minimal-store' ); ?></strong>
			<?php qr_minimal_store_render_category_links( 8 ); ?>
		</div>
	</nav>
</header>
<main class="site-main">
