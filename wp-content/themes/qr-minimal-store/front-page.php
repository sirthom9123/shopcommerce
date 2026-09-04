<?php
get_header();
?>
<section class="hero-campaign">
	<div class="container hero-campaign__grid">
		<div class="hero-campaign__content">
			<p class="eyebrow"><?php esc_html_e( 'Power, security, and smart essentials', 'qr-minimal-store' ); ?></p>
			<h1><?php esc_html_e( 'Technology that keeps your world moving.', 'qr-minimal-store' ); ?></h1>
			<p class="hero-campaign__copy"><?php esc_html_e( 'Discover dependable power backup, smart security, computers, and accessories selected for home and business.', 'qr-minimal-store' ); ?></p>
			<p class="hero-campaign__offer"><?php esc_html_e( 'Built for productive days and protected spaces.', 'qr-minimal-store' ); ?></p>
			<div class="hero-campaign__actions">
				<a class="button" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop now', 'qr-minimal-store' ); ?></a>
				<a class="button button--ghost" href="#featured-products"><?php esc_html_e( 'Featured picks', 'qr-minimal-store' ); ?></a>
			</div>
		</div>
		<div class="hero-campaign__visual" aria-hidden="true">
			<?php
			$hero_image_id = (int) get_theme_mod( 'qr_hero_image', 0 );
			if ( $hero_image_id ) {
				echo wp_get_attachment_image(
					$hero_image_id,
					'large',
					false,
					array(
						'class'   => 'hero-campaign__product-img',
						'loading' => 'eager',
					)
				);
			} else {
				?>
			<span class="hero-campaign__orb hero-campaign__orb--large"></span>
			<span class="hero-campaign__orb hero-campaign__orb--small"></span>
				<?php
			}
			?>
		</div>
	</div>
</section>

<section class="category-rail" aria-labelledby="category-rail-title">
	<div class="container">
		<h2 id="category-rail-title" class="screen-reader-text"><?php esc_html_e( 'Popular categories', 'qr-minimal-store' ); ?></h2>
		<?php qr_minimal_store_render_category_cards( 5 ); ?>
	</div>
</section>

<section class="section section--tight benefits">
	<div class="container benefit-grid">
		<div>
			<span class="benefit-icon" aria-hidden="true">🚚</span>
			<strong><?php esc_html_e( 'Fast Dispatch', 'qr-minimal-store' ); ?></strong>
			<span><?php esc_html_e( 'Orders processed quickly on business days.', 'qr-minimal-store' ); ?></span>
		</div>
		<div>
			<span class="benefit-icon" aria-hidden="true">🔒</span>
			<strong><?php esc_html_e( 'Secure Payments', 'qr-minimal-store' ); ?></strong>
			<span><?php esc_html_e( 'Trusted gateways and protected checkout flow.', 'qr-minimal-store' ); ?></span>
		</div>
		<div>
			<span class="benefit-icon" aria-hidden="true">💬</span>
			<strong><?php esc_html_e( 'Local Support', 'qr-minimal-store' ); ?></strong>
			<span><?php esc_html_e( 'Get help with pre-sale and post-sale product choices.', 'qr-minimal-store' ); ?></span>
		</div>
		<div>
			<span class="benefit-icon" aria-hidden="true">🛡️</span>
			<strong><?php esc_html_e( 'Warranty Friendly', 'qr-minimal-store' ); ?></strong>
			<span><?php esc_html_e( 'Straightforward warranty and returns communication.', 'qr-minimal-store' ); ?></span>
		</div>
	</div>
</section>

<section class="section section--tight hot-products">
	<div class="container">
		<div class="section-head section-head--split">
			<h2><?php esc_html_e( 'Hot Products Today', 'qr-minimal-store' ); ?></h2>
			<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'See all products', 'qr-minimal-store' ); ?></a>
		</div>
		<?php echo do_shortcode( '[products limit="4" columns="4" best_selling="true"]' ); ?>
	</div>
</section>

<?php
$deal_product_id = (int) get_theme_mod( 'qr_deal_product', 0 );
if ( $deal_product_id && function_exists( 'wc_get_product' ) ) :
	$deal = wc_get_product( $deal_product_id );
	if ( $deal && $deal->is_on_sale() ) :
		$sale_end      = $deal->get_date_on_sale_to();
		$end_timestamp = $sale_end ? $sale_end->getTimestamp() : 0;
		?>
