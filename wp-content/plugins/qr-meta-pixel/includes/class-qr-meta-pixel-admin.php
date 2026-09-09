<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QR_Meta_Pixel_Admin {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_notices', array( $this, 'notice_missing_id' ) );
	}

	public function menu() {
		add_options_page(
			__( 'Meta Pixel', 'qr-meta-pixel' ),
			__( 'Meta Pixel', 'qr-meta-pixel' ),
			'manage_woocommerce',
			'qr-meta-pixel',
			array( $this, 'render' )
		);
	}

	public function register() {
		register_setting(
			'qr_meta_pixel',
			QR_META_PIXEL_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_pixel_id' ),
				'default'           => '',
			)
		);
		register_setting(
			'qr_meta_pixel',
			'qr_meta_pixel_skip_staff',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_yes_no' ),
				'default'           => 'yes',
			)
		);
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public function sanitize_pixel_id( $value ) {
		return preg_replace( '/[^0-9]/', '', (string) $value );
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public function sanitize_yes_no( $value ) {
		return 'yes' === $value ? 'yes' : 'no';
	}

	public function notice_missing_id() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( QR_Meta_Pixel::is_configured() ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && 'settings_page_qr-meta-pixel' === $screen->id ) {
			return;
		}

		$url = admin_url( 'options-general.php?page=qr-meta-pixel' );
		echo '<div class="notice notice-warning"><p>';
		echo wp_kses(
			sprintf(
				/* translators: %s: settings URL */
				__( 'Meta Pixel is installed but has no Pixel ID yet. Add it under <a href="%s">Settings → Meta Pixel</a>.', 'qr-meta-pixel' ),
				esc_url( $url )
			),
			array( 'a' => array( 'href' => array() ) )
		);
		echo '</p></div>';
	}

	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$locked = defined( 'QR_META_PIXEL_ID' ) && QR_META_PIXEL_ID;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Meta Pixel', 'qr-meta-pixel' ); ?></h1>
			<p><?php esc_html_e( 'Paste the Pixel ID from Meta Events Manager. The official base code loads in the site header (PageView). WooCommerce also fires ViewContent, AddToCart, InitiateCheckout, Purchase, and Search.', 'qr-meta-pixel' ); ?></p>
			<?php if ( $locked ) : ?>
				<p class="notice notice-info" style="padding:12px;">
					<?php esc_html_e( 'Pixel ID is set in wp-config.php (QR_META_PIXEL_ID) and cannot be changed here.', 'qr-meta-pixel' ); ?>
					<br>
					<code><?php echo esc_html( QR_Meta_Pixel::pixel_id() ); ?></code>
				</p>
			<?php endif; ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'qr_meta_pixel' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="qr_meta_pixel_id"><?php esc_html_e( 'Pixel ID', 'qr-meta-pixel' ); ?></label></th>
						<td>
							<input class="regular-text" type="text" id="qr_meta_pixel_id" name="<?php echo esc_attr( QR_META_PIXEL_OPTION ); ?>" value="<?php echo esc_attr( (string) get_option( QR_META_PIXEL_OPTION, '' ) ); ?>" inputmode="numeric" pattern="[0-9]*" <?php disabled( $locked ); ?>>
							<p class="description"><?php esc_html_e( 'Events Manager → Data sources → your pixel → Pixel ID (digits only).', 'qr-meta-pixel' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Staff traffic', 'qr-meta-pixel' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="qr_meta_pixel_skip_staff" value="no">
								<input type="checkbox" name="qr_meta_pixel_skip_staff" value="yes" <?php checked( get_option( 'qr_meta_pixel_skip_staff', 'yes' ), 'yes' ); ?>>
								<?php esc_html_e( 'Do not fire the pixel for logged-in shop managers (avoids polluting ad events while you test).', 'qr-meta-pixel' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
