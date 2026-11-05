<?php
/**
 * Gaming department (designs Gaming.html, HomeGaming.html): category tree, the /gaming/ landing
 * page and bundles.
 *
 * A bundle is a simple product with the meta _bundle_items = its component supplier SKUs joined
 * with "+". store_sync keeps its stock (the lowest component stock) and creates it. Every price,
 * total and saving here is worked out from live store prices; nothing is hard-coded.
 *
 * @package qr-minimal-store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gaming sub-categories: slug => [name, description]. store_sync's GAMING_TREE uses the same slugs.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function qr_minimal_store_gaming_categories() {
	return array(
		'gaming-bundles'        => array( __( 'Gaming Bundles', 'qr-minimal-store' ), __( 'Ready-to-play setups that cost less than buying the same items one by one.', 'qr-minimal-store' ) ),
		'sim-racing'            => array( __( 'Sim Racing', 'qr-minimal-store' ), __( 'Racing wheels, pedals, stands, shifters and handbrakes.', 'qr-minimal-store' ) ),
		'gaming-keyboards-mice' => array( __( 'Gaming Keyboards & Mice', 'qr-minimal-store' ), __( 'Mechanical keyboards, gaming mice and combos.', 'qr-minimal-store' ) ),
		'gaming-headsets-mics'  => array( __( 'Gaming Headsets & Mics', 'qr-minimal-store' ), __( 'Gaming headsets and USB microphones.', 'qr-minimal-store' ) ),
		'controllers-flight'    => array( __( 'Controllers & Flight', 'qr-minimal-store' ), __( 'Controllers and flight joysticks.', 'qr-minimal-store' ) ),
		'gaming-chairs'         => array( __( 'Gaming Chairs', 'qr-minimal-store' ), __( 'Gaming chairs.', 'qr-minimal-store' ) ),
	);
}

/**
 * Create the Gaming category tree once (wp-admin, shop managers). Existing categories with these
 * slugs are left as they are.
 */