<section class="section deal-of-day" data-deal-end="<?php echo esc_attr( (string) $end_timestamp ); ?>">
	<div class="container">
		<div class="section-head">
			<h2><?php esc_html_e( 'Deal of the Day', 'qr-minimal-store' ); ?></h2>
		</div>
		<div class="deal-of-day__grid">
			<div class="deal-of-day__media">
				<?php echo $deal->get_image( 'woocommerce_single' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?>
			</div>
			<div class="deal-of-day__info">
				<h3 class="deal-of-day__title"><?php echo esc_html( $deal->get_name() ); ?></h3>
				<p class="deal-of-day__price"><?php echo $deal->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce price HTML. ?></p>
				<?php if ( $end_timestamp ) : ?>
					<div class="deal-countdown" aria-label="<?php esc_attr_e( 'Time remaining', 'qr-minimal-store' ); ?>">
						<div class="deal-countdown__unit"><span data-days>00</span><small><?php esc_html_e( 'Days', 'qr-minimal-store' ); ?></small></div>
						<div class="deal-countdown__unit"><span data-hours>00</span><small><?php esc_html_e( 'Hours', 'qr-minimal-store' ); ?></small></div>
						<div class="deal-countdown__unit"><span data-minutes>00</span><small><?php esc_html_e( 'Min', 'qr-minimal-store' ); ?></small></div>
						<div class="deal-countdown__unit"><span data-seconds>00</span><small><?php esc_html_e( 'Sec', 'qr-minimal-store' ); ?></small></div>
					</div>
				<?php endif; ?>
				<a class="button" href="<?php echo esc_url( $deal->get_permalink() ); ?>"><?php esc_html_e( 'Shop this deal', 'qr-minimal-store' ); ?></a>
			</div>
		</div>
	</div>
</section>
		<?php
	endif;
endif;
?>

<section id="featured-products" class="section section--alt">
	<div class="container">
		<div class="section-head">
			<h2><?php esc_html_e( 'Featured Products', 'qr-minimal-store' ); ?></h2>
		</div>
		<?php echo do_shortcode( '[products limit="8" columns="4" visibility="featured" orderby="date" order="DESC"]' ); ?>
	</div>
</section>

<section class="section product-tabs-section" data-product-tabs>
	<div class="container">
		<div class="section-head section-head--split product-tabs__header">
			<h2><?php esc_html_e( 'Shop Highlights', 'qr-minimal-store' ); ?></h2>
			<div class="product-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Product collections', 'qr-minimal-store' ); ?>">
				<button type="button" class="product-tabs__tab is-active" role="tab" id="tab-new" aria-selected="true" aria-controls="panel-new" data-tab="new">
					<?php esc_html_e( 'New Arrivals', 'qr-minimal-store' ); ?>
				</button>
				<button type="button" class="product-tabs__tab" role="tab" id="tab-top" aria-selected="false" aria-controls="panel-top" data-tab="top">
					<?php esc_html_e( 'Top Rated', 'qr-minimal-store' ); ?>
				</button>
				<button type="button" class="product-tabs__tab" role="tab" id="tab-best" aria-selected="false" aria-controls="panel-best" data-tab="best">
					<?php esc_html_e( 'Best Sellers', 'qr-minimal-store' ); ?>
				</button>
			</div>
		</div>
		<div class="product-tabs__panels">
			<div class="product-tabs__panel is-active" role="tabpanel" id="panel-new" aria-labelledby="tab-new" data-panel="new">
				<?php echo do_shortcode( '[recent_products limit="8" columns="4" orderby="date" order="DESC"]' ); ?>
			</div>
			<div class="product-tabs__panel" role="tabpanel" id="panel-top" aria-labelledby="tab-top" data-panel="top" hidden>
				<?php echo do_shortcode( '[top_rated_products limit="8" columns="4"]' ); ?>
			</div>
			<div class="product-tabs__panel" role="tabpanel" id="panel-best" aria-labelledby="tab-best" data-panel="best" hidden>
				<?php echo do_shortcode( '[best_selling_products limit="8" columns="4"]' ); ?>
			</div>
		</div>
	</div>
</section>

<section class="section trust-strip">
	<div class="container trust-strip__grid">
		<div>
			<h3><?php esc_html_e( 'Curated range', 'qr-minimal-store' ); ?></h3>
			<p><?php esc_html_e( 'Focused catalog across power, security, and productivity.', 'qr-minimal-store' ); ?></p>
		</div>
		<div>
			<h3><?php esc_html_e( 'Secure checkout', 'qr-minimal-store' ); ?></h3>
			<p><?php esc_html_e( 'Simple checkout flow designed for fast order completion.', 'qr-minimal-store' ); ?></p>
		</div>
		<div>
			<h3><?php esc_html_e( 'Local support', 'qr-minimal-store' ); ?></h3>
			<p><?php esc_html_e( 'Helpful account tools and support for every order.', 'qr-minimal-store' ); ?></p>
		</div>
	</div>
</section>

<section class="section section--tight brand-strip-wrap">
	<div class="container">
		<div class="section-head section-head--split">
			<h2><?php esc_html_e( 'Known Brands', 'qr-minimal-store' ); ?></h2>
			<span><?php esc_html_e( 'Trusted names we stock', 'qr-minimal-store' ); ?></span>
		</div>
		<ul class="brand-strip" aria-label="<?php esc_attr_e( 'Known brands', 'qr-minimal-store' ); ?>">
			<li>Acer</li>
			<li>Apple</li>
			<li>Asus</li>
			<li>Dell</li>
			<li>Lenovo</li>
			<li>Logitech</li>
		</ul>
	</div>
</section>
<?php
if ( function_exists( 'wc_get_products' ) ) {
	$viewed_raw = '';
	if ( isset( $_COOKIE['woocommerce_recently_viewed'] ) ) {
		$viewed_raw = sanitize_text_field( wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ) );
	}
	$viewed = array_filter( array_map( 'absint', explode( '|', $viewed_raw ) ) );
	if ( ! empty( $viewed ) ) {
		$viewed_ids = implode( ',', array_map( 'absint', array_slice( $viewed, 0, 4 ) ) );
		?>
<section class="section section--tight recently-viewed">
	<div class="container">
		<div class="section-head section-head--split">
			<h2><?php esc_html_e( 'Recently Viewed', 'qr-minimal-store' ); ?></h2>
		</div>
		<?php echo do_shortcode( '[products ids="' . esc_attr( $viewed_ids ) . '" columns="4"]' ); ?>
	</div>
</section>
		<?php
	}
}
get_footer();
?>
