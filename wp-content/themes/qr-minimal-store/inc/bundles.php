<?php
/**
 * Bundles, shared by every department (gaming, Bundle Deals, home departments).
 *
 * A bundle is a simple product with the meta _bundle_items = its component supplier codes joined
 * with "+". store_sync keeps its stock (the lowest component stock) and creates it. Every price,
 * total and saving here is worked out from live store prices; nothing is hard-coded.
 *
 * Where bundles show:
 * - /gaming/ (template-parts/landing-gaming.php, gaming-bundles only, via inc/gaming.php).
 * - Bundle Deals lists every bundle: its own category plus Gaming Bundles. Gaming Bundles and
 *   /gaming/ stay limited to gaming-bundles. Cards replace the product cards on both pages.
 * - Other category and department pages: a "Bundle deals" strip of the bundles listed there.
 * - A component's product page: the nudge towards the cheapest in-stock bundle with a saving.
 * - The bundle's own product page: What's in the box with live single prices and the saving.
 *
 * @package qr-minimal-store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Category slugs whose listing is all bundles (cards replace the product grid).
 *
 * @return string[]
 */
function qr_minimal_store_bundle_categories() {
	return array( 'bundle-deals', 'gaming-bundles' );
}

/**
 * Categories on the Bundle Deals page. Gaming bundles stay in gaming-bundles; this query shows
 * them here as well. Their categories, prices and other product data are not changed.
 *
 * @return string[]
 */
function qr_minimal_store_bundle_deals_query_slugs() {
	$slugs = array( 'bundle-deals' );
	if ( term_exists( 'gaming-bundles', 'product_cat' ) ) {
		$slugs[] = 'gaming-bundles';
	}
	return $slugs;
}

/**
 * Bundle Deals archive includes Gaming Bundles. The query var is put back afterwards so the
 * page, its filters and the current-department marker stay on the Bundle Deals category.
 *
 * @param WP_Query $q Query.
 */
add_action( 'pre_get_posts', 'qr_minimal_store_bundle_deals_query', 20 );
function qr_minimal_store_bundle_deals_query( $q ) {
	if ( is_admin() || ! $q instanceof WP_Query || ! $q->is_main_query() || ! $q->is_tax( 'product_cat', 'bundle-deals' ) ) {
		return;
	}
	$slugs = qr_minimal_store_bundle_deals_query_slugs();
	if ( count( $slugs ) < 2 ) {
		return;
	}
	$q->set( 'product_cat', implode( ',', $slugs ) );
	$q->set( 'qr_bundle_deals_expanded', true );
}

/**
 * Put the Bundle Deals query var back after the posts are loaded.
 *
 * @param WP_Post[] $posts Posts.
 * @param WP_Query  $q     Query.
 * @return WP_Post[]
 */
add_filter( 'the_posts', 'qr_minimal_store_bundle_deals_restore_query_var', 1, 2 );
function qr_minimal_store_bundle_deals_restore_query_var( $posts, $q ) {
	if ( $q instanceof WP_Query && $q->get( 'qr_bundle_deals_expanded' ) ) {
		$q->set( 'product_cat', 'bundle-deals' );
		$q->set( 'qr_bundle_deals_expanded', false );
	}
	return $posts;
}

/**
 * Create the Bundle Deals category once (wp-admin, shop managers), so the drafts never land
 * uncategorised. An existing category with this slug is left as it is.
 */
