<?php
/**
 * Home page (design board A). Sections in the design's order; each hides without store data.
 *
 * @package qr-minimal-store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$departments = qr_minimal_store_available_departments();
$printing    = isset( $departments['3d-printing'] ) ? $departments['3d-printing'] : null;
$hero        = qr_minimal_store_hero_products();
$rule        = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
?>
<div class="qr-home">

<section class="qr-hero">
	<div class="container qr-hero__grid">
		<div class="qr-hero__copy">
			<p class="qr-eyebrow"><?php esc_html_e( 'Genuine · Local warranty · Delivered nationwide', 'qr-minimal-store' ); ?></p>
			<h1><?php esc_html_e( 'Smart tech for your home, without the guesswork.', 'qr-minimal-store' ); ?></h1>
			<p class="qr-hero__lead"><?php esc_html_e( 'Xiaomi, Dell, Anycubic and more from authorised South African distributors. Pay in full, or in monthly instalments with Mobicred or an RCS store card (subject to approval).', 'qr-minimal-store' ); ?></p>
			<div class="qr-actions">
				<a class="qr-btn" href="#popular"><?php esc_html_e( 'Shop popular picks', 'qr-minimal-store' ); ?></a>
				<?php if ( $printing ) : ?>
					<a class="qr-btn qr-btn--outline" href="<?php echo esc_url( $printing['url'] ); ?>"><?php esc_html_e( 'New: 3D printing', 'qr-minimal-store' ); ?></a>
				<?php else : ?>
					<a class="qr-btn qr-btn--outline" href="#departments"><?php esc_html_e( 'Shop by department', 'qr-minimal-store' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( $hero ) : ?>
			<ul class="qr-hero__tiles">
				<?php
				foreach ( $hero as $product ) :
					$department = qr_minimal_store_product_department_info( $product );
					$is_new     = $department && $department['new'];
					$tag        = trim( (string) $product->get_meta( '_qr_tagline' ) );
					if ( '' === $tag ) {
						$tag = $department ? $department['title'] : __( 'In stock', 'qr-minimal-store' );
					}
					if ( $is_new ) {
						/* translators: %s: tagline */
						$tag = sprintf( __( 'New · %s', 'qr-minimal-store' ), $tag );
					}
					?>
					<li>
						<a class="qr-hero__tile" href="<?php echo esc_url( $product->get_permalink() ); ?>">
							<span class="qr-hero__img"><?php echo $product->get_image( 'woocommerce_thumbnail', array( 'alt' => $product->get_name(), 'loading' => 'eager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?></span>
							<span class="qr-hero__tag<?php echo $is_new ? ' is-new' : ''; ?>"><?php echo esc_html( $tag ); ?></span>
							<span class="qr-hero__name"><?php echo esc_html( $product->get_name() ); ?></span>
							<span class="qr-hero__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>

<section class="qr-proof" aria-label="<?php esc_attr_e( 'Why shop with us', 'qr-minimal-store' ); ?>">
	<ul class="container qr-proof__grid">
		<?php
		$proof = array(
			array( __( 'Authorised dealer', 'qr-minimal-store' ), __( 'Genuine stock, local warranty', 'qr-minimal-store' ) ),
		);
		if ( $rule ) {
			$proof[] = array(
				/* translators: %s: delivery fee */
				sprintf( __( '%s delivery', 'qr-minimal-store' ), qr_minimal_store_money_whole( $rule['fee'] ) ),
				/* translators: %s: free delivery threshold */
				sprintf( __( 'Free from %s · 2–3 working days', 'qr-minimal-store' ), qr_minimal_store_money_whole( $rule['threshold'] ) ),
			);
		}
		$proof[] = array( __( '30-day returns', 'qr-minimal-store' ), __( 'On unused items, in original packaging', 'qr-minimal-store' ) );
		$proof[] = array( __( 'Secure checkout', 'qr-minimal-store' ), __( 'Payfast: card, EFT, Mobicred and store cards', 'qr-minimal-store' ) );
		foreach ( $proof as $item ) :
			?>
			<li>
				<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/></svg>
				<span><strong><?php echo esc_html( $item[0] ); ?></strong><span><?php echo esc_html( $item[1] ); ?></span></span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>

<?php
// Tiles: the design's six, with other departments filling any gap.
$tile_order = array( 'smart-home', 'kitchen', 'wearables', 'pet-tech', 'grooming-wellness', '3d-printing', 'computer-office', 'power-mobile', 'tv-audio' );
$tiles      = array();
foreach ( array_unique( array_merge( $tile_order, array_keys( $departments ) ) ) as $key ) {
	if ( isset( $departments[ $key ] ) && count( $tiles ) < 6 ) {
		$tiles[] = $departments[ $key ];
	}
}
if ( $tiles ) :
	?>
<section id="departments" class="qr-section container">
	<h2 class="qr-h2"><?php esc_html_e( 'Shop by department', 'qr-minimal-store' ); ?></h2>
	<ul class="qr-departments">
		<?php
		foreach ( $tiles as $department ) :
			$product = wc_get_product( $department['product_id'] );
			?>
			<li>
				<a class="qr-department" href="<?php echo esc_url( $department['url'] ); ?>">
					<span class="qr-department__img"><?php echo $product ? $product->get_image( 'woocommerce_gallery_thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?></span>
					<span class="qr-department__text">
						<span class="qr-department__title"><?php echo esc_html( $department['title'] ); ?><?php echo $department['new'] ? ' <span class="qr-new-pill">' . esc_html__( 'New', 'qr-minimal-store' ) . '</span>' : ''; ?></span>
						<span class="qr-department__sub"><?php echo esc_html( $department['sub'] ); ?></span>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
<?php endif; ?>

<?php
$popular = qr_minimal_store_popular_products( 4, array_map( fn( $p ) => $p->get_id(), $hero ) );
if ( $popular ) :
	?>
<section id="popular" class="qr-section container">
	<div class="qr-section__head">
		<div>
			<h2 class="qr-h2"><?php esc_html_e( 'Popular right now', 'qr-minimal-store' ); ?></h2>
			<p class="qr-section__sub"><?php esc_html_e( 'Ranked by what customers buy on our store. In stock only.', 'qr-minimal-store' ); ?></p>
		</div>
		<a class="qr-section__link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop all products', 'qr-minimal-store' ); ?></a>
	</div>
	<ul class="qr-cards">
		<?php
		foreach ( $popular as $product ) {
			qr_minimal_store_home_product_card( $product );
		}
		?>
	</ul>
</section>
<?php endif; ?>

<section class="qr-section container">
	<div class="qr-payway">
		<div class="qr-payway__copy">
			<p class="qr-eyebrow"><?php esc_html_e( 'Pay your way', 'qr-minimal-store' ); ?></p>
			<h2 class="qr-h2"><?php esc_html_e( 'Take it home now. Pay it off monthly.', 'qr-minimal-store' ); ?></h2>
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

<?php
$feature = qr_minimal_store_home_feature_product();
if ( $feature ) :
	$video_id   = qr_minimal_store_youtube_id( $feature->get_meta( '_product_video_youtube' ) );
	$video_html = qr_minimal_store_youtube_html(
		$video_id,
		/* translators: %s: product name */
		sprintf( __( '%s: official video', 'qr-minimal-store' ), $feature->get_name() ),
		$feature->get_image_id()
	);
	?>
<section id="print" class="qr-section container qr-feature">
	<div class="qr-feature__media">
		<?php
		if ( $video_html ) {
			echo $video_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in qr_minimal_store_youtube_html().
		} else {
			echo '<a class="qr-feature__img" href="' . esc_url( $feature->get_permalink() ) . '">' . $feature->get_image( 'large', array( 'alt' => $feature->get_name() ) ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML.
		}
		?>
	</div>
	<div class="qr-feature__copy">
		<p class="qr-eyebrow"><?php echo esc_html( $printing ? __( 'New · 3D printing', 'qr-minimal-store' ) : __( 'New', 'qr-minimal-store' ) ); ?></p>
		<h2 class="qr-h2 qr-h2--lg"><?php echo esc_html( get_theme_mod( 'qr_home_feature_title', __( 'Print in 16 colours, at home.', 'qr-minimal-store' ) ) ); ?></h2>
		<p class="qr-feature__text"><?php echo esc_html( get_theme_mod( 'qr_home_feature_text', __( 'The Kobra S1 Ace 2 Pro Combo prints up to 16 colours with the included ACE 2 Pro, up to 600 mm/s, fully enclosed with AI print monitoring. Plus resin printers, filament and spares.', 'qr-minimal-store' ) ) ); ?></p>
		<p class="qr-feature__price"><span class="qr-feature__amount"><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $feature ) ) ); ?></span> <?php esc_html_e( 'or pay monthly, subject to approval', 'qr-minimal-store' ); ?></p>
		<div class="qr-actions">
			<a class="qr-btn" href="<?php echo esc_url( $feature->get_permalink() ); ?>">
				<?php
				/* translators: %s: product name */
				echo esc_html( sprintf( __( 'View the %s', 'qr-minimal-store' ), $feature->get_name() ) );
				?>
			</a>
			<?php if ( $printing ) : ?>
				<a class="qr-btn qr-btn--outline" href="<?php echo esc_url( $printing['url'] ); ?>"><?php esc_html_e( 'All 3D printing', 'qr-minimal-store' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
$reels   = qr_minimal_store_home_reels();
$socials = qr_minimal_store_social_profiles();
if ( $reels || $socials ) :
	?>
<section class="qr-band">
	<div class="qr-section container">
		<div class="qr-section__head">
			<div>
				<h2 class="qr-h2"><?php esc_html_e( 'See them in action', 'qr-minimal-store' ); ?></h2>
				<p class="qr-section__sub">
					<?php
					echo $reels
						? esc_html__( 'Short videos from our TikTok and Instagram. Tap a product to shop it.', 'qr-minimal-store' )
						: esc_html__( 'Short videos of our products in use, on TikTok and Instagram.', 'qr-minimal-store' );
					?>
				</p>
			</div>
			<?php if ( $reels && $socials ) : ?>
				<p class="qr-section__link">
					<?php
					$links = array();
					foreach ( $socials as $network => $profile ) {
						/* translators: %s: social network */
						$links[] = '<a href="' . esc_url( $profile['url'] ) . '" target="_blank" rel="noopener">' . esc_html( sprintf( __( 'Follow us on %s', 'qr-minimal-store' ), $network ) ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'qr-minimal-store' ) . '</span></a>';
					}
					echo implode( ' · ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php if ( $reels ) : ?>
			<ul class="qr-reels">
				<?php foreach ( $reels as $reel ) : ?>
					<?php $poster = (string) wp_get_attachment_image_url( $reel['product']->get_image_id(), 'woocommerce_single' ); ?>
					<li class="qr-reel">
						<div class="qr-reel__video">
							<?php if ( $reel['src'] ) : ?>
								<video controls playsinline preload="none" poster="<?php echo esc_url( $poster ); ?>" aria-label="<?php echo esc_attr( $reel['hook'] ? $reel['hook'] : $reel['product']->get_name() ); ?>">
									<source src="<?php echo esc_url( $reel['src'] ); ?>" type="video/mp4">
								</video>
							<?php else : ?>
								<a class="qr-reel__post" href="<?php echo esc_url( $reel['post']['url'] ); ?>" target="_blank" rel="noopener">
									<?php echo $reel['product']->get_image( 'woocommerce_single', array( 'alt' => '', 'loading' => 'lazy', 'class' => 'qr-reel__poster' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?>
									<svg class="qr-reel__play" width="56" height="56" viewBox="0 0 80 80" aria-hidden="true" focusable="false"><circle cx="40" cy="40" r="39" fill="#000" fill-opacity="0.45" stroke="#fff" stroke-width="2"/><path d="M33 26l24 14-24 14z" fill="#fff"/></svg>
									<span class="qr-reel__network">
										<?php
										/* translators: %s: TikTok or Instagram */
										echo esc_html( sprintf( __( 'Watch on %s', 'qr-minimal-store' ), $reel['post']['network'] ) );
										?>
										<span class="screen-reader-text">: <?php echo esc_html( $reel['product']->get_name() ); ?> <?php esc_html_e( '(opens in a new tab)', 'qr-minimal-store' ); ?></span>
									</span>
								</a>
							<?php endif; ?>
							<?php if ( $reel['hook'] ) : ?>
								<p class="qr-reel__hook" aria-hidden="true"><?php echo esc_html( $reel['hook'] ); ?></p>
							<?php endif; ?>
							<?php if ( $reel['ai'] ) : ?>
								<span class="qr-reel__ai"><?php esc_html_e( 'AI-generated', 'qr-minimal-store' ); ?></span>
							<?php endif; ?>
						</div>
						<a class="qr-reel__name" href="<?php echo esc_url( $reel['product']->get_permalink() ); ?>"><?php echo esc_html( $reel['product']->get_name() ); ?></a>
						<span class="qr-reel__price"><?php echo wp_kses_post( $reel['product']->get_price_html() ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<ul class="qr-socials">
				<?php foreach ( $socials as $network => $profile ) : ?>
					<li>
						<a class="qr-social qr-social--<?php echo esc_attr( strtolower( $network ) ); ?>" href="<?php echo esc_url( $profile['url'] ); ?>" target="_blank" rel="noopener">
							<span class="qr-social__icon" aria-hidden="true">
								<?php if ( 'TikTok' === $network ) : ?>
									<svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M16.6 3c.4 2.3 1.9 3.8 4.2 4v3.2c-1.5 0-2.9-.4-4.2-1.2v6.3c0 3.4-2.6 5.7-5.7 5.7S5.2 18.6 5.2 15.4c0-3.3 2.8-5.8 6.3-5.5v3.3c-1.5-.3-3 .7-3 2.2 0 1.3 1 2.3 2.4 2.3 1.4 0 2.4-1 2.4-2.6V3h3.3z"/></svg>
								<?php else : ?>
									<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
								<?php endif; ?>
							</span>
							<span class="qr-social__text">
								<span class="qr-social__network"><?php echo esc_html( $network ); ?></span>
								<span class="qr-social__handle"><?php echo esc_html( $profile['handle'] ); ?></span>
								<span class="qr-social__desc"><?php esc_html_e( 'Unboxings, set-ups and how-tos of the products we sell.', 'qr-minimal-store' ); ?></span>
							</span>
							<span class="qr-social__cta">
								<?php
								/* translators: %s: social network */
								echo esc_html( sprintf( __( 'Follow us on %s', 'qr-minimal-store' ), $network ) );
								?>
								<span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'qr-minimal-store' ); ?></span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

</div>
<?php
get_footer();
