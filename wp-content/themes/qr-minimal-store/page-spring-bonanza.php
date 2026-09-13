<?php
/**
 * Template Name: Spring Bonanza
 * Description: Spring Bonanza 2026 sale landing page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

$promo_ids     = function_exists( 'qr_minimal_store_promo_product_ids' ) ? qr_minimal_store_promo_product_ids() : array();
$featured_ids  = array_slice( $promo_ids, 0, 4 );
$end_timestamp = function_exists( 'qr_minimal_store_active_promo_end' ) ? qr_minimal_store_active_promo_end() : 0;
if ( $end_timestamp < 1 ) {
	$end           = new DateTime( '2026-09-30 23:59:59', wp_timezone() );
	$end_timestamp = $end->getTimestamp();
}

$category_cards = array(
	array(
		'heading' => __( 'Smart Home', 'qr-minimal-store' ),
		'blurb'   => __( 'Scales, kettles, air purifiers', 'qr-minimal-store' ),
		'slugs'   => array( 'smart-home' ),
	),
	array(
		'heading' => __( 'Personal Care', 'qr-minimal-store' ),
		'blurb'   => __( 'Hair dryers, steamers, grooming', 'qr-minimal-store' ),
		'slugs'   => array( 'personal-grooming-wellness', 'personal-care', 'grooming', 'household-gadgets' ),
	),
	array(
		'heading' => __( 'Health & Fitness', 'qr-minimal-store' ),
		'blurb'   => __( 'Massage guns, smart watches', 'qr-minimal-store' ),
		'slugs'   => array( 'health-fitness', 'health', 'fitness', 'smart-watches-bands' ),
	),
);

get_header();
?>
<section class="bonanza-hero" data-bonanza-end="<?php echo esc_attr( (string) $end_timestamp ); ?>">
	<div class="container bonanza-hero__grid">
		<div class="bonanza-hero__content">
			<p class="eyebrow"><?php esc_html_e( 'Limited Time — Sep 7–30', 'qr-minimal-store' ); ?></p>
			<h1><?php esc_html_e( 'Spring Bonanza — Big Savings on Smart Home & Grooming', 'qr-minimal-store' ); ?></h1>
			<p class="bonanza-hero__copy"><?php esc_html_e( 'Smart gadgets, kitchen appliances, and lifestyle essentials from trusted brands. Authorized Xiaomi dealer with local warranty.', 'qr-minimal-store' ); ?></p>
			<div class="bonanza-countdown deal-countdown" aria-label="<?php esc_attr_e( 'Time remaining', 'qr-minimal-store' ); ?>">
				<div class="deal-countdown__unit"><span data-days>00</span><small><?php esc_html_e( 'Days', 'qr-minimal-store' ); ?></small></div>
				<div class="deal-countdown__unit"><span data-hours>00</span><small><?php esc_html_e( 'Hours', 'qr-minimal-store' ); ?></small></div>
				<div class="deal-countdown__unit"><span data-minutes>00</span><small><?php esc_html_e( 'Min', 'qr-minimal-store' ); ?></small></div>
				<div class="deal-countdown__unit"><span data-seconds>00</span><small><?php esc_html_e( 'Sec', 'qr-minimal-store' ); ?></small></div>
			</div>
			<p class="bonanza-countdown__ended" data-bonanza-ended hidden><?php esc_html_e( 'Sale ended', 'qr-minimal-store' ); ?></p>
		</div>
		<div class="bonanza-hero__visual" aria-hidden="true">
			<span class="bonanza-hero__orb bonanza-hero__orb--large"></span>
			<span class="bonanza-hero__orb bonanza-hero__orb--small"></span>
		</div>
	</div>
	<div class="bonanza-trust-bar">
		<div class="container">
			<ul class="bonanza-trust-bar__list">
				<li><span class="bonanza-trust-bar__dot" aria-hidden="true"></span><?php esc_html_e( 'Authorized Xiaomi Dealer', 'qr-minimal-store' ); ?></li>
				<li><span class="bonanza-trust-bar__dot" aria-hidden="true"></span><?php esc_html_e( 'Free Delivery Over R2,000', 'qr-minimal-store' ); ?></li>
				<li><span class="bonanza-trust-bar__dot" aria-hidden="true"></span><?php esc_html_e( 'Local Warranty & Support', 'qr-minimal-store' ); ?></li>
			</ul>
		</div>
	</div>
</section>

<section id="featured-products" class="section">
	<div class="container">
		<div class="section-head">
			<h2><?php esc_html_e( 'Top Picks This Spring', 'qr-minimal-store' ); ?></h2>
		</div>
		<?php qr_minimal_store_render_product_ids( $featured_ids, 4 ); ?>
	</div>
</section>

<section class="section benefits bonanza-benefits" aria-labelledby="bonanza-why-heading">
	<div class="container">
		<div class="section-head">
			<h2 id="bonanza-why-heading"><?php esc_html_e( 'Why Shop With Us', 'qr-minimal-store' ); ?></h2>
		</div>
		<div class="benefit-grid">
			<div>
				<span class="benefit-icon" aria-hidden="true">🚚</span>
				<strong><?php esc_html_e( 'Free Delivery', 'qr-minimal-store' ); ?></strong>
				<span><?php esc_html_e( 'On orders over R2,000', 'qr-minimal-store' ); ?></span>
			</div>
			<div>
				<span class="benefit-icon" aria-hidden="true">🛡️</span>
				<strong><?php esc_html_e( 'Local Warranty', 'qr-minimal-store' ); ?></strong>
				<span><?php esc_html_e( 'SA-based support & returns', 'qr-minimal-store' ); ?></span>
			</div>
			<div>
				<span class="benefit-icon" aria-hidden="true">✅</span>
				<strong><?php esc_html_e( 'Authorized Dealer', 'qr-minimal-store' ); ?></strong>
				<span><?php esc_html_e( 'Genuine Xiaomi products', 'qr-minimal-store' ); ?></span>
			</div>
			<div>
				<span class="benefit-icon" aria-hidden="true">💳</span>
				<strong><?php esc_html_e( 'Secure Checkout', 'qr-minimal-store' ); ?></strong>
				<span><?php esc_html_e( 'Trusted payment gateways', 'qr-minimal-store' ); ?></span>
			</div>
		</div>
	</div>
</section>

<section class="section section--alt">
	<div class="container">
		<div class="section-head">
			<h2><?php esc_html_e( 'Shop by Category', 'qr-minimal-store' ); ?></h2>
		</div>
		<ul class="bonanza-cat-grid">
			<?php foreach ( $category_cards as $card ) : ?>
				<?php
				$term = function_exists( 'qr_minimal_store_find_category' ) ? qr_minimal_store_find_category( $card['slugs'] ) : null;
				$link = $shop_url;
				if ( $term && in_array( $term->slug, $card['slugs'], true ) ) {
					$term_link = get_term_link( $term );
					if ( ! is_wp_error( $term_link ) ) {
						$link = $term_link;
					}
				}
				?>
				<li>
					<a class="bonanza-cat-card" href="<?php echo esc_url( $link ); ?>">
						<strong><?php echo esc_html( $card['heading'] ); ?></strong>
						<span><?php echo esc_html( $card['blurb'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="section-head">
			<div>
				<h2><?php esc_html_e( 'Spring Bonanza Sale', 'qr-minimal-store' ); ?></h2>
				<p class="bonanza-section-lede"><?php esc_html_e( 'Every product in this promo — sale prices are shown on each item', 'qr-minimal-store' ); ?></p>
			</div>
		</div>
		<?php qr_minimal_store_render_product_ids( $promo_ids, 4 ); ?>
	</div>
</section>

<section class="section trust-strip bonanza-trust-strip">
	<div class="container trust-strip__grid">
		<div>
			<h3><?php esc_html_e( 'Authorized Xiaomi Dealer', 'qr-minimal-store' ); ?></h3>
			<p><?php esc_html_e( 'Every product is genuine with full manufacturer warranty', 'qr-minimal-store' ); ?></p>
		</div>
		<div>
			<h3><?php esc_html_e( 'South African Business', 'qr-minimal-store' ); ?></h3>
			<p><?php esc_html_e( 'Local support, local shipping, local returns', 'qr-minimal-store' ); ?></p>
		</div>
		<div>
			<h3><?php esc_html_e( 'Spring Bonanza Guarantee', 'qr-minimal-store' ); ?></h3>
			<p><?php esc_html_e( 'Sale prices on every Spring Bonanza product, no minimum order required', 'qr-minimal-store' ); ?></p>
		</div>
	</div>
</section>

<section class="bonanza-final-cta">
	<div class="container">
		<h2><?php esc_html_e( 'Don\'t miss out — Spring Bonanza ends Sep 30', 'qr-minimal-store' ); ?></h2>
		<a class="button" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop Now', 'qr-minimal-store' ); ?></a>
	</div>
</section>
<?php
get_footer();