add_action( 'admin_init', 'qr_minimal_store_ensure_bundle_category' );
function qr_minimal_store_ensure_bundle_category() {
	if ( '1' === get_option( 'qr_minimal_store_bundle_category' ) || ! current_user_can( 'manage_product_terms' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	if ( ! term_exists( 'bundle-deals', 'product_cat' ) ) {
		$result = wp_insert_term(
			__( 'Bundle Deals', 'qr-minimal-store' ),
			'product_cat',
			array(
				'slug'        => 'bundle-deals',
				'description' => __( 'Items that go together, packed as one order for less than buying them one by one.', 'qr-minimal-store' ),
			)
		);
		if ( is_wp_error( $result ) ) {
			return;
		}
	}
	update_option( 'qr_minimal_store_bundle_category', '1', false );
}

/* ---------- Components ---------- */

/**
 * Published single product for a component supplier code: SKU "SYN-<code>" (how store_sync creates
 * them), else SKU "<code>", else a product whose supplier_code meta has the code as one of its "|"
 * parts (the Xiaomi singles have their own SKUs such as QR-XIAOMISMARTBAND11 and keep colour
 * alternatives as "A|B|C"). Bundles never count as a component. The code => product ID map is
 * cached until a product is saved (qr_minimal_store_cache_gen); prices are always read live.
 *
 * @param string $code Supplier code.
 * @return WC_Product|null
 */
function qr_minimal_store_bundle_component( $code ) {
	static $found = array();
	$code = strtoupper( trim( (string) $code ) );
	if ( '' === $code || ! function_exists( 'wc_get_product' ) ) {
		return null;
	}
	if ( array_key_exists( $code, $found ) ) {
		return $found[ $code ];
	}
	$key = 'qr_bundle_part_' . md5( $code . '|' . (int) get_option( 'qr_minimal_store_cache_gen', 0 ) );
	$id  = get_transient( $key );
	if ( false !== $id ) {
		$product = $id ? wc_get_product( (int) $id ) : null;
		if ( ! $id || ( $product && 'publish' === $product->get_status() ) ) {
			$found[ $code ] = $product ? $product : null;
			return $found[ $code ];
		}
	}
	$product = qr_minimal_store_bundle_find_component( $code );
	set_transient( $key, $product ? $product->get_id() : 0, 12 * HOUR_IN_SECONDS );
	$found[ $code ] = $product;
	return $product;
}

/**
 * Uncached lookup for qr_minimal_store_bundle_component().
 *
 * @param string $code Upper-case supplier code.
 * @return WC_Product|null
 */
function qr_minimal_store_bundle_find_component( $code ) {
	foreach ( array( 'SYN-' . $code, $code ) as $sku ) {
		$id = wc_get_product_id_by_sku( $sku );
		if ( $id ) {
			$product = wc_get_product( $id );
			if ( $product && 'publish' === $product->get_status() && '' === (string) $product->get_meta( '_bundle_items' ) ) {
				return $product;
			}
		}
	}
	$query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => 20,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'orderby'             => 'ID',
			'order'               => 'ASC',
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'supplier_code',
					'value'   => $code,
					'compare' => 'LIKE',
				),
			),
		)
	);
	foreach ( $query->posts as $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || '' !== (string) $product->get_meta( '_bundle_items' ) ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', strtoupper( (string) $product->get_meta( 'supplier_code' ) ) ) );
		if ( in_array( $code, $parts, true ) ) {
			return $product;
		}
	}
	return null;
}

/**
 * Short card name of a product (departments add theirs through the
 * qr_minimal_store_bundle_short_names filter, keyed by supplier code), else its store name.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function qr_minimal_store_bundle_short_name( $product ) {
	static $names = null;
	if ( null === $names ) {
		$names = (array) apply_filters( 'qr_minimal_store_bundle_short_names', array() );
	}
	$code = strtoupper( trim( (string) $product->get_meta( 'supplier_code' ) ) );
	return isset( $names[ $code ] ) ? $names[ $code ] : html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' );
}

/* ---------- Bundle data ---------- */

/**
 * Live numbers of one bundle. Null when it is not a bundle, or when a component is not on sale on
 * its own: there is no honest "bought separately" total then. A component listed twice (His &
 * Hers sets whose two colours share one single listing) shows once with a quantity.
 *
 * @param WC_Product $product Bundle product.
 * @param array      $defs    Optional card wording keyed by bundle code (title, anchor, inc, group, tag, for, hot).
 * @return array|null product, code, title, anchor, inc, group, tag, for, hot, items (product, name,
 *                    qty, price = line total), count, price, total, save, in_stock, free.
 */
