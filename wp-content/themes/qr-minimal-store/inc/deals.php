<?php
/**
 * Deals page, /deals/: every published product whose sale price has an end date.
 *
 * Nothing is listed by hand. A product is a deal while WooCommerce has it on sale and its sale
 * end date (date_on_sale_to) is still ahead, so a deal drops off by itself when its price ends,
 * and the next promo only needs dated sale prices on its products. Prices, savings and end dates
 * are read live from the catalogue. A sale price without an end date is not a deal here: the page
 * may only say "Ends ..." because the price really ends then.
 *
 * - The "Deals" link (header, phone menu, footer) only shows while a deal can be bought.
 * - Headline: Customizer → Deals page; empty = "Deals".
 * - Cards use the bundle card styles (inc/bundles.php) with a "Save" badge and the end date.
 *
 * @package qr-minimal-store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Page: /deals/ ---------- */

/**
 * @return string
 */
function qr_minimal_store_deals_url() {
	return home_url( '/deals/' );
}

add_action( 'init', 'qr_minimal_store_deals_rewrite' );
function qr_minimal_store_deals_rewrite() {
	add_rewrite_rule( '^deals/?$', 'index.php?qr_deals=1', 'top' );
	if ( get_option( 'qr_minimal_store_deals_rewrite' ) !== '1' ) {
		flush_rewrite_rules( false );
		update_option( 'qr_minimal_store_deals_rewrite', '1', false );
	}
}

add_filter( 'query_vars', 'qr_minimal_store_deals_query_var' );
function qr_minimal_store_deals_query_var( $vars ) {
	$vars[] = 'qr_deals';
	return $vars;
}

/**
 * @return bool
 */
function qr_minimal_store_is_deals_page() {
	global $wp_query;
	return $wp_query instanceof WP_Query && (bool) $wp_query->get( 'qr_deals' );
}

/**
 * The page has no posts of its own: the main query loads nothing and is not the blog home.
 *
 * @param WP_Query $q Query.
 */
add_action( 'pre_get_posts', 'qr_minimal_store_deals_main_query' );
function qr_minimal_store_deals_main_query( $q ) {
	if ( is_admin() || ! $q instanceof WP_Query || ! $q->is_main_query() || ! $q->get( 'qr_deals' ) ) {
		return;
	}
	$q->is_home = false;
	$q->set( 'post__in', array( 0 ) );
	$q->set( 'no_found_rows', true );
}

add_filter( 'pre_handle_404', 'qr_minimal_store_deals_not_404', 10, 2 );
function qr_minimal_store_deals_not_404( $handled, $q ) {
	return ( $q instanceof WP_Query && $q->get( 'qr_deals' ) ) ? true : $handled;
}

add_filter( 'template_include', 'qr_minimal_store_deals_template' );
function qr_minimal_store_deals_template( $template ) {
	if ( ! qr_minimal_store_is_deals_page() || ! function_exists( 'wc_get_product' ) ) {
		return $template;
	}
	$file = get_template_directory() . '/template-parts/landing-deals.php';
	return file_exists( $file ) ? $file : $template;
}

add_filter( 'document_title_parts', 'qr_minimal_store_deals_document_title' );
function qr_minimal_store_deals_document_title( $parts ) {
	if ( qr_minimal_store_is_deals_page() ) {
		$parts['title'] = qr_minimal_store_has_deals() ? qr_minimal_store_deals_title() : __( 'Deals', 'qr-minimal-store' );
		unset( $parts['tagline'] );
	}
	return $parts;
}

add_filter( 'body_class', 'qr_minimal_store_deals_body_class' );
function qr_minimal_store_deals_body_class( $classes ) {
	if ( qr_minimal_store_is_deals_page() ) {
		$classes[] = 'qr-deals-page';
		$classes[] = 'woocommerce';
	}
	return $classes;
}

add_action( 'wp_head', 'qr_minimal_store_deals_canonical', 5 );
function qr_minimal_store_deals_canonical() {
	if ( qr_minimal_store_is_deals_page() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( qr_minimal_store_deals_url() ) );
	}
}

