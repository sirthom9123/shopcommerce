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
		<?php
		if ( class_exists( 'QR_Account_Newsletter' ) ) {
			QR_Account_Newsletter::footer_form();
		} else {
			?>
		<form class="newsletter-bar__form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
			<label class="screen-reader-text" for="qr-newsletter-email"><?php esc_html_e( 'Email address', 'qr-minimal-store' ); ?></label>
			<input id="qr-newsletter-email" type="email" name="newsletter_email" placeholder="<?php esc_attr_e( 'Your email address', 'qr-minimal-store' ); ?>" required>
			<button type="submit"><?php esc_html_e( 'Subscribe', 'qr-minimal-store' ); ?></button>
		</form>
			<?php
		}
		?>
	</div>
</section>
<footer class="site-footer">
	<div class="container footer-contact-bar">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'Orders', 'qr-minimal-store' ); ?></p>
			<p class="footer-call"><a href="mailto:orders@shop.yehudasolutions.com">orders@shop.yehudasolutions.com</a></p>
		</div>
		<div>
			<p class="eyebrow"><?php esc_html_e( 'Returns', 'qr-minimal-store' ); ?></p>
			<p class="footer-call"><a href="mailto:returns@shop.yehudasolutions.com">returns@shop.yehudasolutions.com</a></p>
		</div>
		<div>
			<p class="eyebrow"><?php esc_html_e( 'General enquiries', 'qr-minimal-store' ); ?></p>
			<p class="footer-call"><a href="mailto:store@yehudasolutions.com">store@yehudasolutions.com</a></p>
		</div>
	</div>
	<div class="container site-footer__grid site-footer__grid--four">
		<div class="footer-brand">
			<?php qr_minimal_store_the_brand( 'footer' ); ?>
			<p><?php bloginfo( 'description' ); ?></p>
		</div>
		<div class="footer-column">
			<h3><?php esc_html_e( 'Quick Links', 'qr-minimal-store' ); ?></h3>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'fallback_cb'    => 'qr_minimal_store_footer_menu_fallback',
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
			<h3><?php esc_html_e( 'Legal', 'qr-minimal-store' ); ?></h3>
			<ul class="footer-nav">
				<?php foreach ( qr_minimal_store_legal_pages() as $legal_page ) : ?>
				<li><a href="<?php echo esc_url( $legal_page['url'] ); ?>"><?php echo esc_html( $legal_page['title'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<div class="container site-footer__legal">
		<p><?php echo esc_html( sprintf( /* translators: 1: year, 2: site name */ __( '© %1$s %2$s. South Africa. All rights reserved.', 'qr-minimal-store' ), gmdate( 'Y' ), get_bloginfo( 'name' ) ) ); ?></p>
		<?php qr_minimal_store_google_reviews_badge(); ?>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