function qr_minimal_store_bundle_data( $product, $defs = array() ) {
	if ( ! $product instanceof WC_Product ) {
		return null;
	}
	$code  = strtoupper( trim( (string) $product->get_meta( '_bundle_items' ) ) );
	$codes = array_values( array_filter( array_map( 'trim', explode( '+', $code ) ) ) );
	if ( count( $codes ) < 2 ) {
		return null;
	}
	$items = array();
	$total = 0.0;
	foreach ( $codes as $part ) {
		$component = qr_minimal_store_bundle_component( $part );
		if ( ! $component ) {
			return null;
		}
		$price  = (float) wc_get_price_to_display( $component );
		$total += $price;
		$id     = $component->get_id();
		if ( isset( $items[ $id ] ) ) {
			++$items[ $id ]['qty'];
			$items[ $id ]['price'] += $price;
			continue;
		}
		$items[ $id ] = array(
			'product' => $component,
			'name'    => qr_minimal_store_bundle_short_name( $component ),
			'qty'     => 1,
			'price'   => $price,
		);
	}
	$items = array_values( $items );
	$rule  = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
	$def   = isset( $defs[ $code ] ) ? $defs[ $code ] : array();
	$title = isset( $def['title'] ) ? $def['title'] : preg_replace( '/\s+Bundle$/i', '', html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' ) );
	$tag   = trim( (string) $product->get_meta( '_qr_card_label' ) );
	$for   = trim( (string) $product->get_meta( '_qr_tagline' ) );
	$price = (float) wc_get_price_to_display( $product );
	$names = array_map( static fn( $item ) => $item['qty'] > 1 ? $item['name'] . ' × ' . $item['qty'] : $item['name'], $items );
	return array(
		'product'  => $product,
		'code'     => $code,
		'title'    => $title,
		'anchor'   => isset( $def['anchor'] ) ? $def['anchor'] : 'bundle-' . sanitize_title( $title ),
		'inc'      => isset( $def['inc'] ) ? $def['inc'] : implode( ' + ', $names ),
		'group'    => isset( $def['group'] ) ? $def['group'] : '',
		'tag'      => '' !== $tag ? $tag : ( isset( $def['tag'] ) ? $def['tag'] : '' ),
		'for'      => '' !== $for ? $for : ( isset( $def['for'] ) ? $def['for'] : wp_strip_all_tags( $product->get_short_description() ) ),
		'hot'      => ! empty( $def['hot'] ),
		'items'    => $items,
		'count'    => count( $codes ),
		'price'    => $price,
		'total'    => $total,
		'save'     => max( 0.0, $total - $price ),
		'in_stock' => $product->is_purchasable() && $product->is_in_stock(),
		'free'     => $rule && $price >= $rule['threshold'],
	);
}

/**
 * Published bundles in the given categories (and their children; empty = every bundle), with
 * their live numbers: bundles named in $defs first in that order, the rest by price.
 *
 * @param string[] $categories Category slugs.
 * @param array    $defs       Card wording keyed by bundle code.
 * @return array<int, array>
 */
function qr_minimal_store_bundles( $categories = array(), $defs = array() ) {
	static $cache = array();
	$categories = array_values( array_unique( array_filter( array_map( 'sanitize_title', (array) $categories ) ) ) );
	sort( $categories );
	$key = md5( wp_json_encode( array( $categories, array_keys( $defs ) ) ) );
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$cache[ $key ] = array();
	if ( ! function_exists( 'wc_get_product' ) ) {
		return $cache[ $key ];
	}
	$tax_query = array(
		'relation' => 'AND',
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		),
	);
	if ( $categories ) {
		$tax_query[] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'slug',
			'terms'            => $categories,
			'include_children' => true,
		);
	}
	$query   = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => 48,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'meta_key'            => '_bundle_items', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);
	$bundles = array();
	foreach ( $query->posts as $id ) {
		$bundle = qr_minimal_store_bundle_data( wc_get_product( $id ), $defs );
		if ( $bundle ) {
			$bundles[] = $bundle;
		}
	}
	$order = array_flip( array_keys( $defs ) );
	usort(
		$bundles,
		static function ( $a, $b ) use ( $order ) {
			$a_rank = isset( $order[ $a['code'] ] ) ? $order[ $a['code'] ] : PHP_INT_MAX;
			$b_rank = isset( $order[ $b['code'] ] ) ? $order[ $b['code'] ] : PHP_INT_MAX;
			return $a_rank === $b_rank ? $a['price'] <=> $b['price'] : $a_rank <=> $b_rank;
		}
	);
	$cache[ $key ] = $bundles;
	return $bundles;
}

/**
 * Bundles that can be bought now.
 *
 * @param array<int, array> $bundles Bundles.
 * @return array<int, array>
 */
function qr_minimal_store_bundles_buyable( $bundles ) {
	return array_values( array_filter( $bundles, static fn( $bundle ) => $bundle['in_stock'] ) );
}

/**
 * The bundle to nudge a single item towards: of the bundles in stock with a real saving that have
 * the item in it, the one with the most units of the item (a His & Hers pair of the watch being
 * viewed beats a mixed bundle), then the cheapest. A component can be a colour variation (store_sync
 * gives variations the SKU "SYN-<code>"); it counts as its parent, the product page being viewed.
 *
 * @param WC_Product        $product Single item.
 * @param array<int, array> $bundles Bundles to pick from.
 * @return array|null
 */
