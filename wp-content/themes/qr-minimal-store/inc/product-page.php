<?php
/**
 * Single product page: buy box, in-page sections and the add-to-cart drawer.
 *
 * Product pages are page-cached (LiteSpeed): everything rendered here depends only on the
 * product, never on the visitor or the cart. Cart-dependent UI (the drawer) is built by
 * product-page.js from the Store API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brand name from the WooCommerce Brands taxonomy, else the "Brand" attribute.
 */
function qr_minimal_store_product_brand( WC_Product $product ) {
	$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	if ( taxonomy_exists( 'product_brand' ) ) {
		$terms = get_the_terms( $id, 'product_brand' );
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			return $terms[0];
		}
	}
	$attr = $product->get_attribute( 'brand' );
	return '' !== $attr ? $attr : null;
}

/**
 * Department = top-level ancestor of the product's first real category.
 *
 * @return WP_Term|null
 */
function qr_minimal_store_product_department( WC_Product $product ) {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( ! is_array( $terms ) ) {
		return null;
	}
	foreach ( $terms as $term ) {
		if ( 'uncategorized' === $term->slug ) {
			continue;
		}
		$ancestors = get_ancestors( $term->term_id, 'product_cat', 'taxonomy' );
		return $ancestors ? get_term( end( $ancestors ), 'product_cat' ) : $term;
	}
	return null;
}

/**
 * Stock state used by the buy box: in-stock | low-stock | backorder | out-of-stock.
 */
function qr_minimal_store_stock_state( WC_Product $product ) {
	if ( $product->is_type( 'variable' ) ) {
		return $product->is_in_stock() ? 'in-stock' : 'out-of-stock';
	}
	if ( $product->is_on_backorder( 1 ) ) {
		return 'backorder';
	}
	if ( ! $product->is_in_stock() ) {
		return 'out-of-stock';
	}
	if ( $product->managing_stock() ) {
		$qty = (int) $product->get_stock_quantity();
		$low = function_exists( 'wc_get_low_stock_amount' ) ? (int) wc_get_low_stock_amount( $product ) : 5;
		if ( $qty > 0 && $qty <= max( 1, $low ) ) {
			return 'low-stock';
		}
	}
	return 'in-stock';
}

/**
 * Backorder panel text: supplier ETA (`_supplier_eta`) and terms (`_backorder_note`), set
 * by the stock sync / store session. Used for simple products and variations.
 *
 * @return array{eta: string, note: string}
 */
function qr_minimal_store_backorder_info( WC_Product $product ) {
	$eta  = trim( (string) $product->get_meta( '_supplier_eta' ) );
	$note = trim( (string) $product->get_meta( '_backorder_note' ) );
	if ( $product->get_parent_id() ) {
		$parent = wc_get_product( $product->get_parent_id() );
		if ( $parent ) {
			$eta  = '' !== $eta ? $eta : trim( (string) $parent->get_meta( '_supplier_eta' ) );
			$note = '' !== $note ? $note : trim( (string) $parent->get_meta( '_backorder_note' ) );
		}
	}
	if ( '' !== $eta && false !== strtotime( $eta ) ) {
		$eta = date_i18n( 'j F Y', strtotime( $eta ) );
	}
	return array(
		'eta'  => $eta,
		'note' => $note,
	);
}

function qr_minimal_store_backorder_panel_html( WC_Product $product ) {
	$info = qr_minimal_store_backorder_info( $product );
	$html = '<div class="qr-backorder"><p class="qr-backorder__title">' . esc_html__( 'Available on backorder', 'qr-minimal-store' ) . '</p><p class="qr-backorder__text">';
	if ( '' !== $info['eta'] ) {
		$html .= sprintf(
			/* translators: %s: supplier's expected stock date */
			esc_html__( 'Supplier\'s expected stock date: %s. Order now and we deliver as soon as it lands, then 2–3 working days to you.', 'qr-minimal-store' ),
			'<strong>' . esc_html( $info['eta'] ) . '</strong>'
		);
	} else {
		$html .= esc_html__( 'Lead time confirmed by the supplier after you order. We deliver as soon as it lands, then 2–3 working days to you.', 'qr-minimal-store' );
	}
	$html .= '</p>';
	if ( '' !== $info['note'] ) {
		$html .= '<p class="qr-backorder__note">' . esc_html(
			sprintf(
				/* translators: %s: supplier's backorder terms */
				__( 'Backorder terms as set by the supplier: %s', 'qr-minimal-store' ),
				$info['note']
			)
		) . '</p>';
	}
	return $html . '</div>';
}

