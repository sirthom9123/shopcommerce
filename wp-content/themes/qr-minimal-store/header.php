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
			<p><?php esc_html_e( 'Welcome to Yehuda Store', 'qr-minimal-store' ); ?></p>
			<nav class="utility-nav" aria-label="<?php esc_attr_e( 'Utility navigation', 'qr-minimal-store' ); ?>">
				<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop', 'qr-minimal-store' ); ?></a>
				<?php
				$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
				if ( is_user_logged_in() ) :
					?>
					<a href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'My Account', 'qr-minimal-store' ); ?></a>
					<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Sign Out', 'qr-minimal-store' ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Sign In', 'qr-minimal-store' ); ?></a>
					<a href="<?php echo esc_url( add_query_arg( 'register', '1', $account_url ) ); ?>"><?php esc_html_e( 'Sign Up', 'qr-minimal-store' ); ?></a>
				<?php endif; ?>
			</nav>
		</div>
	</div>
	<div class="header-main">
		<?php qr_minimal_store_the_brand( 'header' ); ?>
		<button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="<?php esc_attr_e( 'Toggle menu', 'qr-minimal-store' ); ?>">
			<span class="nav-toggle__bar"></span>
			<span class="nav-toggle__bar"></span>
			<span class="nav-toggle__bar"></span>
		</button>
		<nav id="primary-nav" class="header-main__navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'qr-minimal-store' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => 'qr_minimal_store_primary_menu_fallback',
					'menu_class'     => 'site-nav__menu',
				)
			);
			?>
		</nav>
		<div class="header-search"><?php qr_minimal_store_product_search_bar(); ?></div>
		<div class="header-actions">
			<?php $account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ); ?>
			<a class="header-action" href="<?php echo esc_url( $account_url ); ?>">
				<span aria-hidden="true">♡</span><span class="header-action__label"><?php echo is_user_logged_in() ? esc_html__( 'Account', 'qr-minimal-store' ) : esc_html__( 'Sign In', 'qr-minimal-store' ); ?></span>
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
			<?php qr_minimal_store_render_category_links( 8, array( 'security-camera', 'bags-accessories', 'ups' ) ); ?>
		</div>
	</nav>
</header>
<main class="site-main">
