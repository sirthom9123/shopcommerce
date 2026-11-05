<?php
/**
 * Gaming department page, /gaming/ (design Gaming.html). Prices, totals and savings are live;
 * sections without products are not shown.
 *
 * @package qr-minimal-store
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$bundles   = qr_minimal_store_gaming_bundles();
$buyable   = qr_minimal_store_gaming_buyable_bundles();
$racing    = array_values( array_filter( $bundles, static fn( $bundle ) => 'racing' === $bundle['group'] ) );
$desk      = array_values( array_filter( $bundles, static fn( $bundle ) => 'racing' !== $bundle['group'] ) );
$singles   = qr_minimal_store_gaming_singles();
$rule      = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
$all_free  = $bundles && ! array_filter( $bundles, static fn( $bundle ) => ! $bundle['free'] );
$black_fri = qr_minimal_store_gaming_is_black_friday();
$term      = get_term_by( 'slug', 'gaming', 'product_cat' );
$term_link = $term instanceof WP_Term ? get_term_link( $term ) : '';
$all_url   = $term_link && ! is_wp_error( $term_link ) ? add_query_arg( 'sort', 'recommended', $term_link ) : '';
$heroes    = qr_minimal_store_gaming_hero_products();

// The hero chip: the Go Pro bundle while it can be bought, else the first bundle with a saving.
$chip = null;
foreach ( $buyable as $bundle ) {
	if ( $bundle['save'] > 0 && ( ! $chip || $bundle['hot'] ) ) {
		$chip = $bundle;
	}
}

// "Bundles from … · save up to …" from what can be bought now.
$from_parts = array();
if ( $buyable ) {
	/* translators: %s: lowest bundle price */
	$from_parts[] = sprintf( __( 'Bundles from %s', 'qr-minimal-store' ), html_entity_decode( wp_strip_all_tags( wc_price( min( array_column( $buyable, 'price' ) ) ) ), ENT_QUOTES, 'UTF-8' ) );
	$best_save    = max( array_column( $buyable, 'save' ) );
	if ( $best_save > 0 ) {
		/* translators: %s: highest saving */
		$from_parts[] = sprintf( __( 'save up to %s', 'qr-minimal-store' ), qr_minimal_store_money_whole( $best_save ) );
	}
	$from_parts[] = __( 'pay monthly, subject to approval', 'qr-minimal-store' );
}