add_action( 'wp', 'qr_minimal_store_product_page_layout' );
function qr_minimal_store_product_page_layout() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	// Buy box order: brand, title, benefit line, price, credit (qr-payment-badges 11),
	// countdown (qr-promos 12), stock, delivery, add to cart, payment line, assurances.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
	add_action( 'woocommerce_single_product_summary', 'qr_minimal_store_buybox_brand', 4 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 6 );
	add_action( 'woocommerce_single_product_summary', 'qr_minimal_store_buybox_vat_note', 10 );
	add_action( 'woocommerce_single_product_summary', 'qr_minimal_store_buybox_stock', 16 );
	add_action( 'woocommerce_single_product_summary', 'qr_minimal_store_buybox_delivery', 17 );
	add_action( 'woocommerce_single_product_summary', 'qr_minimal_store_buybox_payment_line', 35 );
	add_action( 'woocommerce_single_product_summary', 'qr_minimal_store_buybox_assurances', 38 );

	// Our stock line replaces the qr-stock-markers bar (it keeps its out-of-stock notify form).
	if ( class_exists( 'QR_Stock_Markers' ) ) {
		remove_action( 'woocommerce_single_product_summary', array( QR_Stock_Markers::instance(), 'render_stock_bar' ), 15 );
	}

	// Sections below the buy box replace the tabs, up-sells and related products.
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	add_action( 'woocommerce_after_single_product_summary', 'qr_minimal_store_product_sections', 10 );

}

function qr_minimal_store_buybox_brand() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$brand = qr_minimal_store_product_brand( $product );
	$parts = array();
	if ( $brand instanceof WP_Term ) {
		$link    = get_term_link( $brand );
		$parts[] = is_wp_error( $link ) ? esc_html( $brand->name ) : '<a href="' . esc_url( $link ) . '">' . esc_html( $brand->name ) . '</a>';
	} elseif ( is_string( $brand ) ) {
		$parts[] = esc_html( $brand );
	}
	$parts[] = esc_html__( 'Authorised dealer', 'qr-minimal-store' );
	$parts[] = esc_html__( 'Local warranty', 'qr-minimal-store' );
	echo '<p class="qr-buybox__brand">' . implode( ' · ', $parts ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
}

function qr_minimal_store_buybox_vat_note() {
	if ( function_exists( 'wc_tax_enabled' ) && wc_tax_enabled() ) {
		return;
	}
	echo '<p class="qr-buybox__vat">' . esc_html__( 'No VAT added at checkout', 'qr-minimal-store' ) . '</p>';
}

function qr_minimal_store_buybox_stock() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$state = qr_minimal_store_stock_state( $product );
	if ( 'backorder' === $state ) {
		echo qr_minimal_store_backorder_panel_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		return;
	}
	if ( $product->is_type( 'variable' ) ) {
		// Filled per colour/size by product-page.js from the variation data.
		echo '<div class="qr-buybox__variation-stock" data-qr-variation-stock aria-live="polite"></div>';
		return;
	}
	if ( 'out-of-stock' === $state ) {
		printf( '<p class="qr-stock qr-stock--out"><strong>%s</strong></p>', esc_html__( 'Out of stock', 'qr-minimal-store' ) );
		return;
	}
	if ( 'low-stock' === $state ) {
		printf(
			'<p class="qr-stock qr-stock--in"><span><strong>%1$s</strong>, %2$s</span></p>',
			esc_html__( 'In stock', 'qr-minimal-store' ),
			esc_html(
				sprintf(
					/* translators: %d: units left */
					_n( 'only %d left', 'only %d left', (int) $product->get_stock_quantity(), 'qr-minimal-store' ),
					(int) $product->get_stock_quantity()
				)
			)
		);
		return;
	}
	printf(
		'<p class="qr-stock qr-stock--in"><span><strong>%1$s</strong>, %2$s</span></p>',
		esc_html__( 'In stock', 'qr-minimal-store' ),
		esc_html__( 'ready to dispatch', 'qr-minimal-store' )
	);
}