add_action( 'admin_init', 'qr_minimal_store_ensure_gaming_categories' );
function qr_minimal_store_ensure_gaming_categories() {
	if ( '1' === get_option( 'qr_minimal_store_gaming_categories' ) || ! current_user_can( 'manage_product_terms' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	$parent = term_exists( 'gaming', 'product_cat' );
	if ( ! $parent ) {
		$parent = wp_insert_term(
			__( 'Gaming', 'qr-minimal-store' ),
			'product_cat',
			array(
				'slug'        => 'gaming',
				'description' => __( 'Racing wheels, keyboards, headsets and ready-to-play bundles, from an authorised South African distributor.', 'qr-minimal-store' ),
			)
		);
	}
	if ( is_wp_error( $parent ) ) {
		return;
	}
	$parent_id = (int) $parent['term_id'];
	foreach ( qr_minimal_store_gaming_categories() as $slug => $category ) {
		if ( term_exists( $slug, 'product_cat' ) ) {
			continue;
		}
		$result = wp_insert_term(
			$category[0],
			'product_cat',
			array(
				'slug'        => $slug,
				'parent'      => $parent_id,
				'description' => $category[1],
			)
		);
		if ( is_wp_error( $result ) ) {
			return;
		}
	}
	update_option( 'qr_minimal_store_gaming_categories', '1', false );
}

/* ---------- /gaming/ landing page ---------- */

add_action( 'init', 'qr_minimal_store_gaming_rewrite' );
function qr_minimal_store_gaming_rewrite() {
	add_rewrite_rule( '^gaming/?$', 'index.php?product_cat=gaming', 'top' );
	if ( get_option( 'qr_minimal_store_gaming_rewrite' ) !== '1' ) {
		flush_rewrite_rules( false );
		update_option( 'qr_minimal_store_gaming_rewrite', '1', false );
	}
}

add_filter( 'qr_minimal_store_department_url', 'qr_minimal_store_gaming_department_url', 10, 2 );
function qr_minimal_store_gaming_department_url( $url, $key ) {
	return 'gaming' === $key && term_exists( 'gaming', 'product_cat' ) ? home_url( '/gaming/' ) : $url;
}

/**
 * The landing page replaces the plain Gaming category listing; filtered, sorted and paged views
 * stay normal listings ("View all gaming" links to one).
 */
function qr_minimal_store_is_gaming_landing() {
	if ( ! is_tax( 'product_cat', 'gaming' ) || is_paged() ) {
		return false;
	}
	foreach ( array( 'stock', 'price', 'brand', 'sort', 'qr_department' ) as $param ) {
		if ( isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}
	}
	return true;
}

add_action( 'template_redirect', 'qr_minimal_store_gaming_canonical' );
function qr_minimal_store_gaming_canonical() {
	if ( ! qr_minimal_store_is_gaming_landing() || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
	if ( false !== strpos( $path, '/product-category/' ) ) {
		wp_safe_redirect( home_url( '/gaming/' ), 301 );
		exit;
	}
}

/**
 * Black Friday wording only runs until the end of November 2026.
 *
 * @return bool
 */
function qr_minimal_store_gaming_is_black_friday() {
	return current_datetime()->format( 'Y-m-d' ) <= '2026-11-30';
}

/* ---------- Bundles ---------- */

/**
 * Card wording for the start-set bundles, keyed by bundle code. A new bundle works without an
 * entry here: the name comes from the product, the tag from its "Card label" field and the
 * one-liner from its "Home page tagline" field (both also override these defaults).
 *
 * @return array<string, array{title: string, anchor: string, inc: string, group: string, tag: string, for: string, hot?: bool}>
 */
function qr_minimal_store_gaming_bundle_defs() {
	return array(
		'PXN-V900GEN2+PXN-A9'                        => array(
			'title'  => __( 'Start Racing', 'qr-minimal-store' ),
			'anchor' => 'bundle-start-racing',
			'inc'    => __( 'PXN V900 Gen2 + PXN A9', 'qr-minimal-store' ),
			'group'  => 'racing',
			'tag'    => __( 'Best first wheel', 'qr-minimal-store' ),
			'for'    => __( 'Swap the controller for a real wheel without bolting anything to your desk.', 'qr-minimal-store' ),
		),
		'PXN-V9GEN2+PXN-A11+PXN-HBS'                 => array(
			'title'  => __( 'Go Pro', 'qr-minimal-store' ),
			'anchor' => 'bundle-go-pro',
			'inc'    => __( 'PXN V9 Gen 2 + PXN A11 + PXN HB S handbrake', 'qr-minimal-store' ),
			'group'  => 'racing',
			'tag'    => __( 'Our top pick', 'qr-minimal-store' ),
			'for'    => __( 'Three pedals, a shifter, a stand and a rally handbrake. Everything a racing game asks for.', 'qr-minimal-store' ),
			'hot'    => true,
		),
		'PXN-V10ULTRA+PXN-A11+PXN-HBS+PXN-SFU'       => array(
			'title'  => __( 'Direct Drive', 'qr-minimal-store' ),
			'anchor' => 'bundle-direct-drive',
			'inc'    => __( 'PXN V10 Ultra + PXN A11 + PXN HB S + PXN SF U', 'qr-minimal-store' ),
			'group'  => 'racing',
			'tag'    => __( 'For serious lap times', 'qr-minimal-store' ),
			'for'    => __( 'Direct-drive force feedback with a 7-speed shifter and handbrake on a solid stand.', 'qr-minimal-store' ),
		),
		'RD-K745WB-RGB-PRO+RD-M997-PRO-1K+RD-H510W'  => array(
			'title'  => __( 'Gamer Desk Set', 'qr-minimal-store' ),
			'anchor' => 'bundle-gamer-desk-set',
			'inc'    => __( 'K745 Pro + Prism Pro + Zeus 2', 'qr-minimal-store' ),
			'group'  => 'desk',
			'tag'    => __( 'Upgrade in one box', 'qr-minimal-store' ),
			'for'    => __( 'Mechanical keyboard, 26 000 DPI wireless mouse and a USB headset.', 'qr-minimal-store' ),
		),
		'RD-K652GG-RGB-PRO+RD-H858+PCB-GM-CASTULTRA' => array(
			'title'  => __( 'Stream Setup', 'qr-minimal-store' ),
			'anchor' => 'bundle-stream-setup',
			'inc'    => __( 'Azure + Arrow Pro + Cast Ultra', 'qr-minimal-store' ),
			'group'  => 'desk',
			'tag'    => __( 'Ready to go live', 'qr-minimal-store' ),
			'for'    => __( 'Wireless keyboard, wireless headset and a plug-in USB mic for TikTok LIVE and Discord.', 'qr-minimal-store' ),
		),
	);
}

/**
 * Short names for the long supplier names, keyed by supplier SKU.
 *
 * @return array<string, string>
 */
function qr_minimal_store_gaming_short_names() {
	return array(
		'PXN-V900GEN2'          => __( 'PXN V900 Gen2 wheel + 2 pedals', 'qr-minimal-store' ),
		'PXN-V9GEN2'            => __( 'PXN V9 Gen 2 wheel + 3 pedals + shifter', 'qr-minimal-store' ),
		'PXN-V10ULTRA'          => __( 'PXN V10 Ultra direct-drive wheel + pedals', 'qr-minimal-store' ),
		'PXN-V3PRO'             => __( 'PXN V3 Pro wheel + 2 pedals', 'qr-minimal-store' ),
		'PXN-A9'                => __( 'PXN A9 foldable wheel stand', 'qr-minimal-store' ),
		'PXN-A11'               => __( 'PXN A11 foldable wheel stand', 'qr-minimal-store' ),
		'PXN-HBS'               => __( 'PXN HB S 16-bit handbrake', 'qr-minimal-store' ),
		'PXN-SFU'               => __( 'PXN SF U 7+R gear shifter', 'qr-minimal-store' ),
		'PXN-F16'               => __( 'PXN F16 flight joystick (PC)', 'qr-minimal-store' ),
		'PXN-P5 8K'             => __( 'PXN P5 8K wireless controller', 'qr-minimal-store' ),
		'RD-K745WB-RGB-PRO'     => __( 'Redragon K745 Pro mechanical keyboard', 'qr-minimal-store' ),
		'RD-M997-PRO-1K'        => __( 'Redragon Prism Pro wireless mouse', 'qr-minimal-store' ),
		'RD-K652GG-RGB-PRO'     => __( 'Redragon Azure wireless keyboard', 'qr-minimal-store' ),
		'RD-S148'               => __( 'Redragon 4-in-1 keyboard, mouse, pad, headset', 'qr-minimal-store' ),
		'RD-H510W'              => __( 'Redragon Zeus 2 USB headset', 'qr-minimal-store' ),
		'RD-H858'               => __( 'Redragon Arrow Pro wireless headset', 'qr-minimal-store' ),
		'PCB-GM-CASTULTRA'      => __( 'PCBuilder Cast Ultra USB mic', 'qr-minimal-store' ),
		'PCB_GC_NAVIGATOR-LITE' => __( 'PCBuilder Navigator Lite gaming chair', 'qr-minimal-store' ),
	);
}

/**
 * Short card name of a gaming product, else its store name.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function qr_minimal_store_gaming_short_name( $product ) {
	$names = qr_minimal_store_gaming_short_names();
	$code  = strtoupper( trim( (string) $product->get_meta( 'supplier_code' ) ) );
	return isset( $names[ $code ] ) ? $names[ $code ] : html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' );
}

/**
 * Published single product for a component SKU: "SYN-<code>" (how store_sync creates them), else
 * the code itself.
 *
 * @param string $code Supplier SKU.
 * @return WC_Product|null
 */
function qr_minimal_store_gaming_component( $code ) {
	foreach ( array( 'SYN-' . $code, $code ) as $sku ) {
		$id = wc_get_product_id_by_sku( $sku );
		if ( $id ) {
			$product = wc_get_product( $id );
			if ( $product && 'publish' === $product->get_status() ) {
				return $product;
			}
		}
	}
	return null;
}

/**
 * All published bundles with their live numbers, start set first. A bundle with a component that
 * is not on sale on its own is left out: there is no honest "bought separately" total for it.
 *
 * @return array<int, array> product, code, title, anchor, inc, group, tag, for, hot, items (product,
 *                           name, price), price, total, save, in_stock, free.
 */
function qr_minimal_store_gaming_bundles() {
	static $bundles = null;
	if ( null !== $bundles ) {
		return $bundles;
	}
	$bundles = array();
	if ( ! function_exists( 'wc_get_product' ) ) {
		return $bundles;
	}
	$query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => 24,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'meta_key'            => '_bundle_items', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-catalog' ),
					'operator' => 'NOT IN',
				),
			),
		)
	);
	$defs  = qr_minimal_store_gaming_bundle_defs();
	$rule  = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
	foreach ( $query->posts as $id ) {
		$product = wc_get_product( $id );
		$code    = $product ? strtoupper( trim( (string) $product->get_meta( '_bundle_items' ) ) ) : '';
		$codes   = array_values( array_filter( array_map( 'trim', explode( '+', $code ) ) ) );
		if ( count( $codes ) < 2 ) {
			continue;
		}
		$items = array();
		$total = 0.0;
		foreach ( $codes as $part ) {
			$component = qr_minimal_store_gaming_component( $part );
			if ( ! $component ) {
				$items = array();
				break;
			}
			$price   = (float) wc_get_price_to_display( $component );
			$total  += $price;
			$items[] = array(
				'product' => $component,
				'name'    => qr_minimal_store_gaming_short_name( $component ),
				'price'   => $price,
			);
		}
		if ( ! $items ) {
			continue;
		}
		$def   = isset( $defs[ $code ] ) ? $defs[ $code ] : array();
		$title = isset( $def['title'] ) ? $def['title'] : preg_replace( '/\s+Bundle$/i', '', html_entity_decode( $product->get_name(), ENT_QUOTES, 'UTF-8' ) );
		$tag   = trim( (string) $product->get_meta( '_qr_card_label' ) );
		$for   = trim( (string) $product->get_meta( '_qr_tagline' ) );
		if ( isset( $def['group'] ) ) {
			$group = $def['group'];
		} else {
			$group = has_term( 'sim-racing', 'product_cat', $items[0]['product']->get_id() ) ? 'racing' : 'desk';
		}
		$price     = (float) wc_get_price_to_display( $product );
		$bundles[] = array(
			'product'  => $product,
			'code'     => $code,
			'title'    => $title,
			'anchor'   => isset( $def['anchor'] ) ? $def['anchor'] : 'bundle-' . sanitize_title( $title ),
			'inc'      => isset( $def['inc'] ) ? $def['inc'] : implode( ' + ', array_column( $items, 'name' ) ),
			'group'    => $group,
			'tag'      => '' !== $tag ? $tag : ( isset( $def['tag'] ) ? $def['tag'] : '' ),
			'for'      => '' !== $for ? $for : ( isset( $def['for'] ) ? $def['for'] : wp_strip_all_tags( $product->get_short_description() ) ),
			'hot'      => ! empty( $def['hot'] ),
			'items'    => $items,
			'price'    => $price,
			'total'    => $total,
			'save'     => max( 0.0, $total - $price ),
			'in_stock' => $product->is_purchasable() && $product->is_in_stock(),
			'free'     => $rule && $price >= $rule['threshold'],
		);
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
	return $bundles;
}

