<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<section class="newsletter-bar">
	<div class="container newsletter-bar__grid">
		<div class="newsletter-bar__content">
			<h3><?php esc_html_e( 'Sign up for our newsletter', 'qr-minimal-store' ); ?></h3>
			<p><?php esc_html_e( 'Get deals, new arrivals, and tech tips delivered to your inbox.', 'qr-minimal-store' ); ?></p>
		</div>
		<form class="newsletter-bar__form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
			<label class="screen-reader-text" for="qr-newsletter-email"><?php esc_html_e( 'Email address', 'qr-minimal-store' ); ?></label>
			<input id="qr-newsletter-email" type="email" name="newsletter_email" placeholder="<?php esc_attr_e( 'Your email address', 'qr-minimal-store' ); ?>" required>
			<button type="submit"><?php esc_html_e( 'Subscribe', 'qr-minimal-store' ); ?></button>
		</form>
	</div>
</section>
<footer class="site-footer">
	<div class="container footer-contact-bar">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'Need help with your order?', 'qr-minimal-store' ); ?></p>
			<p class="footer-call"><?php esc_html_e( 'Contact us: hello@yehudasolutions.com', 'qr-minimal-store' ); ?></p>
		</div>
		<div class="footer-contact-meta">
			<p><?php esc_html_e( 'South Africa', 'qr-minimal-store' ); ?></p>
		</div>
	</div>
	<div class="container site-footer__grid site-footer__grid--four">
		<div class="footer-brand">
			<h2><?php bloginfo( 'name' ); ?></h2>
			<p><?php bloginfo( 'description' ); ?></p>
		</div>
		<div class="footer-column">
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
		<div class="footer-column">
			<h3><?php esc_html_e( 'Shop Categories', 'qr-minimal-store' ); ?></h3>
			<?php qr_minimal_store_render_category_links( 3 ); ?>
		</div>
		<div class="footer-column">
			<h3><?php esc_html_e( 'Support', 'qr-minimal-store' ); ?></h3>
			<p><?php esc_html_e( 'Secure checkout and reliable delivery.', 'qr-minimal-store' ); ?></p>
			<p><a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>"><?php esc_html_e( 'My Account', 'qr-minimal-store' ); ?></a> / <a href="<?php echo esc_url( home_url( '/checkout/' ) ); ?>"><?php esc_html_e( 'Checkout', 'qr-minimal-store' ); ?></a></p>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