/**
 * Delivery line from the product price alone (cache-safe): free on this item from the
 * qr-shipping threshold, else R50 and how much more makes delivery free.
 */
function qr_minimal_store_buybox_delivery() {
	global $product;
	$rule = qr_minimal_store_free_delivery_rule();
	// Nothing to deliver while sold out (backorders count as in stock).
	if ( ! $product instanceof WC_Product || null === $rule || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	$price = (float) wc_get_price_to_display( $product );
	$icon  = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="17.5" cy="17.5" r="1.6"/></svg>';
	if ( $price >= $rule['threshold'] ) {
		printf(
			'<p class="qr-delivery">%1$s<span><strong>%2$s</strong> %3$s</span></p>',
			$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			esc_html__( 'Free delivery', 'qr-minimal-store' ),
			esc_html__( 'on this item · usually 2–3 working days', 'qr-minimal-store' )
		);
		return;
	}
	$pct = $rule['threshold'] > 0 ? max( 2, min( 100, round( $price / $rule['threshold'] * 100 ) ) ) : 0;
	printf(
		'<div class="qr-delivery qr-delivery--paid">%1$s<span>%2$s · <strong>%3$s</strong> · %4$s</span></div><div class="qr-delivery__track" aria-hidden="true"><div class="qr-delivery__fill" style="width:%5$d%%"></div></div>',
		$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		esc_html(
			sprintf(
				/* translators: %s: delivery fee */
				__( 'Delivery %s', 'qr-minimal-store' ),
				wp_strip_all_tags( wc_price( $rule['fee'], array( 'decimals' => 0 ) ) )
			)
		),
		esc_html(
			sprintf(
				/* translators: %s: amount to add for free delivery */
				__( 'add %s more for free delivery', 'qr-minimal-store' ),
				wp_strip_all_tags( wc_price( $rule['threshold'] - $price ) )
			)
		),
		esc_html__( '2–3 working days', 'qr-minimal-store' ),
		(int) $pct
	);
}

function qr_minimal_store_buybox_payment_line() {
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	$names = array();
	if ( function_exists( 'qr_minimal_store_payfast_methods' ) ) {
		foreach ( qr_minimal_store_payfast_methods() as $method ) {
			$names[] = $method['name'];
		}
	}
	printf(
		'<p class="qr-buybox__pay">%s</p>',
		esc_html(
			sprintf(
				/* translators: %s: payment methods, e.g. "Card, Instant EFT, Mobicred, Store cards" */
				__( 'Secure checkout with Payfast: %s.', 'qr-minimal-store' ),
				implode( ', ', $names )
			)
		)
	);
}

function qr_minimal_store_buybox_assurances() {
	$items = array(
		array( __( 'Local warranty', 'qr-minimal-store' ), __( 'Authorised SA distributor', 'qr-minimal-store' ) ),
		array( __( '30-day returns', 'qr-minimal-store' ), __( 'Unused, original packaging', 'qr-minimal-store' ) ),
		array( __( 'Secure payment', 'qr-minimal-store' ), __( 'Processed by Payfast', 'qr-minimal-store' ) ),
	);
	echo '<ul class="qr-buybox__assurances">';
	foreach ( $items as $item ) {
		printf( '<li><strong>%1$s</strong><span>%2$s</span></li>', esc_html( $item[0] ), esc_html( $item[1] ) );
	}
	echo '</ul>';
}

/**
 * Backorder button text and the per-variation backorder flag used by the swatches.
 */
add_filter( 'woocommerce_product_single_add_to_cart_text', 'qr_minimal_store_backorder_button_text', 10, 2 );
function qr_minimal_store_backorder_button_text( $text, $product ) {
	if ( $product instanceof WC_Product && ! $product->is_type( 'variable' ) && $product->is_on_backorder( 1 ) ) {
		return __( 'Backorder now', 'qr-minimal-store' );
	}
	return $text;
}

add_filter( 'woocommerce_available_variation', 'qr_minimal_store_variation_data', 10, 3 );
function qr_minimal_store_variation_data( $data, $product, $variation ) {
	if ( ! $variation instanceof WC_Product ) {
		return $data;
	}
	$state             = qr_minimal_store_stock_state( $variation );
	$data['qr_stock']  = $state;
	$data['qr_qty']    = $variation->managing_stock() ? (int) $variation->get_stock_quantity() : null;
	$data['qr_panel']  = 'backorder' === $state ? qr_minimal_store_backorder_panel_html( $variation ) : '';
	return $data;
}

function qr_minimal_store_section_nav_items( WC_Product $product, $specs, $video ) {
	$items = array( 'qr-overview' => __( 'Overview', 'qr-minimal-store' ) );
	if ( $video ) {
		$items['qr-video'] = __( 'Video', 'qr-minimal-store' );
	}
	if ( $specs ) {
		$items['qr-specs'] = __( 'Specifications', 'qr-minimal-store' );
	}
	$items['qr-delivery'] = __( 'Delivery and returns', 'qr-minimal-store' );
	if ( comments_open( $product->get_id() ) ) {
		$items['qr-reviews'] = __( 'Reviews', 'qr-minimal-store' );
	}
	return $items;
}

/**
 * Specifications: brand, visible product attributes, SKU, weight and dimensions.
 *
 * @return array<int, array{0: string, 1: string}>
 */
function qr_minimal_store_product_specs( WC_Product $product ) {
	$rows  = array();
	$brand = qr_minimal_store_product_brand( $product );
	if ( $brand ) {
		$rows[] = array( __( 'Brand', 'qr-minimal-store' ), $brand instanceof WP_Term ? $brand->name : $brand );
	}
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute || ! $attribute->get_visible() || $attribute->get_variation() ) {
			continue;
		}
		$label = wc_attribute_label( $attribute->get_name(), $product );
		if ( 'brand' === strtolower( $label ) && $brand ) {
			continue;
		}
		$values = $attribute->is_taxonomy()
			? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) )
			: $attribute->get_options();
		if ( $values ) {
			$rows[] = array( $label, implode( ', ', array_map( 'wp_strip_all_tags', $values ) ) );
		}
	}
	if ( $product->has_weight() ) {
		$rows[] = array( __( 'Weight', 'qr-minimal-store' ), wc_format_weight( $product->get_weight() ) );
	}
	if ( $product->has_dimensions() ) {
		$rows[] = array( __( 'Dimensions', 'qr-minimal-store' ), wc_format_dimensions( $product->get_dimensions( false ) ) );
	}
	if ( $product->get_sku() ) {
		$rows[] = array( __( 'SKU', 'qr-minimal-store' ), $product->get_sku() );
	}
	return $rows;
}

