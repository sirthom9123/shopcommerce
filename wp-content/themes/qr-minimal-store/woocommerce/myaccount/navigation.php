<?php
/**
 * Account menu (design board E): "Hi {name}", Orders, Addresses, Account details, Email
 * preferences, Sign out. A chip row on phones.
 *
 * @package qr-minimal-store
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$qr_user = wp_get_current_user();
$qr_name = $qr_user->exists() ? trim( (string) $qr_user->first_name ) : '';

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="qr-acct-nav woocommerce-MyAccount-navigation" aria-label="<?php esc_attr_e( 'Account pages', 'qr-minimal-store' ); ?>">
	<p class="qr-acct-nav__hi">
		<?php
		/* translators: %s: customer's first name */
		echo esc_html( '' !== $qr_name ? sprintf( __( 'Hi %s', 'qr-minimal-store' ), $qr_name ) : __( 'Your account', 'qr-minimal-store' ) );
		?>
	</p>
	<ul>
		<?php foreach ( wc_get_account_menu_items() as $qr_endpoint => $qr_label ) : ?>
			<?php
			$qr_current = qr_minimal_store_account_is_current( $qr_endpoint );
			$qr_short   = qr_minimal_store_account_short_label( $qr_endpoint, $qr_label );
			?>
			<li class="<?php echo esc_attr( wc_get_account_menu_item_classes( $qr_endpoint ) ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $qr_endpoint ) ); ?>"<?php echo $qr_current ? ' aria-current="page"' : ''; ?>>
					<?php if ( $qr_short !== $qr_label ) : ?>
						<span class="qr-acct-nav__long"><?php echo esc_html( $qr_label ); ?></span><span class="qr-acct-nav__short" aria-hidden="true"><?php echo esc_html( $qr_short ); ?></span>
					<?php else : ?>
						<?php echo esc_html( $qr_label ); ?>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