/**
 * Bundles that can be bought now.
 *
 * @return array<int, array>
 */
function qr_minimal_store_gaming_buyable_bundles() {
	return array_values( array_filter( qr_minimal_store_gaming_bundles(), static fn( $bundle ) => $bundle['in_stock'] ) );
}

/**
 * Link to a bundle's card on the landing page.
 *
 * @param array $bundle Bundle.
 * @return string
 */
function qr_minimal_store_gaming_bundle_url( $bundle ) {
	return home_url( '/gaming/' ) . '#' . $bundle['anchor'];
}

/**
 * The bundle to nudge a single item towards: _bundle_items read in reverse. When the item is in
 * several bundles, the cheapest one in stock with a real saving wins.
 *
 * @param WC_Product $product Single item.
 * @return array|null
 */
function qr_minimal_store_gaming_nudge_bundle( $product ) {
	$best = null;
	foreach ( qr_minimal_store_gaming_buyable_bundles() as $bundle ) {
		if ( $bundle['save'] <= 0 || ( $best && $best['price'] <= $bundle['price'] ) ) {
			continue;
		}
		foreach ( $bundle['items'] as $item ) {
			if ( $item['product']->get_id() === $product->get_id() ) {
				$best = $bundle;
				break;
			}
		}
	}
	return $best;
}