$faqs = array(
	array(
		__( 'Are these genuine products with a warranty?', 'qr-minimal-store' ),
		__( 'Yes. Every item comes from an authorised South African distributor, with local manufacturer warranty. No grey imports.', 'qr-minimal-store' ),
	),
	array(
		__( 'Can I buy just one item?', 'qr-minimal-store' ),
		__( 'Yes. Every product in a bundle is also sold on its own further down this page. The bundle simply costs less than buying the same items separately.', 'qr-minimal-store' ),
	),
);
if ( $rule ) {
	$faqs[] = array(
		__( 'How much is delivery?', 'qr-minimal-store' ),
		sprintf(
			$all_free
				/* translators: 1: free delivery threshold, 2: delivery fee */
				? __( 'Every bundle ships free. Single items under %1$s cost %2$s to deliver nationwide.', 'qr-minimal-store' )
				/* translators: 1: free delivery threshold, 2: delivery fee */
				: __( 'Orders from %1$s ship free. Under that, delivery costs %2$s nationwide.', 'qr-minimal-store' ),
			qr_minimal_store_money_whole( $rule['threshold'] ),
			qr_minimal_store_money_whole( $rule['fee'] )
		),
	);
}
$faqs[] = array(
	__( 'Can I pay it off monthly?', 'qr-minimal-store' ),
	__( 'Yes. Choose Mobicred or an RCS store card at Payfast checkout. Credit is provided by Mobicred or the card issuer and is subject to approval.', 'qr-minimal-store' ),
);
$faqs[] = array(
	__( 'Will it work with my PC or console?', 'qr-minimal-store' ),
	__( 'Check the platform list on each product page before you order. Wheels and joysticks differ between PC, PlayStation and Xbox.', 'qr-minimal-store' ),
);
$faqs[] = array(
	__( 'Can I return it?', 'qr-minimal-store' ),
	__( 'Unused items can be returned within 30 days.', 'qr-minimal-store' ),
);
?>
<div class="qr-gaming">
	<section class="qr-gdark qr-ghero">
		<div class="container qr-ghero__grid">
			<div class="qr-ghero__copy">
				<p class="qr-geyebrow"><?php echo esc_html( $black_fri ? __( 'Black Friday · Gaming bundles', 'qr-minimal-store' ) : __( 'Gaming bundles', 'qr-minimal-store' ) ); ?></p>
				<h1><?php esc_html_e( 'Buy the setup.', 'qr-minimal-store' ); ?> <em><?php esc_html_e( 'Not the parts.', 'qr-minimal-store' ); ?></em></h1>
				<p class="qr-ghero__lead">
					<?php
					echo esc_html(
						$all_free
							? __( 'Racing wheels, stands, keyboards and headsets packed into ready-to-play bundles. Every bundle costs less than buying the same items one by one, and ships free.', 'qr-minimal-store' )
							: __( 'Racing wheels, stands, keyboards and headsets packed into ready-to-play bundles. Every bundle costs less than buying the same items one by one.', 'qr-minimal-store' )
					);
					?>
				</p>
				<?php if ( $racing || $desk ) : ?>
					<div class="qr-actions">
						<?php if ( $racing ) : ?>
							<a class="qr-btn" href="#racing"><?php esc_html_e( 'Shop racing bundles', 'qr-minimal-store' ); ?></a>
						<?php endif; ?>
						<?php if ( $desk ) : ?>
							<a class="qr-btn qr-btn--ghost-light" href="#desk"><?php esc_html_e( 'Desk & stream setups', 'qr-minimal-store' ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( $from_parts ) : ?>
					<p class="qr-ghero__from"><?php echo esc_html( implode( ' · ', $from_parts ) ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $heroes ) : ?>
				<div class="qr-gstage">
					<?php foreach ( $heroes as $index => $product ) : ?>
						<a class="qr-gtile qr-gstage__tile qr-gstage__tile--<?php echo esc_attr( (string) ( $index + 1 ) ); ?>" href="<?php echo esc_url( $product->get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
							<?php echo qr_minimal_store_gaming_image( $product, qr_minimal_store_gaming_short_name( $product ), 0 === $index ? 'woocommerce_single' : 'woocommerce_thumbnail', 'eager' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?>
						</a>
					<?php endforeach; ?>
					<?php if ( $chip ) : ?>
						<a class="qr-gstage__chip" href="#<?php echo esc_attr( $chip['anchor'] ); ?>">
							<?php
							/* translators: %s: bundle name */
							echo esc_html( sprintf( __( '%s bundle', 'qr-minimal-store' ), $chip['title'] ) );
							?>
							<b><?php echo wp_kses_post( wc_price( $chip['price'] ) ); ?></b>
							<s class="qr-was"><?php echo wp_kses_post( wc_price( $chip['total'] ) ); ?></s>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="qr-gproof" aria-label="<?php esc_attr_e( 'Why buy your setup here', 'qr-minimal-store' ); ?>">
		<ul class="container">
			<li><b><?php esc_html_e( 'Genuine, SA warranty', 'qr-minimal-store' ); ?></b><?php esc_html_e( 'From an authorised SA distributor', 'qr-minimal-store' ); ?></li>
			<?php if ( $all_free ) : ?>
				<li><b><?php esc_html_e( 'Free delivery', 'qr-minimal-store' ); ?></b><?php esc_html_e( 'On every bundle, nationwide', 'qr-minimal-store' ); ?></li>
			<?php endif; ?>
			<li><b><?php esc_html_e( '30-day returns', 'qr-minimal-store' ); ?></b><?php esc_html_e( 'On unused items', 'qr-minimal-store' ); ?></li>
			<li><b><?php esc_html_e( 'Pay monthly', 'qr-minimal-store' ); ?></b><?php esc_html_e( 'Mobicred or RCS, subject to approval', 'qr-minimal-store' ); ?></li>
		</ul>
	</section>

	<?php if ( ! $bundles && ! $singles ) : ?>
		<section class="container qr-section">
			<div class="qr-shop__empty">
				<p class="qr-shop__empty-title"><?php esc_html_e( 'The gaming range is on its way.', 'qr-minimal-store' ); ?></p>
				<p><a href="<?php echo esc_url( 'mailto:orders@shop.yehudasolutions.com?subject=' . rawurlencode( __( 'Gaming', 'qr-minimal-store' ) ) ); ?>"><?php esc_html_e( 'Email us what you are looking for', 'qr-minimal-store' ); ?></a></p>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $racing ) : ?>
		<section id="racing" class="container qr-gsection">
			<div class="qr-gsection__head">
				<p class="qr-geyebrow"><?php esc_html_e( 'Sim racing', 'qr-minimal-store' ); ?></p>
				<h2><?php esc_html_e( 'Racing bundles', 'qr-minimal-store' ); ?></h2>
				<p><?php esc_html_e( 'Wheel, pedals and a stand that folds away after the race. Pick your level.', 'qr-minimal-store' ); ?></p>
			</div>
			<ul class="qr-gjump">
				<li><a href="#racing"><?php esc_html_e( 'Racing', 'qr-minimal-store' ); ?></a></li>
				<?php if ( $desk ) : ?>
					<li><a href="#desk"><?php esc_html_e( 'Desk & stream', 'qr-minimal-store' ); ?></a></li>
				<?php endif; ?>
				<?php if ( $singles ) : ?>
					<li><a href="#single"><?php esc_html_e( 'Single items', 'qr-minimal-store' ); ?></a></li>
				<?php endif; ?>
				<li><a href="#guide"><?php esc_html_e( 'Buying guide', 'qr-minimal-store' ); ?></a></li>
			</ul>
			<div class="qr-bundles">
				<?php
				foreach ( $racing as $bundle ) {
					qr_minimal_store_gaming_bundle_card( $bundle );
				}
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $desk ) : ?>
		<section id="desk" class="container qr-gsection qr-gsection--flush">
			<div class="qr-gsection__head">
				<p class="qr-geyebrow"><?php esc_html_e( 'PC gaming & streaming', 'qr-minimal-store' ); ?></p>
				<h2><?php esc_html_e( 'Desk & stream bundles', 'qr-minimal-store' ); ?></h2>
				<p><?php esc_html_e( 'Everything on your desk in one order, instead of three.', 'qr-minimal-store' ); ?></p>
			</div>
			<div class="qr-bundles qr-bundles--two">
				<?php
				foreach ( $desk as $bundle ) {
					qr_minimal_store_gaming_bundle_card( $bundle );
				}
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $singles ) : ?>
		<section id="single" class="qr-gsingles">
			<div class="container qr-gsection">
				<div class="qr-gsection__head qr-gsection__head--row">
					<div>
						<p class="qr-geyebrow"><?php esc_html_e( 'Need just one thing?', 'qr-minimal-store' ); ?></p>
						<h2><?php esc_html_e( 'Shop single items', 'qr-minimal-store' ); ?></h2>
						<p><?php esc_html_e( 'Every bundle item is sold on its own too. Check the bundle before you buy.', 'qr-minimal-store' ); ?></p>
					</div>
					<?php if ( $all_url ) : ?>
						<a class="qr-btn qr-btn--outline" href="<?php echo esc_url( $all_url ); ?>"><?php esc_html_e( 'View all gaming', 'qr-minimal-store' ); ?></a>
					<?php endif; ?>
				</div>
				<ul class="qr-gsingles__grid">
					<?php
					foreach ( $singles as $product ) {
						qr_minimal_store_gaming_single_card( $product );
					}
					?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<section id="guide" class="container qr-gsection">
		<div class="qr-gsection__head">
			<p class="qr-geyebrow"><?php esc_html_e( 'New to sim racing?', 'qr-minimal-store' ); ?></p>
			<h2><?php esc_html_e( 'Build it in this order', 'qr-minimal-store' ); ?></h2>
		</div>
		<ol class="qr-gguide">
			<li><h3><?php esc_html_e( 'Wheel and pedals', 'qr-minimal-store' ); ?></h3><p><?php esc_html_e( 'The core. Every wheel here ships with its own pedal set, so you can race out of the box.', 'qr-minimal-store' ); ?></p></li>
			<li><h3><?php esc_html_e( 'A stand', 'qr-minimal-store' ); ?></h3><p><?php esc_html_e( "Stops the wheel sliding under hard braking. Folds away when you're done, so no desk needed.", 'qr-minimal-store' ); ?></p></li>
			<li><h3><?php esc_html_e( 'Shifter and handbrake', 'qr-minimal-store' ); ?></h3><p><?php esc_html_e( 'Add manual gears and a rally handbrake when you want the full feel.', 'qr-minimal-store' ); ?></p></li>
			<li><h3><?php esc_html_e( 'Check your platform', 'qr-minimal-store' ); ?></h3><p><?php esc_html_e( 'PC, PlayStation or Xbox? Confirm support on the product page before you order.', 'qr-minimal-store' ); ?></p></li>
		</ol>
	</section>

	<section class="container qr-gsection qr-gsection--flush">
		<div class="qr-payway">
			<div class="qr-payway__copy">
				<p class="qr-eyebrow"><?php esc_html_e( 'Pay your way', 'qr-minimal-store' ); ?></p>
				<h2 class="qr-h2"><?php esc_html_e( 'Take the full setup home now.', 'qr-minimal-store' ); ?></h2>
				<p><?php esc_html_e( 'Choose Mobicred or an RCS store card at Payfast checkout, on any order size. Same price as paying cash. Credit is provided by Mobicred or the card issuer and is subject to approval.', 'qr-minimal-store' ); ?></p>
			</div>
			<?php $logos = qr_minimal_store_credit_logos(); ?>
			<?php if ( $logos ) : ?>
				<div class="qr-payway__logos">
					<?php foreach ( $logos as $alt => $src ) : ?>
						<span><img src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" height="36"></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="container qr-gsection qr-gsection--flush">
		<div class="qr-gsection__head">
			<h2><?php esc_html_e( 'Questions', 'qr-minimal-store' ); ?></h2>
		</div>
		<div class="qr-gfaq">
			<?php foreach ( $faqs as $faq ) : ?>
				<details>
					<summary><?php echo esc_html( $faq[0] ); ?></summary>
					<p><?php echo esc_html( $faq[1] ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</section>

	<?php if ( $bundles ) : ?>
		<section class="qr-gdark qr-gfinal">
			<div class="container">
				<p class="qr-geyebrow"><?php echo esc_html( $black_fri ? __( 'Black Friday · Gaming', 'qr-minimal-store' ) : __( 'Gaming', 'qr-minimal-store' ) ); ?></p>
				<h2><?php esc_html_e( 'One order. Ready to play.', 'qr-minimal-store' ); ?></h2>
				<p>
					<?php
					echo esc_html(
						$all_free
							? __( 'Bundles ship free, cost less than the parts, and come with SA warranty.', 'qr-minimal-store' )
							: __( 'Bundles cost less than the parts and come with SA warranty.', 'qr-minimal-store' )
					);
					?>
				</p>
				<a class="qr-btn" href="<?php echo esc_url( $racing ? '#racing' : '#desk' ); ?>"><?php esc_html_e( 'Shop gaming bundles', 'qr-minimal-store' ); ?></a>
			</div>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer( 'shop' );