/**
 * "More in {department}": in-stock products from the same department.
 *
 * @return WC_Product[]
 */
function qr_minimal_store_more_in_department( WC_Product $product, $department, $limit = 3 ) {
	if ( ! $department instanceof WP_Term ) {
		return array();
	}
	$slugs = array( $department->slug );
	foreach ( get_term_children( $department->term_id, 'product_cat' ) as $child_id ) {
		$child = get_term( $child_id, 'product_cat' );
		if ( $child && ! is_wp_error( $child ) ) {
			$slugs[] = $child->slug;
		}
	}
	return wc_get_products(
		array(
			'limit'        => $limit,
			'status'       => 'publish',
			'visibility'   => 'catalog',
			'category'     => $slugs,
			'stock_status' => 'instock',
			'exclude'      => array( $product->get_id() ),
			'orderby'      => 'popularity',
		)
	);
}

function qr_minimal_store_product_sections() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$specs      = qr_minimal_store_product_specs( $product );
	$video      = apply_filters( 'qr_minimal_store_product_video_html', '', $product );
	$department = qr_minimal_store_product_department( $product );
	$more       = qr_minimal_store_more_in_department( $product, $department );
	$nav        = qr_minimal_store_section_nav_items( $product, $specs, $video );
	$rule       = qr_minimal_store_free_delivery_rule();
	?>
	<nav class="qr-pdp-nav" aria-label="<?php esc_attr_e( 'On this page', 'qr-minimal-store' ); ?>">
		<ul>
			<?php foreach ( $nav as $anchor => $label ) : ?>
				<li><a href="#<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</nav>

	<section id="qr-overview" class="qr-pdp-section qr-pdp-overview">
		<h2><?php esc_html_e( 'Overview', 'qr-minimal-store' ); ?></h2>
		<div class="qr-pdp-overview__content">
			<?php
			$content = $product->get_description();
			if ( '' === trim( $content ) ) {
				$content = $product->get_short_description();
			}
			echo wp_kses_post( wpautop( do_shortcode( $content ) ) );
			?>
		</div>
	</section>

	<?php if ( $video ) : ?>
		<section id="qr-video" class="qr-pdp-section qr-pdp-video">
			<h2><?php esc_html_e( 'See it in action', 'qr-minimal-store' ); ?></h2>
			<?php echo $video; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup from the video filter, escaped there. ?>
		</section>
	<?php endif; ?>

	<?php if ( $specs ) : ?>
		<details id="qr-specs" class="qr-pdp-section qr-pdp-panel" open>
			<summary><h2><?php esc_html_e( 'Specifications', 'qr-minimal-store' ); ?></h2></summary>
			<table class="qr-specs">
				<tbody>
					<?php foreach ( $specs as $row ) : ?>
						<tr><th scope="row"><?php echo esc_html( $row[0] ); ?></th><td><?php echo esc_html( $row[1] ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</details>
	<?php endif; ?>

	<details id="qr-delivery" class="qr-pdp-section qr-pdp-panel" open data-qr-mobile-closed>
		<summary><h2><?php esc_html_e( 'Delivery, returns and warranty', 'qr-minimal-store' ); ?></h2></summary>
		<div class="qr-policy-cards">
			<div class="qr-policy-card">
				<h3><?php esc_html_e( 'Delivery', 'qr-minimal-store' ); ?></h3>
				<p>
					<?php
					if ( $rule ) {
						echo esc_html(
							sprintf(
								/* translators: 1: delivery fee, 2: free-delivery threshold */
								__( '%1$s per order, free from %2$s. Usually 2–3 working days, nationwide.', 'qr-minimal-store' ),
								wp_strip_all_tags( wc_price( $rule['fee'], array( 'decimals' => 0 ) ) ),
								wp_strip_all_tags( wc_price( $rule['threshold'], array( 'decimals' => 0 ) ) )
							)
						);
					} else {
						esc_html_e( 'Usually 2–3 working days, nationwide.', 'qr-minimal-store' );
					}
					?>
				</p>
			</div>
			<div class="qr-policy-card">
				<h3><?php esc_html_e( 'Returns', 'qr-minimal-store' ); ?></h3>
				<p><?php esc_html_e( 'Changed your mind? Return unused items within 30 days of delivery. Faulty items are repaired, replaced or refunded.', 'qr-minimal-store' ); ?></p>
			</div>
			<div class="qr-policy-card">
				<h3><?php esc_html_e( 'Warranty', 'qr-minimal-store' ); ?></h3>
				<p><?php esc_html_e( 'Genuine product from an authorised South African distributor, with local warranty.', 'qr-minimal-store' ); ?></p>
			</div>
		</div>
	</details>

	<?php if ( comments_open( $product->get_id() ) ) : ?>
		<details id="qr-reviews" class="qr-pdp-section qr-pdp-panel" open data-qr-mobile-closed>
			<summary><h2><?php esc_html_e( 'Reviews', 'qr-minimal-store' ); ?></h2></summary>
			<?php if ( 0 === (int) $product->get_review_count() ) : ?>
				<div class="qr-reviews-empty">
					<div>
						<p class="qr-reviews-empty__title"><?php esc_html_e( 'No reviews yet', 'qr-minimal-store' ); ?></p>
						<p><?php esc_html_e( 'Bought this from us? Tell other shoppers how it works for you.', 'qr-minimal-store' ); ?></p>
					</div>
					<button type="button" class="qr-btn-outline" data-qr-write-review aria-controls="qr-review-form" aria-expanded="false"><?php esc_html_e( 'Write a review', 'qr-minimal-store' ); ?></button>
				</div>
				<div id="qr-review-form" class="qr-review-form is-empty" hidden>
					<?php comments_template(); ?>
				</div>
			<?php else : ?>
				<div class="qr-review-form">
					<?php comments_template(); ?>
				</div>
			<?php endif; ?>
		</details>
	<?php endif; ?>

	<?php if ( $more ) : ?>
		<section class="qr-pdp-section qr-pdp-more">
			<h2>
				<?php
				/* translators: %s: department name */
				echo esc_html( sprintf( __( 'More in %s', 'qr-minimal-store' ), $department->name ) );
				?>
			</h2>
			<ul class="qr-more-list">
				<?php foreach ( $more as $item ) : ?>
					<li>
						<a href="<?php echo esc_url( $item->get_permalink() ); ?>">
							<span class="qr-more-list__img"><?php echo $item->get_image( 'woocommerce_thumbnail', array( 'alt' => $item->get_name() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?></span>
							<span class="qr-more-list__body">
								<span class="qr-more-list__name"><?php echo esc_html( $item->get_name() ); ?></span>
								<span class="qr-more-list__price"><?php echo wp_kses_post( $item->get_price_html() ); ?></span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
	<?php
}

/**
 * Add-to-cart drawer (filled by product-page.js after a Store API add).
 */
add_action( 'wp_footer', 'qr_minimal_store_cart_drawer' );
function qr_minimal_store_cart_drawer() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	?>
	<div class="qr-drawer" id="qr-cart-drawer" hidden>
		<div class="qr-drawer__backdrop" data-qr-drawer-close></div>
		<aside class="qr-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="qr-drawer-title" tabindex="-1">
			<header class="qr-drawer__head">
				<h2 id="qr-drawer-title"><?php echo qr_minimal_store_check_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Added to cart', 'qr-minimal-store' ); ?></h2>
				<button type="button" class="qr-drawer__close" data-qr-drawer-close aria-label="<?php esc_attr_e( 'Close', 'qr-minimal-store' ); ?>">&times;</button>
			</header>
			<div class="qr-drawer__body" data-qr-drawer-body></div>
			<footer class="qr-drawer__foot">
				<p class="qr-drawer__total"><span><?php esc_html_e( 'Subtotal', 'qr-minimal-store' ); ?></span><strong data-qr-drawer-total></strong></p>
				<a class="qr-drawer__checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Checkout', 'qr-minimal-store' ); ?></a>
				<a class="qr-drawer__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'View cart', 'qr-minimal-store' ); ?></a>
				<button type="button" class="qr-drawer__continue" data-qr-drawer-close><?php esc_html_e( 'Continue shopping', 'qr-minimal-store' ); ?></button>
			</footer>
		</aside>
	</div>
	<?php
}

function qr_minimal_store_check_svg() {
	return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1f7a3a" stroke-width="2.2" aria-hidden="true" focusable="false"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
}

add_action( 'wp_enqueue_scripts', 'qr_minimal_store_product_page_assets', 25 );
function qr_minimal_store_product_page_assets() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$file = get_template_directory() . '/assets/js/product-page.js';
	wp_enqueue_script(
		'qr-minimal-store-product-page',
		get_template_directory_uri() . '/assets/js/product-page.js',
		array(),
		file_exists( $file ) ? (string) filemtime( $file ) : wp_get_theme()->get( 'Version' ),
		true
	);
	$rule = qr_minimal_store_free_delivery_rule();
	wp_localize_script(
		'qr-minimal-store-product-page',
		'qrProductPage',
		array(
			'storeApi'         => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
			'freeDelivery'     => $rule,
			'pricesIncludeTax' => wc_prices_include_tax(),
			'swatches'         => qr_minimal_store_colour_swatches(),
			'i18n'             => array(
				'qty'          => __( 'Qty %1$d · %2$s', 'qr-minimal-store' ),
				'unlocked'     => __( 'Free delivery unlocked.', 'qr-minimal-store' ),
				/* translators: %s: free-delivery threshold */
				'unlockedNote' => __( 'Your cart is %s or more.', 'qr-minimal-store' ),
				/* translators: %s: amount still needed */
				'addMore'      => __( 'Add %s more for free delivery.', 'qr-minimal-store' ),
				'goesWith'     => __( 'Goes with it', 'qr-minimal-store' ),
				'add'          => __( 'Add', 'qr-minimal-store' ),
				'added'        => __( 'Added', 'qr-minimal-store' ),
				'error'        => __( 'Could not add this to your cart. Please try again.', 'qr-minimal-store' ),
				'inStock'      => __( 'In stock', 'qr-minimal-store' ),
				/* translators: %s: option name, e.g. Black */
				'inStockIn'    => __( 'In stock in %s', 'qr-minimal-store' ),
				/* translators: %d: units left */
				'onlyLeft'     => __( 'only %d left', 'qr-minimal-store' ),
				'outOfStock'   => __( 'Out of stock', 'qr-minimal-store' ),
				'backorder'    => __( 'on backorder', 'qr-minimal-store' ),
				'fadedNote'    => __( 'Faded options are on backorder.', 'qr-minimal-store' ),
				'backorderNow' => __( 'Backorder now', 'qr-minimal-store' ),
			),
		)
	);
}