/**
 * Single items in stock: every bundle component in bundle order, then the rest of the Gaming
 * categories. No backorders on gaming.
 *
 * @return WC_Product[]
 */
function qr_minimal_store_gaming_singles() {
	$singles = array();
	foreach ( qr_minimal_store_gaming_bundles() as $bundle ) {
		foreach ( $bundle['items'] as $item ) {
			$singles[ $item['product']->get_id() ] = $item['product'];
		}
	}
	$slugs = array_values( array_diff( array_keys( qr_minimal_store_gaming_categories() ), array( 'gaming-bundles' ) ) );
	foreach ( qr_minimal_store_3d_products( $slugs, 48 ) as $product ) {
		$singles[ $product->get_id() ] = $product;
	}
	return array_values(
		array_filter(
			$singles,
			static fn( $product ) => '' === (string) $product->get_meta( '_bundle_items' )
				&& $product->is_visible()
				&& $product->is_purchasable()
				&& $product->is_in_stock()
				&& ! $product->is_on_backorder( 1 )
		)
	);
}

/**
 * Hero images: the design's four, with other bundle components filling any gap.
 *
 * @return WC_Product[]
 */
function qr_minimal_store_gaming_hero_products() {
	$products = array();
	foreach ( array( 'PXN-V9GEN2', 'RD-H858', 'PXN-A11', 'RD-M997-PRO-1K' ) as $code ) {
		$product = qr_minimal_store_gaming_component( $code );
		if ( $product ) {
			$products[ $product->get_id() ] = $product;
		}
	}
	foreach ( qr_minimal_store_gaming_bundles() as $bundle ) {
		foreach ( $bundle['items'] as $item ) {
			$products[ $item['product']->get_id() ] = $item['product'];
		}
	}
	return array_slice( array_values( $products ), 0, 4 );
}

