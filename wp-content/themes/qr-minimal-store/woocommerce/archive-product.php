<?php
/**
 * Shop, category, brand and product search listing (design board E).
 *
 * @package qr-minimal-store
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

global $wp_query;

$context  = qr_minimal_store_shop_context();
$filters  = qr_minimal_store_shop_filters();
$facets   = qr_minimal_store_shop_facets();
$bands    = qr_minimal_store_price_bands();
$base     = qr_minimal_store_listing_base();
$total    = (int) $wp_query->found_posts;
$paged    = max( 1, (int) get_query_var( 'paged' ) );
$shown    = min( $total, $paged * (int) $wp_query->get( 'posts_per_page' ) );
$next_url = $paged < (int) $wp_query->max_num_pages ? get_pagenum_link( $paged + 1 ) : '';
$active   = $filters['stock_all'] || $filters['price'] || $filters['brand'];
$clear    = qr_minimal_store_listing_url( array( 'sort' => $filters['sort'] ) );
$rule     = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;

/* translators: %d: number of products */
$count_text = sprintf( _n( '%d product', '%d products', $total, 'qr-minimal-store' ), $total );

// Hidden fields that keep the listing (search terms, department) when a form is sent.
$keep_fields = static function ( $extra = array() ) use ( $base ) {
	foreach ( array_merge( $base['keep'], $extra ) as $name => $value ) {
		printf( '<input type="hidden" name="%1$s" value="%2$s">', esc_attr( $name ), esc_attr( $value ) );
	}
};