/**
 * Swatch colours by option name (lower case). Unknown names show as text chips.
 *
 * @return array<string, string>
 */
function qr_minimal_store_colour_swatches() {
	return apply_filters(
		'qr_minimal_store_colour_swatches',
		array(
			'black'      => '#1d1d1f',
			'white'      => '#ffffff',
			'grey'       => '#8e8e93',
			'gray'       => '#8e8e93',
			'light grey' => '#c7c7cc',
			'dark grey'  => '#48484a',
			'silver'     => '#c0c0c0',
			'red'        => '#d62828',
			'blue'       => '#1d4ed8',
			'light blue' => '#7cb7f0',
			'navy'       => '#1f2a5c',
			'green'      => '#2f9e44',
			'yellow'     => '#f4c430',
			'orange'     => '#f08c00',
			'pink'       => '#f783ac',
			'purple'     => '#7048e8',
			'cyan'       => '#15aabf',
			'magenta'    => '#c2255c',
			'gold'       => '#c9a227',
			'bronze'     => '#a0612a',
			'copper'     => '#b87333',
			'beige'      => '#e3d3b0',
			'brown'      => '#7a4b2a',
			'clear'      => '#eef3f7',
			'transparent' => '#eef3f7',
		)
	);
}

/**
 * Google rating badge (footer fallback and Google's floating merchant widget) stays off
 * until the store has a real rating. Turn on with
 * add_filter( 'qr_minimal_store_show_rating_badge', '__return_true' ).
 */
function qr_minimal_store_show_rating_badge() {
	return (bool) apply_filters( 'qr_minimal_store_show_rating_badge', false );
}

add_filter( 'qr_gcr_load_merchant_widget', 'qr_minimal_store_show_rating_badge' );
