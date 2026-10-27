<?php
/**
 * Signed out (design board E): Sign in (or Create an account) beside Track an order. All
 * WooCommerce and qr-account form hooks (name fields, consent tick, bot checks) still run.
 *
 * @package qr-minimal-store
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$qr_can_register = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$qr_register     = $qr_can_register && ( class_exists( 'QR_Account' ) ? QR_Account::wants_register_panel() : isset( $_GET['register'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$qr_track        = qr_minimal_store_track_order_result();
$qr_account_url  = wc_get_page_permalink( 'myaccount' );

if ( $qr_track && $qr_track['order'] ) {
	qr_minimal_store_order_detail( $qr_track['order'], 'track' );
	return;
}

do_action( 'woocommerce_before_customer_login_form' );
?>
<div class="qr-auth">
	<div class="qr-auth__main">
		<?php if ( $qr_register ) : ?>
			<h1 class="qr-auth__title"><?php esc_html_e( 'Create an account', 'qr-minimal-store' ); ?></h1>
			<p class="qr-auth__lead"><?php esc_html_e( 'See your orders and saved addresses in one place. You never need an account to buy.', 'qr-minimal-store' ); ?></p>

			<form method="post" class="qr-form woocommerce-form woocommerce-form-register register" action="<?php echo esc_url( add_query_arg( 'register', '1', $qr_account_url ) ); ?>" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
					<p class="woocommerce-form-row form-row form-row-wide">
						<label for="reg_username"><?php esc_html_e( 'Username', 'qr-minimal-store' ); ?></label>
						<input type="text" class="woocommerce-Input input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore ?>" required aria-required="true" />
					</p>
				<?php endif; ?>

				<p class="woocommerce-form-row form-row form-row-wide">
					<label for="reg_email"><?php esc_html_e( 'Email address', 'qr-minimal-store' ); ?></label>
					<input type="email" class="woocommerce-Input input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore ?>" required aria-required="true" />
				</p>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
					<p class="woocommerce-form-row form-row form-row-wide">
						<label for="reg_password"><?php esc_html_e( 'Password', 'qr-minimal-store' ); ?></label>
						<input type="password" class="woocommerce-Input input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'We email you a link to set your password.', 'qr-minimal-store' ); ?></p>
				<?php endif; ?>

				<?php do_action( 'woocommerce_register_form' ); ?>

				<p class="form-row">
					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<button type="submit" class="qr-btn qr-btn--block woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Create account', 'qr-minimal-store' ); ?>"><?php esc_html_e( 'Create account', 'qr-minimal-store' ); ?></button>
				</p>

				<?php do_action( 'woocommerce_register_form_end' ); ?>
			</form>
			<p class="qr-auth__switch"><?php esc_html_e( 'Already have an account?', 'qr-minimal-store' ); ?> <a href="<?php echo esc_url( $qr_account_url ); ?>"><?php esc_html_e( 'Sign in', 'qr-minimal-store' ); ?></a></p>
		<?php else : ?>
			<h1 class="qr-auth__title"><?php esc_html_e( 'Sign in', 'qr-minimal-store' ); ?></h1>
			<p class="qr-auth__lead"><?php esc_html_e( 'See your orders and saved addresses. You never need an account to buy.', 'qr-minimal-store' ); ?></p>

			<form class="qr-form woocommerce-form woocommerce-form-login login" method="post" novalidate>
				<?php do_action( 'woocommerce_login_form_start' ); ?>

				<p class="woocommerce-form-row form-row form-row-wide">
					<label for="username"><?php esc_html_e( 'Email address', 'qr-minimal-store' ); ?></label>
					<input type="text" inputmode="email" class="woocommerce-Input input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore ?>" required aria-required="true" />
				</p>
				<p class="woocommerce-form-row form-row form-row-wide">
					<span class="qr-form__label-row">
						<label for="password"><?php esc_html_e( 'Password', 'qr-minimal-store' ); ?></label>
						<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'qr-minimal-store' ); ?></a>
					</span>
					<input class="woocommerce-Input input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
				</p>

				<?php do_action( 'woocommerce_login_form' ); ?>

				<p class="form-row">
					<label class="qr-form__check" for="rememberme">
						<input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Keep me signed in', 'qr-minimal-store' ); ?></span>
					</label>
				</p>
				<p class="form-row">
					<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
					<button type="submit" class="qr-btn qr-btn--block woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Sign in', 'qr-minimal-store' ); ?>"><?php esc_html_e( 'Sign in', 'qr-minimal-store' ); ?></button>
				</p>

				<?php do_action( 'woocommerce_login_form_end' ); ?>
			</form>
			<?php if ( $qr_can_register ) : ?>
				<p class="qr-auth__switch"><?php esc_html_e( 'New here?', 'qr-minimal-store' ); ?> <a href="<?php echo esc_url( add_query_arg( 'register', '1', $qr_account_url ) ); ?>"><?php esc_html_e( 'Create an account', 'qr-minimal-store' ); ?></a></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<section class="qr-auth__track" aria-labelledby="qr-track-title">
		<h2 id="qr-track-title"><?php esc_html_e( 'Track an order', 'qr-minimal-store' ); ?></h2>
		<p><?php esc_html_e( 'Checked out as a guest? Use the order number from your confirmation email.', 'qr-minimal-store' ); ?></p>
		<?php if ( $qr_track && '' !== $qr_track['error'] ) : ?>
			<p class="qr-auth__error" role="alert"><?php echo esc_html( $qr_track['error'] ); ?></p>
		<?php endif; ?>
		<form class="qr-form" method="post" action="<?php echo esc_url( $qr_account_url ); ?>#qr-track-title">
			<p class="form-row">
				<label for="qr_track_order"><?php esc_html_e( 'Order number', 'qr-minimal-store' ); ?></label>
				<input type="text" class="input-text" inputmode="numeric" name="qr_track_order" id="qr_track_order" autocomplete="off" value="<?php echo esc_attr( $qr_track ? $qr_track['number'] : '' ); ?>" required />
			</p>
			<p class="form-row">
				<label for="qr_track_email"><?php esc_html_e( 'Email used at checkout', 'qr-minimal-store' ); ?></label>
				<input type="email" class="input-text" name="qr_track_email" id="qr_track_email" autocomplete="email" value="<?php echo esc_attr( $qr_track ? $qr_track['email'] : '' ); ?>" required />
			</p>
			<?php wp_nonce_field( 'qr_track_order', 'qr_track_nonce' ); ?>
			<button type="submit" class="qr-btn qr-btn--outline qr-btn--block"><?php esc_html_e( 'Track order', 'qr-minimal-store' ); ?></button>
		</form>
		<p class="qr-auth__help">
			<?php esc_html_e( 'Questions:', 'qr-minimal-store' ); ?>
			<a href="<?php echo esc_url( 'mailto:' . QR_ORDERS_EMAIL ); ?>"><?php echo esc_html( QR_ORDERS_EMAIL ); ?></a>
		</p>
	</section>
</div>
<?php
do_action( 'woocommerce_after_customer_login_form' );