function qr_minimal_store_bundle_nudge( $product, $bundles ) {
	$best       = null;
	$best_units = 0;
	$id         = $product->get_id();
	if ( ! $id ) {   // An unsaved product would match every simple component (parent id 0).
		return null;
	}
	foreach ( qr_minimal_store_bundles_buyable( $bundles ) as $bundle ) {
		if ( $bundle['save'] <= 0 ) {
			continue;
		}
		$units = 0;
		foreach ( $bundle['items'] as $item ) {
			if ( $item['product']->get_id() === $id || $item['product']->get_parent_id() === $id ) {
				$units += $item['qty'];
			}
		}
		if ( $units > $best_units || ( $units && $units === $best_units && $bundle['price'] < $best['price'] ) ) {
			$best       = $bundle;
			$best_units = $units;
		}
	}
	return $best;
}

/* ---------- Markup ---------- */

/**
 * Product image with a real alt text.
 *
 * @param WC_Product $product Product.
 * @param string     $alt     Alt text.
 * @param string     $size    Image size.
 * @param string     $loading lazy or eager.
 * @return string
 */
function qr_minimal_store_bundle_image( $product, $alt, $size = 'woocommerce_thumbnail', $loading = 'lazy' ) {
	return $product->get_image(
		$size,
		array(
			'alt'     => $alt,
			'loading' => $loading,
		)
	);
}

/**
 * Collage of a bundle's component images.
 *
 * @param array  $bundle Bundle.
 * @param string $class  Extra class.
 */
