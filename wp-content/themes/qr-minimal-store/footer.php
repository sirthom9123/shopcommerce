<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<?php if ( qr_minimal_store_is_checkout_flow() ) : ?>
<footer class="site-footer site-footer--checkout">
	<div class="container site-footer__legal">
		<ul class="footer-nav footer-nav--inline">
			<?php foreach ( qr_minimal_store_legal_pages() as $legal_page ) : ?>
			<li><a href="<?php echo esc_url( $legal_page['url'] ); ?>"><?php echo esc_html( $legal_page['title'] ); ?></a></li>
			<?php endforeach; ?>
			<li><button type="button" class="qr-consent-open" id="qr-consent-settings"><?php esc_html_e( 'Cookie & privacy settings', 'qr-minimal-store' ); ?></button></li>
		</ul>
		<?php
		if ( function_exists( 'qr_payment_badges_strip' ) ) {
			qr_payment_badges_strip();
		}
		?>
	</div>
</footer>
<?php else : ?>
<?php if ( class_exists( 'QR_Account_Newsletter' ) ) : ?>
<section class="qr-signup">
	<div class="container qr-signup__grid">
		<div>
			<h2 class="qr-signup__title"><?php esc_html_e( 'New arrivals and deals, first.', 'qr-minimal-store' ); ?></h2>
			<p><?php esc_html_e( 'One email when something new lands or a deal starts. Unsubscribe any time.', 'qr-minimal-store' ); ?></p>
		</div>
		<?php qr_minimal_store_newsletter_form(); ?>
	</div>
</section>
<?php endif; ?>
<?php
if ( is_front_page() ) {
	qr_minimal_store_brands_strip();
}
?>
<footer class="site-footer site-footer--main">
	<div class="container qr-footer__grid">
		<div class="qr-footer__brand">
			<?php qr_minimal_store_the_brand( 'footer' ); ?>
			<p><?php esc_html_e( 'Smart tech for South African homes, from authorised distributors.', 'qr-minimal-store' ); ?></p>
		</div>
		<div>
			<h2 class="qr-footer__title"><?php esc_html_e( 'Departments', 'qr-minimal-store' ); ?></h2>
			<ul class="qr-footer__links">
				<?php qr_minimal_store_department_links( 9 ); ?>
			</ul>
		</div>
		<div>
			<h2 class="qr-footer__title"><?php esc_html_e( 'Help', 'qr-minimal-store' ); ?></h2>
			<ul class="qr-footer__links">
				<?php foreach ( qr_minimal_store_help_links() as $help_link ) : ?>
					<li><a href="<?php echo esc_url( $help_link['url'] ); ?>"><?php echo esc_html( $help_link['label'] ); ?></a></li>
				<?php endforeach; ?>
				<li><a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ) ); ?>"><?php esc_html_e( 'My account and orders', 'qr-minimal-store' ); ?></a></li>
			</ul>
		</div>
		<div>
			<h2 class="qr-footer__title"><?php esc_html_e( 'Contact', 'qr-minimal-store' ); ?></h2>
			<ul class="qr-footer__links">
				<li><?php esc_html_e( 'Orders:', 'qr-minimal-store' ); ?> <a href="mailto:orders@shop.yehudasolutions.com">orders@shop.yehudasolutions.com</a></li>
				<li><?php esc_html_e( 'Returns:', 'qr-minimal-store' ); ?> <a href="mailto:returns@shop.yehudasolutions.com">returns@shop.yehudasolutions.com</a></li>
				<li><?php esc_html_e( 'Other questions:', 'qr-minimal-store' ); ?> <a href="mailto:store@yehudasolutions.com">store@yehudasolutions.com</a></li>
				<?php foreach ( qr_minimal_store_social_profiles() as $network => $profile ) : ?>
					<li>
						<?php
						/* translators: %s: TikTok or Instagram */
						printf( esc_html__( '%s:', 'qr-minimal-store' ), esc_html( $network ) );
						?>
						<a href="<?php echo esc_url( $profile['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $profile['handle'] ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'qr-minimal-store' ); ?></span></a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			if ( function_exists( 'qr_payment_badges_strip' ) ) {
				qr_payment_badges_strip();
			}
			?>
		</div>
	</div>
	<div class="container qr-footer__bottom">
		<p><?php echo esc_html( sprintf( /* translators: %s: year */ __( '© %s Yehuda Solutions (Pty) Ltd', 'qr-minimal-store' ), gmdate( 'Y' ) ) ); ?></p>
		<?php
		if ( qr_minimal_store_show_rating_badge() ) {
			qr_minimal_store_google_reviews_badge();
		}
		?>
		<button type="button" class="qr-consent-open" id="qr-consent-settings"><?php esc_html_e( 'Cookie and privacy settings', 'qr-minimal-store' ); ?></button>
	</div>
</footer>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