// Facet data for the phone filter sheet ("Show N products" updates while ticking).
$facet_json = array(
	'rows'  => $facets['rows'],
	'bands' => array_map(
		static fn( $band ) => array( $band['min'], $band['max'] ),
		$bands
	),
);
?>
<div class="container qr-shop">
	<?php woocommerce_output_all_notices(); ?>

	<header class="qr-shop__head">
		<nav class="qr-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'qr-minimal-store' ); ?>">
			<ol>
				<?php foreach ( $context['crumbs'] as $crumb ) : ?>
					<li>
						<?php if ( $crumb[1] ) : ?>
							<a href="<?php echo esc_url( $crumb[1] ); ?>"><?php echo esc_html( $crumb[0] ); ?></a>
						<?php else : ?>
							<span aria-current="page"><?php echo esc_html( $crumb[0] ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<h1 class="qr-shop__title"><?php echo esc_html( $context['title'] ); ?></h1>
		<?php if ( $context['intro'] ) : ?>
			<p class="qr-shop__intro"><?php echo esc_html( $context['intro'] ); ?></p>
		<?php endif; ?>
		<?php if ( $context['chips'] ) : ?>
			<ul class="qr-chips" aria-label="<?php echo esc_attr( $context['chips_label'] ); ?>">
				<?php foreach ( $context['chips'] as $chip ) : ?>
					<li>
						<a class="qr-chip<?php echo $chip[3] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $chip[2] ); ?>"<?php echo $chip[3] ? ' aria-current="page"' : ''; ?>>
							<?php echo esc_html( $chip[0] ); ?><?php echo $chip[1] ? ' <span class="qr-chip__count">(' . esc_html( (string) $chip[1] ) . ')</span>' : ''; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</header>

	<div class="qr-shop__layout">
		<aside class="qr-shop__rail">
			<div class="qr-filters" id="qr-filters" role="dialog" aria-modal="true" aria-labelledby="qr-filters-title" data-qr-filters>
				<div class="qr-filters__head">
					<h2 class="qr-filters__title" id="qr-filters-title"><?php esc_html_e( 'Filter', 'qr-minimal-store' ); ?></h2>
					<button type="button" class="qr-filters__close" data-qr-filters-close>
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>
						<span class="screen-reader-text"><?php esc_html_e( 'Close filters', 'qr-minimal-store' ); ?></span>
					</button>
				</div>
				<form class="qr-filters__form" method="get" action="<?php echo esc_url( $base['url'] ); ?>" data-qr-facets="<?php echo esc_attr( wp_json_encode( $facet_json ) ); ?>">
					<?php $keep_fields( 'recommended' !== $filters['sort'] ? array( 'sort' => $filters['sort'] ) : array() ); ?>
					<div class="qr-filters__body">
						<fieldset class="qr-filter">
							<legend><?php esc_html_e( 'Availability', 'qr-minimal-store' ); ?></legend>
							<p class="qr-filter__fixed">
								<svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><rect x="0.75" y="0.75" width="18.5" height="18.5" rx="4" fill="currentColor"/><path d="M5.5 10.5l3 3 6-6.5" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
								<span><?php esc_html_e( 'In stock', 'qr-minimal-store' ); ?> <span class="qr-filter__count" data-qr-count="in_stock">(<?php echo esc_html( (string) $facets['in_stock'] ); ?>)</span></span>
							</p>
							<?php if ( $facets['sold_out'] || $filters['stock_all'] ) : ?>
								<label class="qr-filter__option">
									<input type="checkbox" name="stock" value="all" <?php checked( $filters['stock_all'] ); ?>>
									<span><?php esc_html_e( 'Include out of stock', 'qr-minimal-store' ); ?> <span class="qr-filter__count" data-qr-count="sold_out">(<?php echo esc_html( (string) $facets['sold_out'] ); ?>)</span></span>
								</label>
							<?php endif; ?>
						</fieldset>
						<fieldset class="qr-filter">
							<legend><?php esc_html_e( 'Price', 'qr-minimal-store' ); ?></legend>
							<?php foreach ( $bands as $key => $band ) : ?>
								<?php
								$checked = in_array( $key, $filters['price'], true );
								if ( ! $facets['price'][ $key ] && ! $checked ) {
									continue;
								}
								?>
								<label class="qr-filter__option">
									<input type="checkbox" name="price[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked ); ?>>
									<span><?php echo esc_html( $band['label'] ); ?> <span class="qr-filter__count" data-qr-count="price:<?php echo esc_attr( $key ); ?>">(<?php echo esc_html( (string) $facets['price'][ $key ] ); ?>)</span></span>
								</label>
							<?php endforeach; ?>
						</fieldset>
						<?php if ( $facets['brands'] && ! is_tax( 'product_brand' ) ) : ?>
							<fieldset class="qr-filter">
								<legend><?php esc_html_e( 'Brand', 'qr-minimal-store' ); ?></legend>
								<?php foreach ( $facets['brands'] as $slug => $name ) : ?>
									<?php
									$checked = in_array( $slug, $filters['brand'], true );
									if ( ! $facets['brand'][ $slug ] && ! $checked ) {
										continue;
									}
									?>
									<label class="qr-filter__option">
										<input type="checkbox" name="brand[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $checked ); ?>>
										<span><?php echo esc_html( $name ); ?> <span class="qr-filter__count" data-qr-count="brand:<?php echo esc_attr( $slug ); ?>">(<?php echo esc_html( (string) $facets['brand'][ $slug ] ); ?>)</span></span>
									</label>
								<?php endforeach; ?>
							</fieldset>
						<?php endif; ?>
					</div>
					<div class="qr-filters__foot">
						<a class="qr-filters__clear<?php echo $active ? '' : ' is-idle'; ?>" href="<?php echo esc_url( $clear ); ?>"><?php esc_html_e( 'Clear all', 'qr-minimal-store' ); ?></a>
						<button type="submit" class="qr-btn qr-filters__apply" data-qr-filters-apply>
							<?php
							/* translators: %d: number of products */
							echo esc_html( sprintf( _n( 'Show %d product', 'Show %d products', $total, 'qr-minimal-store' ), $total ) );
							?>
						</button>
					</div>
				</form>
			</div>
			<div class="qr-shop__info">
				<div class="qr-shop__credit">
					<strong><?php esc_html_e( 'Pay monthly on any of these', 'qr-minimal-store' ); ?></strong>
					<?php esc_html_e( 'Mobicred or an RCS store card at checkout, same price as cash. Subject to approval.', 'qr-minimal-store' ); ?>
				</div>
				<?php if ( $rule ) : ?>
					<div class="qr-shop__delivery">
						<strong><?php esc_html_e( 'Delivery', 'qr-minimal-store' ); ?></strong>
						<?php
						/* translators: 1: delivery fee, 2: free delivery threshold */
						echo esc_html( sprintf( __( '%1$s, free from %2$s. 2–3 working days.', 'qr-minimal-store' ), qr_minimal_store_money_whole( $rule['fee'] ), qr_minimal_store_money_whole( $rule['threshold'] ) ) );
						?>
					</div>
				<?php endif; ?>
			</div>
		</aside>

		<div class="qr-shop__main">
			<div class="qr-shop__bar">
				<p class="qr-shop__count" role="status">
					<strong><?php echo esc_html( $count_text ); ?></strong>
					<?php if ( 'recommended' === $filters['sort'] ) : ?>
						<span>· <?php esc_html_e( 'in stock first', 'qr-minimal-store' ); ?></span>
					<?php endif; ?>
				</p>
				<div class="qr-shop__tools">
					<button type="button" class="qr-shop__filter-btn" aria-controls="qr-filters" aria-expanded="false" data-qr-filters-open>
						<?php esc_html_e( 'Filter', 'qr-minimal-store' ); ?>
						<?php if ( $active ) : ?>
							<span class="qr-shop__filter-dot"><span class="screen-reader-text"><?php esc_html_e( '(filters on)', 'qr-minimal-store' ); ?></span></span>
						<?php endif; ?>
					</button>
					<form class="qr-sort" method="get" action="<?php echo esc_url( $base['url'] ); ?>">
						<?php
						$keep_sort = array();
						if ( $filters['stock_all'] ) {
							$keep_sort['stock'] = 'all';
						}
						if ( $filters['price'] ) {
							$keep_sort['price'] = implode( ',', $filters['price'] );
						}
						if ( $filters['brand'] ) {
							$keep_sort['brand'] = implode( ',', $filters['brand'] );
						}
						$keep_fields( $keep_sort );
						?>
						<label for="qr-sort"><?php esc_html_e( 'Sort', 'qr-minimal-store' ); ?></label>
						<select id="qr-sort" name="sort" data-qr-sort>
							<?php foreach ( qr_minimal_store_sort_options() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['sort'], $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="qr-sort__go"><?php esc_html_e( 'Sort', 'qr-minimal-store' ); ?></button>
					</form>
				</div>
			</div>

			<?php if ( have_posts() ) : ?>
				<ul class="qr-cards qr-shop__grid" data-qr-grid>
					<?php
					$index = 0;
					while ( have_posts() ) {
						the_post();
						$product = wc_get_product( get_the_ID() );
						if ( ! $product || ! $product->is_visible() ) {
							continue;
						}
						qr_minimal_store_product_card( $product, array( 'free_tag' => true ) );
						++$index;
						if ( 1 === $paged && ( 3 === $index || ( $index === $total && $index < 3 ) ) ) {
							?>
							<li class="qr-shop__help">
								<div>
									<p class="qr-shop__help-title"><?php esc_html_e( 'Not sure which one?', 'qr-minimal-store' ); ?></p>
									<p><?php esc_html_e( "Email us what you need and we'll help you choose.", 'qr-minimal-store' ); ?></p>
								</div>
								<?php /* translators: %s: listing title */ ?>
								<a class="qr-btn qr-btn--outline" href="<?php echo esc_url( 'mailto:orders@shop.yehudasolutions.com?subject=' . rawurlencode( sprintf( __( 'Help choosing: %s', 'qr-minimal-store' ), $context['title'] ) ) ); ?>"><?php esc_html_e( 'Ask us', 'qr-minimal-store' ); ?></a>
							</li>
							<?php
						}
					}
					?>
				</ul>

				<div class="qr-shop__more" data-qr-more>
					<p class="qr-shop__progress-text" data-qr-progress-text>
						<?php
						if ( $paged > 1 ) {
							/* translators: 1: first product number, 2: last product number, 3: total products */
							echo esc_html( sprintf( __( 'Showing %1$d–%2$d of %3$d', 'qr-minimal-store' ), ( $paged - 1 ) * (int) $wp_query->get( 'posts_per_page' ) + 1, $shown, $total ) );
						} else {
							/* translators: 1: products shown, 2: total products */
							echo esc_html( sprintf( __( 'Showing %1$d of %2$d', 'qr-minimal-store' ), $shown, $total ) );
						}
						?>
					</p>
					<div class="qr-shop__progress" aria-hidden="true"><span data-qr-progress style="width: <?php echo esc_attr( (string) ( $total ? round( 100 * $shown / $total ) : 100 ) ); ?>%"></span></div>
					<?php if ( $next_url ) : ?>
						<a class="qr-btn qr-btn--outline qr-shop__more-btn" href="<?php echo esc_url( $next_url ); ?>" data-qr-more-link data-total="<?php echo esc_attr( (string) $total ); ?>" data-shown="<?php echo esc_attr( (string) $shown ); ?>"><?php esc_html_e( 'Show more', 'qr-minimal-store' ); ?></a>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="qr-shop__empty">
					<p class="qr-shop__empty-title"><?php esc_html_e( 'No products match these filters.', 'qr-minimal-store' ); ?></p>
					<p>
						<?php if ( ! $filters['stock_all'] && $facets['sold_out'] ) : ?>
							<a href="<?php echo esc_url( qr_minimal_store_listing_url( array_merge( $filters, array( 'stock_all' => true ) ) ) ); ?>">
								<?php
								/* translators: %d: number of sold-out products */
								echo esc_html( sprintf( _n( 'Show %d out-of-stock product', 'Show %d out-of-stock products', $facets['sold_out'], 'qr-minimal-store' ), $facets['sold_out'] ) );
								?>
							</a>
						<?php endif; ?>
						<?php if ( $active ) : ?>
							<a href="<?php echo esc_url( $clear ); ?>"><?php esc_html_e( 'Clear all filters', 'qr-minimal-store' ); ?></a>
						<?php endif; ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Shop all products', 'qr-minimal-store' ); ?></a>
					</p>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div class="qr-filters-backdrop" data-qr-filters-close hidden></div>
</div>
<?php
woocommerce_reset_loop();
get_footer( 'shop' );
