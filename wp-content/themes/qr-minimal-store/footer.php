<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<footer class="site-footer">
	<div class="container footer-contact-bar">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'Need help with your order?', 'qr-minimal-store' ); ?></p>
			<p class="footer-call"><?php esc_html_e( 'Call us 24/7: +27 00 000 0000', 'qr-minimal-store' ); ?></p>
		</div>
		<div class="footer-contact-meta">
			<p><?php esc_html_e( '17 Princess Road, London, Greater London NW1 8JR, UK', 'qr-minimal-store' ); ?></p>
		</div>
	</div>
	<div class="container site-footer__grid site-footer__grid--four">
		<div>
			<h2><?php bloginfo( 'name' ); ?></h2>
			<p><?php bloginfo( 'description' ); ?></p>
		</div>
		<div>
			<h3><?php esc_html_e( 'Quick Links', 'qr-minimal-store' ); ?></h3>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'fallback_cb'    => 'wp_page_menu',
					'menu_class'     => 'footer-nav',
				)
			);
			?>
		</div>
		<div>
			<h3><?php esc_html_e( 'Shop Categories', 'qr-minimal-store' ); ?></h3>
			<p><a href="<?php echo esc_url( home_url( '/product-category/ups/' ) ); ?>"><?php esc_html_e( 'UPS', 'qr-minimal-store' ); ?></a></p>
			<p><a href="<?php echo esc_url( home_url( '/product-category/cctv/' ) ); ?>"><?php esc_html_e( 'CCTV', 'qr-minimal-store' ); ?></a></p>
			<p><a href="<?php echo esc_url( home_url( '/product-category/peripherals/' ) ); ?>"><?php esc_html_e( 'Peripherals', 'qr-minimal-store' ); ?></a></p>
		</div>
		<div>
			<h3><?php esc_html_e( 'Support', 'qr-minimal-store' ); ?></h3>
			<p><?php esc_html_e( 'Secure checkout and reliable delivery.', 'qr-minimal-store' ); ?></p>
			<p><a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>"><?php esc_html_e( 'My Account', 'qr-minimal-store' ); ?></a> / <a href="<?php echo esc_url( home_url( '/checkout/' ) ); ?>"><?php esc_html_e( 'Checkout', 'qr-minimal-store' ); ?></a></p>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