// An empty deals page and its group views are not pages for search engines.
add_filter( 'wp_robots', 'qr_minimal_store_deals_robots' );
function qr_minimal_store_deals_robots( $robots ) {
	if ( qr_minimal_store_is_deals_page() && ( ! qr_minimal_store_has_deals() || '' !== qr_minimal_store_deals_group() ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}

/**
 * LiteSpeed: never keep the page in the cache past the next price end (at most a day).
 */
add_action( 'template_redirect', 'qr_minimal_store_deals_cache_ttl' );
function qr_minimal_store_deals_cache_ttl() {
	if ( ! qr_minimal_store_is_deals_page() ) {
		return;
	}
	$ttl = DAY_IN_SECONDS;
	foreach ( qr_minimal_store_deals() as $deal ) {
		$ttl = min( $ttl, $deal['ends'] - time() );
	}
	do_action( 'litespeed_control_set_ttl', max( 60, $ttl ) );
}

/* ---------- Wording ---------- */

/**
 * Page headline: the Customizer text, else "Deals". The Singles' Day run (prices end 20 Nov 2026)
 * has its headline built in so it needs no setting, and it stops by itself.
 *
 * @return string
 */
function qr_minimal_store_deals_title() {
	$title = trim( (string) get_theme_mod( 'qr_deals_title', '' ) );
	if ( '' !== $title ) {
		return $title;
	}
	return current_datetime()->format( 'Y-m-d' ) <= '2026-11-20' ? __( "Singles' Day deals", 'qr-minimal-store' ) : __( 'Deals', 'qr-minimal-store' );
}

/**
 * Line under the headline. Names the date only when every deal ends on that day.
 *
 * @param array<int, array> $deals Deals.
 * @return string
 */
function qr_minimal_store_deals_lead( $deals ) {
	$days = array_unique( array_map( static fn( $deal ) => wp_date( 'Y-m-d', qr_minimal_store_deal_last_day( $deal ) ), $deals ) );
	if ( 1 === count( $days ) ) {
		/* translators: %s: date, e.g. 20 November */
		return sprintf( __( 'Prices end %s or while stock lasts.', 'qr-minimal-store' ), wp_date( 'j F', qr_minimal_store_deal_last_day( $deals[0] ) ) );
	}
	return __( 'Each price ends on the date shown, or while stock lasts.', 'qr-minimal-store' );
}

add_action( 'customize_register', 'qr_minimal_store_deals_customizer', 21 );
function qr_minimal_store_deals_customizer( $wp_customize ) {
	$wp_customize->add_section(
		'qr_deals_section',
		array(
			'title'       => __( 'Deals page', 'qr-minimal-store' ),
			'priority'    => 30,
			'description' => __( 'The page at /deals/ lists every product with a sale price that has an end date. It needs no upkeep: a product leaves the page when its sale ends.', 'qr-minimal-store' ),
		)
	);
	$wp_customize->add_setting(
		'qr_deals_title',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'qr_deals_title',
		array(
			'label'   => __( 'Headline (empty = Deals)', 'qr-minimal-store' ),
			'section' => 'qr_deals_section',
			'type'    => 'text',
		)
	);
}

/* ---------- Deals ---------- */

/**
 * IDs of published products that have (or whose variations have) a sale end date ahead.
 * Cached for ten minutes and until a product changes; every deal is checked live after this.
 *
 * @return int[]
 */
function qr_minimal_store_deal_ids() {
	$ids = get_transient( 'qr_minimal_store_deal_ids' );
	if ( is_array( $ids ) ) {
		return $ids;
	}
	$query = new WP_Query(
		array(
			'post_type'           => array( 'product', 'product_variation' ),
			'post_status'         => 'publish',
			'posts_per_page'      => 200,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'orderby'             => 'ID',
			'order'               => 'ASC',
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_sale_price_dates_to',
					'value'   => time(),
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	);
	$ids   = array();
	foreach ( $query->posts as $id ) {
		$ids[] = 'product_variation' === get_post_type( $id ) ? (int) wp_get_post_parent_id( $id ) : (int) $id;
	}
	$ids = array_values( array_unique( array_filter( $ids ) ) );
	set_transient( 'qr_minimal_store_deal_ids', $ids, 10 * MINUTE_IN_SECONDS );
	return $ids;
}

foreach ( array( 'save_post_product', 'woocommerce_new_product', 'woocommerce_update_product', 'woocommerce_new_product_variation', 'woocommerce_update_product_variation', 'woocommerce_product_set_stock_status', 'woocommerce_variation_set_stock_status' ) as $qr_minimal_store_deals_hook ) {
	add_action( $qr_minimal_store_deals_hook, 'qr_minimal_store_deals_flush' );
}
unset( $qr_minimal_store_deals_hook );
function qr_minimal_store_deals_flush() {
	delete_transient( 'qr_minimal_store_deal_ids' );
}

// WooCommerce's daily job has just started or ended sale prices: every cached page may be out of date.
add_action( 'wc_after_products_starting_sales', 'qr_minimal_store_deals_sales_changed' );
add_action( 'wc_after_products_ending_sales', 'qr_minimal_store_deals_sales_changed' );
function qr_minimal_store_deals_sales_changed() {
	qr_minimal_store_deals_flush();
	do_action( 'litespeed_purge_all' );
}

/**
 * Live numbers of one deal, or null when the product is not one now. A variable product counts
 * through its variations: the card shows the lowest dated sale price among them.
 *
 * @param WC_Product|false|null $product Product.
 * @return array|null product, price, regular, save, ends (timestamp), from (bool: price is a
 *                    "from" price), in_stock, free, group, group_title.
 */
function qr_minimal_store_deal_data( $product ) {
	if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
		return null;
	}
	$now   = time();
	$items = array( $product );
	if ( $product->is_type( 'variable' ) ) {
		$items = array_filter( array_map( 'wc_get_product', $product->get_visible_children() ) );
	}
	$best = null;
	foreach ( $items as $item ) {
		$to = $item->get_date_on_sale_to();
		if ( ! $to || $to->getTimestamp() <= $now || ! $item->is_on_sale() ) {
			continue;
		}
		$price   = (float) wc_get_price_to_display( $item );
		$regular = (float) wc_get_price_to_display( $item, array( 'price' => $item->get_regular_price() ) );
		if ( $price <= 0 || $regular <= $price ) {
			continue;
		}
		if ( ! $best || $price < $best['price'] ) {
			$best = array(
				'price'   => $price,
				'regular' => $regular,
				'ends'    => $to->getTimestamp(),
			);
		}
	}
	if ( ! $best ) {
		return null;
	}
	$from = false;
	if ( $product->is_type( 'variable' ) ) {
		$min = (float) $product->get_variation_price( 'min', true );
		$max = (float) $product->get_variation_price( 'max', true );
		// A cheaper variation that is not on sale: the product page shows it, the card stays with the deal.
		$from = $max > $min && abs( $min - $best['price'] ) < 0.005;
	}
	$rule       = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
	$department = function_exists( 'qr_minimal_store_product_department_info' ) ? qr_minimal_store_product_department_info( $product ) : null;
	return array(
		'product'     => $product,
		'price'       => $best['price'],
		'regular'     => $best['regular'],
		'save'        => $best['regular'] - $best['price'],
		'ends'        => $best['ends'],
		'from'        => $from,
		'in_stock'    => $product->is_purchasable() && $product->is_in_stock(),
		'free'        => $rule && $best['price'] >= $rule['threshold'],
		'group'       => $department ? $department['key'] : '',
		'group_title' => $department ? $department['title'] : '',
	);
}

/**
 * Last moment a deal's price can be bought, for the date shown. A sale that ends at 23:59:59 on
 * the 20th and one that ends at 00:00:00 on the 21st both read "20 Nov".
 *
 * @param array $deal Deal.
 * @return int Timestamp.
 */
function qr_minimal_store_deal_last_day( $deal ) {
	return $deal['ends'] - 1;
}

/**
 * Every deal running now: in stock first, then the price that ends soonest, then the biggest saving.
 *
 * @return array<int, array>
 */
function qr_minimal_store_deals() {
	static $deals = null;
	if ( null !== $deals ) {
		return $deals;
	}
	$deals = array();
	if ( ! function_exists( 'wc_get_product' ) ) {
		return $deals;
	}
	foreach ( qr_minimal_store_deal_ids() as $id ) {
		$deal = qr_minimal_store_deal_data( wc_get_product( $id ) );
		if ( $deal ) {
			$deals[] = $deal;
		}
	}
	usort(
		$deals,
		static function ( $a, $b ) {
			return array( (int) $b['in_stock'], $a['ends'], $b['save'], $a['product']->get_name() ) <=> array( (int) $a['in_stock'], $b['ends'], $a['save'], $b['product']->get_name() );
		}
	);
	return $deals;
}

/**
 * Is there a deal to buy now? Decides the menu link and the empty page. When the answer changes
 * (a sale price was added or removed by hand), the page cache is cleared so the link follows.
 *
 * @return bool
 */
function qr_minimal_store_has_deals() {
	static $has = null;
	if ( null !== $has ) {
		return $has;
	}
	$has   = (bool) array_filter( qr_minimal_store_deals(), static fn( $deal ) => $deal['in_stock'] );
	$state = $has ? '1' : '0';
	if ( get_option( 'qr_minimal_store_deals_nav', '' ) !== $state ) {
		update_option( 'qr_minimal_store_deals_nav', $state, true );
		do_action( 'litespeed_purge_all' );
	}
	return $has;
}

/**
 * Groups of the running deals (their departments), for the chips: key => [title, count].
 *
 * @param array<int, array> $deals Deals.
 * @return array<string, array{0: string, 1: int}>
 */
function qr_minimal_store_deal_groups( $deals ) {
	$groups = array();
	foreach ( $deals as $deal ) {
		if ( '' === $deal['group'] ) {
			continue;
		}
		if ( ! isset( $groups[ $deal['group'] ] ) ) {
			$groups[ $deal['group'] ] = array( $deal['group_title'], 0 );
		}
		++$groups[ $deal['group'] ][1];
	}
	return $groups;
}

/**
 * Group picked in the URL (/deals/?group=gaming), or '' for all deals.
 *
 * @return string
 */
function qr_minimal_store_deals_group() {
	$group = isset( $_GET['group'] ) ? sanitize_key( wp_unslash( $_GET['group'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only listing filter.
	return ( '' !== $group && isset( qr_minimal_store_deal_groups( qr_minimal_store_deals() )[ $group ] ) ) ? $group : '';
}

/* ---------- Markup ---------- */

/**
 * Deal card: Save badge, image, name, sale price with the regular price crossed out, the end
 * date, stock and delivery, Add to cart.
 *
 * @param array $deal Deal.
 */
function qr_minimal_store_deal_card( $deal ) {
	$product = $deal['product'];
	$url     = $product->get_permalink();
	$name    = html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' );
	$rule    = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
	$price   = wc_price( $deal['price'] );
	?>
	<li class="qr-bundle qr-deal<?php echo $deal['in_stock'] ? '' : ' is-sold-out'; ?> product">
		<span class="qr-save">
			<?php
			/* translators: %s: amount saved */
			// Rounded down: the badge never shows more than the real saving.
			echo esc_html( sprintf( __( 'Save %s', 'qr-minimal-store' ), qr_minimal_store_money_whole( floor( $deal['save'] ) ) ) );
			?>
		</span>
		<a class="qr-gtile qr-deal__img" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo qr_minimal_store_bundle_image( $product, $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?>
		</a>
		<h2 class="qr-deal__name"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a></h2>
		<div class="qr-bundle__pricing">
			<p class="qr-bundle__line">
				<span class="qr-bundle__price">
					<?php
					/* translators: %s: price */
					echo wp_kses_post( $deal['from'] ? sprintf( __( 'From %s', 'qr-minimal-store' ), $price ) : $price );
					?>
				</span>
				<span class="qr-was"><span class="screen-reader-text"><?php esc_html_e( 'Was', 'qr-minimal-store' ); ?></span> <s><?php echo wp_kses_post( wc_price( $deal['regular'] ) ); ?></s></span>
			</p>
			<p class="qr-deal__ends">
				<?php
				/* translators: %s: date, e.g. 20 Nov */
				echo esc_html( sprintf( __( 'Ends %s', 'qr-minimal-store' ), wp_date( 'j M', qr_minimal_store_deal_last_day( $deal ) ) ) );
				?>
			</p>
			<p class="qr-bundle__meta">
				<?php if ( $deal['in_stock'] ) : ?>
					<span class="qr-gstock"><?php esc_html_e( 'In stock', 'qr-minimal-store' ); ?></span>
					<?php if ( $deal['free'] ) : ?>
						<span class="qr-gfree"><?php esc_html_e( 'Free delivery', 'qr-minimal-store' ); ?></span>
					<?php elseif ( $rule ) : ?>
						<span class="qr-gsmall">
							<?php
							/* translators: %s: delivery fee */
							echo esc_html( sprintf( __( '%s delivery', 'qr-minimal-store' ), qr_minimal_store_money_whole( $rule['fee'] ) ) );
							?>
						</span>
					<?php endif; ?>
				<?php else : ?>
					<span class="qr-gstock is-out"><?php esc_html_e( 'Out of stock', 'qr-minimal-store' ); ?></span>
				<?php endif; ?>
			</p>
			<?php if ( ! $deal['in_stock'] ) : ?>
				<a class="qr-btn qr-btn--outline qr-bundle__add" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'View product', 'qr-minimal-store' ); ?><span class="screen-reader-text">: <?php echo esc_html( $name ); ?></span></a>
			<?php elseif ( $product->is_type( 'simple' ) && $product->supports( 'ajax_add_to_cart' ) ) : ?>
				<a class="qr-btn qr-bundle__add add_to_cart_button ajax_add_to_cart" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-product_id="<?php echo esc_attr( (string) $product->get_id() ); ?>" data-quantity="1" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" rel="nofollow"><?php esc_html_e( 'Add to cart', 'qr-minimal-store' ); ?><span class="screen-reader-text">: <?php echo esc_html( $name ); ?></span></a>
			<?php else : ?>
				<a class="qr-btn qr-bundle__add" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Choose options', 'qr-minimal-store' ); ?><span class="screen-reader-text">: <?php echo esc_html( $name ); ?></span></a>
			<?php endif; ?>
		</div>
	</li>
	<?php
}
