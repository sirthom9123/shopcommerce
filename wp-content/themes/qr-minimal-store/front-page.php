<?php
get_header();
?>
<section class="hero">
	<div class="container hero-grid">
		<div class="hero__inner">
			<p class="eyebrow"><?php esc_html_e( 'Power, security, and smart essentials', 'qr-minimal-store' ); ?></p>
			<h1><?php esc_html_e( 'Minimal tech store built for everyday performance.', 'qr-minimal-store' ); ?></h1>
			<p><?php esc_html_e( 'Inspired by Electro Home v12: clear merchandising, high-intent categories, and fast paths to checkout.', 'qr-minimal-store' ); ?></p>
			<div class="hero__actions">
				<a class="button" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop now', 'qr-minimal-store' ); ?></a>
				<a class="button button--ghost" href="#featured-products"><?php esc_html_e( 'Featured picks', 'qr-minimal-store' ); ?></a>
			</div>
		</div>
		<div class="promo-stack">
			<article class="promo-card">
				<p class="eyebrow"><?php esc_html_e( 'UPS + Power Backup', 'qr-minimal-store' ); ?></p>
				<h3><?php esc_html_e( 'Keep business online during outages.', 'qr-minimal-store' ); ?></h3>
				<a href="<?php echo esc_url( home_url( '/product-category/ups/' ) ); ?>"><?php esc_html_e( 'Shop UPS', 'qr-minimal-store' ); ?></a>
			</article>
			<article class="promo-card">
				<p class="eyebrow"><?php esc_html_e( 'CCTV Deals', 'qr-minimal-store' ); ?></p>
				<h3><?php esc_html_e( 'Smart surveillance bundles for home and retail.', 'qr-minimal-store' ); ?></h3>
				<a href="<?php echo esc_url( home_url( '/product-category/cctv/' ) ); ?>"><?php esc_html_e( 'View Cameras', 'qr-minimal-store' ); ?></a>
			</article>
		</div>
	</div>
</section>

<section class="section section--tight benefits">
	<div class="container benefit-grid">
		<div><strong><?php esc_html_e( 'Fast Dispatch', 'qr-minimal-store' ); ?></strong><span><?php esc_html_e( 'Orders processed quickly on business days.', 'qr-minimal-store' ); ?></span></div>
		<div><strong><?php esc_html_e( 'Secure Payments', 'qr-minimal-store' ); ?></strong><span><?php esc_html_e( 'Trusted gateways and protected checkout flow.', 'qr-minimal-store' ); ?></span></div>
		<div><strong><?php esc_html_e( 'Local Support', 'qr-minimal-store' ); ?></strong><span><?php esc_html_e( 'Get help with pre-sale and post-sale product choices.', 'qr-minimal-store' ); ?></span></div>
		<div><strong><?php esc_html_e( 'Warranty Friendly', 'qr-minimal-store' ); ?></strong><span><?php esc_html_e( 'Straightforward warranty and returns communication.', 'qr-minimal-store' ); ?></span></div>
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

<section class="section">
	<div class="container">
		<div class="section-head">
			<h2><?php esc_html_e( 'Browse Categories', 'qr-minimal-store' ); ?></h2>
		</div>
		<?php echo do_shortcode( '[product_categories number="8" columns="4" parent="0"]' ); ?>
	</div>
</section>

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
get_footer();
?>