/**
 * Bundle image with a real alt text.
 *
 * @param WC_Product $product Product.
 * @param string     $alt     Alt text.
 * @param string     $size    Image size.
 * @param string     $loading lazy or eager.
 * @return string
 */
function qr_minimal_store_gaming_image( $product, $alt, $size = 'woocommerce_thumbnail', $loading = 'lazy' ) {
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
function qr_minimal_store_gaming_collage( $bundle, $class = '' ) {
	$items = array_slice( $bundle['items'], 0, 4 );
	?>
	<div class="qr-collage qr-collage--<?php echo esc_attr( (string) count( $items ) ); ?> <?php echo esc_attr( $class ); ?>">
		<?php foreach ( $items as $index => $item ) : ?>
			<span class="qr-gtile"><?php echo qr_minimal_store_gaming_image( $item['product'], $item['name'], 0 === $index ? 'woocommerce_single' : 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?></span>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Bundle card (Gaming.html). The Save badge and the crossed-out total only show while the bundle
 * really costs less than its parts.
 *
 * @param array $bundle Bundle.
 */
function qr_minimal_store_gaming_bundle_card( $bundle ) {
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
			<?php qr_minimal_store_gaming_collage( $bundle ); ?>
		</a>
		<h3 class="qr-bundle__name"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $bundle['title'] ); ?></a></h3>
		<?php if ( '' !== $bundle['for'] ) : ?>
			<p class="qr-bundle__for"><?php echo esc_html( $bundle['for'] ); ?></p>
		<?php endif; ?>
		<p class="screen-reader-text"><?php esc_html_e( "What's in the box", 'qr-minimal-store' ); ?></p>
		<ul class="qr-bundle__box">
			<?php foreach ( $bundle['items'] as $item ) : ?>
				<li>
					<span><?php echo esc_html( $item['name'] ); ?></span>
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
 * Single item card (Gaming.html): the bundle nudge when the item is in a bundle, else the
 * delivery line.
 *
 * @param WC_Product $product Product.
 */
function qr_minimal_store_gaming_single_card( $product ) {
	$url    = $product->get_permalink();
	$name   = qr_minimal_store_gaming_short_name( $product );
	$bundle = qr_minimal_store_gaming_nudge_bundle( $product );
	$rule   = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
	?>
	<li class="qr-gsingle product">
		<a class="qr-gsingle__img qr-gtile" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true"><?php echo qr_minimal_store_gaming_image( $product, $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?></a>
		<p class="qr-gstock"><?php esc_html_e( 'In stock', 'qr-minimal-store' ); ?></p>
		<h3 class="qr-gsingle__name"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a></h3>
		<p class="qr-gsingle__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
		<?php if ( $bundle ) : ?>
			<?php $more = count( $bundle['items'] ) - 1; ?>
			<p class="qr-nudge">
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
				<a href="#<?php echo esc_attr( $bundle['anchor'] ); ?>"><?php esc_html_e( 'See bundle', 'qr-minimal-store' ); ?><span class="screen-reader-text">: <?php echo esc_html( $bundle['title'] ); ?></span></a>
			</p>
		<?php elseif ( $rule ) : ?>
			<p class="qr-gsmall qr-gsingle__delivery">
				<?php
				if ( (float) wc_get_price_to_display( $product ) >= $rule['threshold'] ) {
					esc_html_e( 'Free delivery', 'qr-minimal-store' );
				} else {
					/* translators: 1: delivery fee, 2: free delivery threshold */
					echo esc_html( sprintf( __( 'Delivery %1$s, free from %2$s', 'qr-minimal-store' ), qr_minimal_store_money_whole( $rule['fee'] ), qr_minimal_store_money_whole( $rule['threshold'] ) ) );
				}
				?>
			</p>
		<?php endif; ?>
	</li>
	<?php
}

/* ---------- Home page band ---------- */

/**
 * Three bundles for the home band: the design's three while they can be bought, others filling
 * any gap.
 *
 * @return array<int, array>
 */
function qr_minimal_store_gaming_home_bundles() {
	$buyable = qr_minimal_store_gaming_buyable_bundles();
	$first   = array( 'PXN-V900GEN2+PXN-A9', 'PXN-V9GEN2+PXN-A11+PXN-HBS', 'RD-K652GG-RGB-PRO+RD-H858+PCB-GM-CASTULTRA' );
	usort(
		$buyable,
		static function ( $a, $b ) use ( $first ) {
			$a_rank = array_search( $a['code'], $first, true );
			$b_rank = array_search( $b['code'], $first, true );
			return ( false === $a_rank ? 99 : $a_rank ) <=> ( false === $b_rank ? 99 : $b_rank );
		}
	);
	return array_slice( $buyable, 0, 3 );
}

/**
 * Gaming band on the home page (HomeGaming.html). Hidden while no bundle is in stock.
 */
function qr_minimal_store_gaming_home_band() {
	$bundles = qr_minimal_store_gaming_home_bundles();
	if ( ! $bundles ) {
		return;
	}
	$count = count( qr_minimal_store_gaming_bundles() );
	$url   = home_url( '/gaming/' );
	$free  = ! array_filter( qr_minimal_store_gaming_bundles(), static fn( $bundle ) => ! $bundle['free'] );
	?>
	<section class="qr-section container" aria-labelledby="qr-gband-title">
		<div class="qr-gband qr-gdark">
			<div class="qr-gband__top">
				<div>
					<p class="qr-geyebrow"><?php echo esc_html( qr_minimal_store_gaming_is_black_friday() ? __( 'New · Gaming · Black Friday', 'qr-minimal-store' ) : __( 'New · Gaming', 'qr-minimal-store' ) ); ?></p>
					<h2 id="qr-gband-title"><?php esc_html_e( 'Buy the setup, not the parts.', 'qr-minimal-store' ); ?></h2>
					<p>
						<?php
						echo esc_html(
							$free
								? __( 'Ready-to-play bundles that cost less than buying the items one by one. Free delivery on every bundle.', 'qr-minimal-store' )
								: __( 'Ready-to-play bundles that cost less than buying the items one by one.', 'qr-minimal-store' )
						);
						?>
					</p>
				</div>
				<a class="qr-btn" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Shop gaming bundles', 'qr-minimal-store' ); ?></a>
			</div>
			<ul class="qr-gband__cards">
				<?php foreach ( $bundles as $bundle ) : ?>
					<li>
						<a class="qr-gmini" href="<?php echo esc_url( qr_minimal_store_gaming_bundle_url( $bundle ) ); ?>">
							<?php qr_minimal_store_gaming_collage( $bundle, 'qr-collage--mini' ); ?>
							<span class="qr-gmini__row">
								<span class="qr-gmini__name"><?php echo esc_html( $bundle['title'] ); ?></span>
								<?php if ( $bundle['save'] > 0 ) : ?>
									<span class="qr-save">
										<?php
										/* translators: %s: amount saved */
										echo esc_html( sprintf( __( 'Save %s', 'qr-minimal-store' ), qr_minimal_store_money_whole( $bundle['save'] ) ) );
										?>
									</span>
								<?php endif; ?>
							</span>
							<span class="qr-gmini__inc"><?php echo esc_html( $bundle['inc'] ); ?></span>
							<span class="qr-gmini__row">
								<span>
									<span class="qr-gmini__price"><?php echo wp_kses_post( wc_price( $bundle['price'] ) ); ?></span>
									<?php if ( $bundle['save'] > 0 ) : ?>
										<s class="qr-gmini__was"><?php echo wp_kses_post( wc_price( $bundle['total'] ) ); ?></s>
									<?php endif; ?>
								</span>
								<?php if ( $bundle['free'] ) : ?>
									<span class="qr-gmini__free"><?php esc_html_e( 'Free delivery', 'qr-minimal-store' ); ?></span>
								<?php endif; ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="qr-gband__foot">
				<span><?php esc_html_e( 'Genuine, SA warranty · 30-day returns · Pay monthly with Mobicred or RCS, subject to approval', 'qr-minimal-store' ); ?></span>
				<a href="<?php echo esc_url( $url ); ?>">
					<?php
					/* translators: %d: number of bundles */
					echo esc_html( sprintf( _n( 'See %d bundle', 'See all %d bundles', $count, 'qr-minimal-store' ), $count ) );
					?>
					<span aria-hidden="true">→</span>
				</a>
			</div>
		</div>
	</section>
	<?php
}
