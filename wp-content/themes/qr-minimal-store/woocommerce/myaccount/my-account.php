<?php
/**
 * My Account wrapper (design board E): menu on the left, content on the right. The order
 * detail page has no menu (it has a back link).
 *
 * @package qr-minimal-store
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

global $wp;
$qr_detail = isset( $wp->query_vars['view-order'] );
$qr_title  = '';
foreach ( wc_get_account_menu_items() as $qr_endpoint => $qr_label ) {
	if ( 'orders' !== $qr_endpoint && isset( $wp->query_vars[ $qr_endpoint ] ) ) {
		$qr_title = $qr_label;
	}
}
?>
<div class="qr-acct<?php echo $qr_detail ? ' qr-acct--detail' : ''; ?>">
	<?php
	if ( ! $qr_detail ) {
		do_action( 'woocommerce_account_navigation' );
	}
	?>
	<div class="qr-acct__content woocommerce-MyAccount-content">
		<?php if ( '' !== $qr_title ) : ?>
			<h1 class="qr-acct__title"><?php echo esc_html( $qr_title ); ?></h1>
		<?php endif; ?>
		<?php do_action( 'woocommerce_account_content' ); ?>
	</div>
</div>