function qr_minimal_store_bundle_collage( $bundle, $class = '' ) {
	$items = array_slice( $bundle['items'], 0, 4 );
	?>
	<div class="qr-collage qr-collage--<?php echo esc_attr( (string) count( $items ) ); ?> <?php echo esc_attr( $class ); ?>">
		<?php foreach ( $items as $index => $item ) : ?>
			<span class="qr-gtile"><?php echo qr_minimal_store_bundle_image( $item['product'], $item['name'], 0 === $index ? 'woocommerce_single' : 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?></span>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Item name with its quantity when the bundle has more than one.
 *
 * @param array $item Bundle item.
 * @return string
 */
function qr_minimal_store_bundle_item_name( $item ) {
	/* translators: 1: item name, 2: quantity */
	return $item['qty'] > 1 ? sprintf( __( '%1$s × %2$d', 'qr-minimal-store' ), $item['name'], $item['qty'] ) : $item['name'];
}

/**
 * Bundle card (design Gaming.html). The Save badge and the crossed-out total only show while the
 * bundle really costs less than its parts.
 *
 * @param array $bundle Bundle.
 */
function qr_minimal_store_bundle_card( $bundle ) {
	$product = $bundle['product'];
	$url     = $product->get_permalink();
	?>
	<article id="<?php echo esc_attr( $bundle['anchor'] ); ?>" class="qr-bundle<?php echo $bundle['hot'] ? ' is-hot' : ''; ?><?php echo $bundle['in_stock'] ? '' : ' is-sold-out'; ?> product">
		<?php if ( '' !== $bundle['tag'] ) : ?>
			<span class="qr-bundle__tag"><?php echo esc_html( $bundle['tag'] ); ?></span>
		<?php endif; ?>
		<?php if ( $bundle['save'] > 0 ) : ?>
			<span class="qr-save">
				<?php
				/* translators: %s: amount saved */
				echo esc_html( sprintf( __( 'Save %s', 'qr-minimal-store' ), qr_minimal_store_money_whole( $bundle['save'] ) ) );
				?>
			</span>
		<?php endif; ?>
		<a class="qr-bundle__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php qr_minimal_store_bundle_collage( $bundle ); ?>
		</a>
		<h3 class="qr-bundle__name"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $bundle['title'] ); ?></a></h3>
		<?php if ( '' !== $bundle['for'] ) : ?>
			<p class="qr-bundle__for"><?php echo esc_html( $bundle['for'] ); ?></p>
		<?php endif; ?>
		<p class="screen-reader-text"><?php esc_html_e( "What's in the box", 'qr-minimal-store' ); ?></p>
		<ul class="qr-bundle__box">
			<?php foreach ( $bundle['items'] as $item ) : ?>
				<li>
					<span><?php echo esc_html( qr_minimal_store_bundle_item_name( $item ) ); ?></span>
					<span><?php echo wp_kses_post( wc_price( $item['price'] ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="qr-bundle__pricing">
			<p class="qr-bundle__line">
				<span class="qr-bundle__price"><?php echo wp_kses_post( wc_price( $bundle['price'] ) ); ?></span>
				<?php if ( $bundle['save'] > 0 ) : ?>
					<span class="qr-was"><s><?php echo wp_kses_post( wc_price( $bundle['total'] ) ); ?></s> <?php esc_html_e( 'bought separately', 'qr-minimal-store' ); ?></span>
				<?php endif; ?>
			</p>
			<p class="qr-bundle__meta">
				<?php if ( $bundle['in_stock'] ) : ?>
					<span class="qr-gstock"><?php esc_html_e( 'In stock', 'qr-minimal-store' ); ?></span>
					<?php if ( $bundle['free'] ) : ?>
						<span class="qr-gfree"><?php esc_html_e( 'Free delivery', 'qr-minimal-store' ); ?></span>
					<?php endif; ?>
					<span class="qr-gsmall"><?php esc_html_e( 'or pay monthly, subject to approval', 'qr-minimal-store' ); ?></span>
				<?php else : ?>
					<span class="qr-gstock is-out"><?php esc_html_e( 'Out of stock', 'qr-minimal-store' ); ?></span>
				<?php endif; ?>
			</p>
			<?php if ( $bundle['in_stock'] && $product->supports( 'ajax_add_to_cart' ) ) : ?>
				<a class="qr-btn qr-bundle__add add_to_cart_button ajax_add_to_cart" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-product_id="<?php echo esc_attr( (string) $product->get_id() ); ?>" data-quantity="1" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" rel="nofollow"><?php esc_html_e( 'Add bundle to cart', 'qr-minimal-store' ); ?><span class="screen-reader-text">: <?php echo esc_html( $bundle['title'] ); ?></span></a>
			<?php else : ?>
				<a class="qr-btn qr-btn--outline qr-bundle__add" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'View bundle', 'qr-minimal-store' ); ?><span class="screen-reader-text">: <?php echo esc_html( $bundle['title'] ); ?></span></a>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * Nudge paragraph towards a bundle.
 *
 * @param array  $bundle Bundle.
 * @param string $href   Link to the bundle.
 * @param string $class  Extra class.
 */
function qr_minimal_store_bundle_nudge_html( $bundle, $href, $class = '' ) {
	$more = max( 1, $bundle['count'] - 1 );
	?>
	<p class="qr-nudge <?php echo esc_attr( $class ); ?>">
		<?php
		echo wp_kses(
			sprintf(
				/* translators: 1: bundle name, 2: number of other items in the bundle, 3: amount saved */
				_n(
					'Part of the <b>%1$s</b> bundle: get it with %2$d more item and <b>save %3$s</b>.',
					'Part of the <b>%1$s</b> bundle: get it with %2$d more items and <b>save %3$s</b>.',
					$more,
					'qr-minimal-store'
				),
				esc_html( $bundle['title'] ),
				$more,
				esc_html( qr_minimal_store_money_whole( $bundle['save'] ) )
			),
			array( 'b' => array() )
		);
		?>
		<a href="<?php echo esc_url( $href ); ?>"><?php esc_html_e( 'See bundle', 'qr-minimal-store' ); ?><span class="screen-reader-text">: <?php echo esc_html( $bundle['title'] ); ?></span></a>
	</p>
	<?php
}

/* ---------- Product pages ---------- */

add_action( 'wp', 'qr_minimal_store_bundle_product_hooks' );
function qr_minimal_store_bundle_product_hooks() {
	if ( function_exists( 'is_product' ) && is_product() ) {
		// After the delivery line (17), before add to cart (30).
		add_action( 'woocommerce_single_product_summary', 'qr_minimal_store_bundle_buybox', 18 );
	}
}

/**
 * Buy box: What's in the box on a bundle, the bundle nudge on one of its items.
 */
function qr_minimal_store_bundle_buybox() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	if ( '' !== (string) $product->get_meta( '_bundle_items' ) ) {
		$bundle = qr_minimal_store_bundle_data( $product );
		if ( $bundle ) {
			qr_minimal_store_bundle_box( $bundle );
		}
		return;
	}
	$bundle = qr_minimal_store_bundle_nudge( $product, qr_minimal_store_bundles() );
	if ( $bundle ) {
		qr_minimal_store_bundle_nudge_html( $bundle, $bundle['product']->get_permalink(), 'qr-pnudge' );
	}
}

/**
 * What's in the box on the bundle's own page: each item with its live single price, the total
 * bought separately and the saving.
 *
 * @param array $bundle Bundle.
 */
function qr_minimal_store_bundle_box( $bundle ) {
	?>
	<div class="qr-bundlebox">
		<h2 class="qr-bundlebox__title"><?php esc_html_e( "What's in the box", 'qr-minimal-store' ); ?></h2>
		<ul class="qr-bundle__box">
			<?php foreach ( $bundle['items'] as $item ) : ?>
				<li>
					<span><a href="<?php echo esc_url( $item['product']->get_permalink() ); ?>"><?php echo esc_html( qr_minimal_store_bundle_item_name( $item ) ); ?></a></span>
					<span><?php echo wp_kses_post( wc_price( $item['price'] ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $bundle['save'] > 0 ) : ?>
			<p class="qr-bundlebox__total">
				<span class="qr-was"><?php esc_html_e( 'Bought separately', 'qr-minimal-store' ); ?> <s><?php echo wp_kses_post( wc_price( $bundle['total'] ) ); ?></s></span>
				<span class="qr-save">
					<?php
					/* translators: %s: amount saved */
					echo esc_html( sprintf( __( 'You save %s', 'qr-minimal-store' ), qr_minimal_store_money_whole( $bundle['save'] ) ) );
					?>
				</span>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/* ---------- Category and department listings ---------- */

/**
 * Bundles for the current product category listing (first page, no filters or sort):
 * - mode "deals": a Bundle Deals / Gaming Bundles page, cards replace the product cards;
 * - mode "strip": any other category or department page with bundles in it, up to three
 *   buyable bundles with a saving above the grid.
 *
 * @return array{mode: string, bundles: array}|null
 */
function qr_minimal_store_listing_bundles() {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() || is_paged() ) {
		return null;
	}
	foreach ( array( 'stock', 'price', 'brand', 'sort' ) as $param ) {
		if ( isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return null;
		}
	}
	$slugs = array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) get_query_var( 'product_cat' ) ) ) ) );
	if ( ! $slugs ) {
		return null;
	}
	if ( 1 === count( $slugs ) && in_array( $slugs[0], qr_minimal_store_bundle_categories(), true ) ) {
		$cats    = ( 'bundle-deals' === $slugs[0] ) ? qr_minimal_store_bundle_deals_query_slugs() : $slugs;
		$bundles = qr_minimal_store_bundles( $cats );
		// In stock first, keeping the price order.
		usort( $bundles, static fn( $a, $b ) => (int) $b['in_stock'] <=> (int) $a['in_stock'] );
		return $bundles ? array(
			'mode'    => 'deals',
			'bundles' => $bundles,
		) : null;
	}
	$bundles = array_values( array_filter( qr_minimal_store_bundles_buyable( qr_minimal_store_bundles( $slugs ) ), static fn( $bundle ) => $bundle['save'] > 0 ) );
	return $bundles ? array(
		'mode'    => 'strip',
		'bundles' => array_slice( $bundles, 0, 3 ),
	) : null;
}

/**
 * "Bundle deals" strip above a category or department grid.
 *
 * @param array<int, array> $bundles Bundles.
 */
function qr_minimal_store_bundle_strip( $bundles ) {
	$term = get_term_by( 'slug', 'bundle-deals', 'product_cat' );
	$link = $term instanceof WP_Term && $term->count > 0 ? get_term_link( $term ) : '';
	?>
	<section class="qr-bdeals" aria-labelledby="qr-bdeals-title">
		<div class="qr-bdeals__head">
			<div>
				<h2 id="qr-bdeals-title"><?php esc_html_e( 'Bundle deals', 'qr-minimal-store' ); ?></h2>
				<p><?php esc_html_e( 'Items that go together, for less than buying them one by one.', 'qr-minimal-store' ); ?></p>
			</div>
			<?php if ( $link && ! is_wp_error( $link ) ) : ?>
				<a class="qr-bdeals__all" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'See all bundle deals', 'qr-minimal-store' ); ?> <span aria-hidden="true">→</span></a>
			<?php endif; ?>
		</div>
		<div class="qr-bundles<?php echo count( $bundles ) < 3 ? ' qr-bundles--two' : ''; ?>">
			<?php
			foreach ( $bundles as $bundle ) {
				qr_minimal_store_bundle_card( $bundle );
			}
			?>
		</div>
	</section>
	<?php
}
