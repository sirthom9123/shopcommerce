<?php
/**
 * Home page, header departments and footer (design board A).
 *
 * Everything here renders the same for every visitor, so it is safe for the LiteSpeed page
 * cache. Sections without in-stock store data are not rendered.
 *
 * @package qr-minimal-store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Departments shown in the header, the home tiles and the footer. Each maps to the store's
 * current product categories; once a parent category with the department slug exists (and
 * has sub-categories), links go to that category instead.
 *
 * @return array<string, array{title: string, sub: string, cats: string[], new: bool}>
 */
function qr_minimal_store_departments() {
	$departments = array(
		'smart-home'        => array(
			'title' => __( 'Smart Home', 'qr-minimal-store' ),
			'sub'   => __( 'Cameras, doorbell, robot vacuum, air', 'qr-minimal-store' ),
			'cats'  => array( 'smart-home', 'smart-home-camera', 'smart-home-doorbell', 'smart-home-vacuum', 'air-quality', 'security-camera', 'security-nvr', 'cctv' ),
		),
		'kitchen'           => array(
			'title' => __( 'Kitchen', 'qr-minimal-store' ),
			'sub'   => __( 'Air fryers, espresso, small appliances', 'qr-minimal-store' ),
			'cats'  => array( 'kitchen-appliances' ),
		),
		'wearables'         => array(
			'title' => __( 'Wearables', 'qr-minimal-store' ),
			'sub'   => __( 'Smart watches and bands', 'qr-minimal-store' ),
			'cats'  => array( 'smart-watches-bands' ),
		),
		'pet-tech'          => array(
			'title' => __( 'Pet Tech', 'qr-minimal-store' ),
			'sub'   => __( 'Fountains, pet air purifier, filters', 'qr-minimal-store' ),
			'cats'  => array( 'pet-tech' ),
		),
		'grooming-wellness' => array(
			'title' => __( 'Grooming & Wellness', 'qr-minimal-store' ),
			'sub'   => __( 'Hair dryers, massage guns, scales', 'qr-minimal-store' ),
			'cats'  => array( 'personal-grooming-wellness' ),
		),
		'computer-office'   => array(
			'title' => __( 'Computer & Office', 'qr-minimal-store' ),
			'sub'   => __( 'Mice, keyboards, monitors, bags', 'qr-minimal-store' ),
			'cats'  => array( 'peripherals', 'peripherals-mouse', 'peripherals-keyboard', 'peripherals-combo', 'peripherals-headset', 'monitors', 'bags-accessories' ),
		),
		'power-mobile'      => array(
			'title' => __( 'Power & Mobile', 'qr-minimal-store' ),
			'sub'   => __( 'Power banks, power stations, protection', 'qr-minimal-store' ),
			'cats'  => array( 'power', 'portable-power', 'protection', 'power-lighting', 'ups', 'mobile', 'chargers-accessories', 'accessories', 'storage' ),
		),
		'tv-audio'          => array(
			'title' => __( 'TV & Audio', 'qr-minimal-store' ),
			'sub'   => __( 'Soundbars and media players', 'qr-minimal-store' ),
			'cats'  => array( 'audio-soundbar' ),
		),
		'3d-printing'       => array(
			'title' => __( '3D Printing', 'qr-minimal-store' ),
			'sub'   => __( 'Anycubic printers, filament, resin', 'qr-minimal-store' ),
			'cats'  => array( '3d-printing', 'fdm-printers', 'resin-printers', 'wash-and-cure', 'filament', 'resin', 'spares' ),
			'new'   => true,
		),
	);
	foreach ( $departments as $key => $department ) {
		$departments[ $key ]['new'] = ! empty( $department['new'] );
	}
	return apply_filters( 'qr_minimal_store_departments', $departments );
}

/**
 * Departments with at least one product to buy (in stock or on backorder), with their
 * existing category slugs, link and a representative product. Cached for 30 minutes.
 *
 * @return array<string, array>
 */
function qr_minimal_store_available_departments() {
	$cached = get_transient( 'qr_minimal_store_departments' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$available = array();
	if ( ! function_exists( 'wc_get_products' ) ) {
		return $available;
	}
	foreach ( qr_minimal_store_departments() as $key => $department ) {
		$slugs = array();
		foreach ( $department['cats'] as $slug ) {
			if ( term_exists( $slug, 'product_cat' ) ) {
				$slugs[] = $slug;
			}
		}
		if ( empty( $slugs ) ) {
			continue;
		}
		$query = new WP_Query(
			array(
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'posts_per_page'      => 1,
				'fields'              => 'ids',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'meta_key'            => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'             => array(
					'meta_value_num' => 'DESC',
					'date'           => 'DESC',
				),
				'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => $slugs,
					),
					qr_minimal_store_visible_in_stock_tax_query(),
				),
			)
		);
		if ( empty( $query->posts ) ) {
			continue;
		}
		$available[ $key ] = array_merge(
			$department,
			array(
				'key'        => $key,
				'slugs'      => $slugs,
				'product_id' => (int) $query->posts[0],
				'url'        => qr_minimal_store_department_url( $key, $slugs ),
			)
		);
	}
	set_transient( 'qr_minimal_store_departments', $available, 30 * MINUTE_IN_SECONDS );
	return $available;
}

add_action( 'save_post_product', 'qr_minimal_store_flush_home_cache' );
add_action( 'woocommerce_product_set_stock_status', 'qr_minimal_store_flush_home_cache' );
add_action( 'created_product_cat', 'qr_minimal_store_flush_home_cache' );
add_action( 'edited_product_cat', 'qr_minimal_store_flush_home_cache' );
function qr_minimal_store_flush_home_cache() {
	delete_transient( 'qr_minimal_store_departments' );
}

/**
 * Hide hidden and sold-out products (backorders stay).
 *
 * @return array
 */
function qr_minimal_store_visible_in_stock_tax_query() {
	return array(
		'taxonomy' => 'product_visibility',
		'field'    => 'name',
		'terms'    => array( 'exclude-from-catalog', 'outofstock' ),
		'operator' => 'NOT IN',
	);
}

/**
 * @param string   $key   Department key.
 * @param string[] $slugs Existing category slugs of the department.
 * @return string
 */
function qr_minimal_store_department_url( $key, $slugs ) {
	$parent = get_term_by( 'slug', $key, 'product_cat' );
	if ( $parent instanceof WP_Term ) {
		$children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent->term_id,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
			$link = get_term_link( $parent );
			return is_wp_error( $link ) ? home_url( '/' ) : $link;
		}
	}
	if ( 1 === count( $slugs ) ) {
		$link = get_term_link( $slugs[0], 'product_cat' );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}
	return add_query_arg(
		array(
			'product_cat'   => implode( ',', $slugs ),
			'qr_department' => $key,
		),
		home_url( '/' )
	);
}

// A department link that spans several categories is titled with the department name.
add_filter( 'woocommerce_page_title', 'qr_minimal_store_department_title' );
function qr_minimal_store_department_title( $title ) {
	$key = isset( $_GET['qr_department'] ) ? sanitize_key( wp_unslash( $_GET['qr_department'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$all = qr_minimal_store_departments();
	return ( $key && isset( $all[ $key ] ) && is_tax( 'product_cat' ) ) ? $all[ $key ]['title'] : $title;
}

/**
 * Whole-rand amount in store format (R2 500).
 *
 * @param float $amount Amount.
 * @return string
 */
function qr_minimal_store_money_whole( $amount ) {
	return html_entity_decode( wp_strip_all_tags( wc_price( $amount, array( 'decimals' => 0 ) ) ), ENT_QUOTES, 'UTF-8' );
}

/**
 * Offer bar lines (delivery, returns, credit).
 *
 * @return array<int, array{strong: string, rest: string, short: bool}>
 */
function qr_minimal_store_offer_lines() {
	$lines = array();
	$rule  = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
	if ( $rule && function_exists( 'wc_price' ) ) {
		$lines[] = array(
			/* translators: %s: delivery fee */
			'strong' => sprintf( __( '%s delivery', 'qr-minimal-store' ), qr_minimal_store_money_whole( $rule['fee'] ) ),
			/* translators: %s: free delivery threshold */
			'rest'   => sprintf( __( ', free from %s', 'qr-minimal-store' ), qr_minimal_store_money_whole( $rule['threshold'] ) ),
			'short'  => true,
		);
	}
	$lines[] = array(
		'strong' => __( '30-day returns', 'qr-minimal-store' ),
		'rest'   => __( ' on unused items', 'qr-minimal-store' ),
		'short'  => true,
	);
	$lines[] = array(
		'strong' => __( 'Pay in instalments', 'qr-minimal-store' ),
		'rest'   => __( ' with Mobicred or RCS, subject to approval', 'qr-minimal-store' ),
		'short'  => false,
	);
	return $lines;
}

function qr_minimal_store_offer_bar() {
	?>
	<div class="qr-offer-bar">
		<div class="container qr-offer-bar__inner">
			<?php foreach ( qr_minimal_store_offer_lines() as $line ) : ?>
				<span class="<?php echo $line['short'] ? '' : 'qr-offer-bar__long'; ?>"><strong><?php echo esc_html( $line['strong'] ); ?></strong><?php echo esc_html( $line['rest'] ); ?></span>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Header department links.
 *
 * @param int $limit Max departments.
 */
function qr_minimal_store_department_links( $limit = 7 ) {
	$departments = array_slice( qr_minimal_store_available_departments(), 0, $limit, true );
	foreach ( $departments as $department ) {
		printf(
			'<li><a href="%1$s">%2$s%3$s</a></li>',
			esc_url( $department['url'] ),
			esc_html( $department['title'] ),
			$department['new'] ? ' <span class="qr-new-pill">' . esc_html__( 'New', 'qr-minimal-store' ) . '</span>' : ''
		);
	}
}

/**
 * Header search: one field, products only.
 */
function qr_minimal_store_header_search() {
	$value = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<form role="search" method="get" class="qr-hdr__search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="qr-product-search"><?php esc_html_e( 'Search products', 'qr-minimal-store' ); ?></label>
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
		<input id="qr-product-search" type="search" name="s" placeholder="<?php esc_attr_e( 'Search air fryers, smart watches and more…', 'qr-minimal-store' ); ?>" value="<?php echo esc_attr( $value ); ?>">
		<input type="hidden" name="post_type" value="product">
		<button type="submit" class="screen-reader-text"><?php esc_html_e( 'Search', 'qr-minimal-store' ); ?></button>
	</form>
	<?php
}

// Header cart count after an AJAX add to cart.
add_filter( 'woocommerce_add_to_cart_fragments', 'qr_minimal_store_cart_count_fragment' );
function qr_minimal_store_cart_count_fragment( $fragments ) {
	$fragments['span.cart-count'] = '<span class="cart-count">' . esc_html( (string) WC()->cart->get_cart_contents_count() ) . '</span>';
	return $fragments;
}

/**
 * Popular in-stock products by store sales (WooCommerce total_sales), never sold out.
 * Accessories and filters are kept out so the row opens with main products.
 *
 * @param int   $limit   Products.
 * @param int[] $exclude Product IDs to skip.
 * @return WC_Product[]
 */
function qr_minimal_store_popular_products( $limit = 4, $exclude = array() ) {
	$skip_cats = apply_filters( 'qr_minimal_store_popular_exclude_cats', array( 'accessories', 'bags-accessories', 'chargers-accessories', 'mobile', 'storage' ) );
	$tax_query = array( qr_minimal_store_visible_in_stock_tax_query() );
	$skip_cats = array_values( array_filter( $skip_cats, 'term_exists' ) );
	if ( $skip_cats ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $skip_cats,
			'operator' => 'NOT IN',
		);
	}
	$query    = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit * 4,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'post__not_in'        => array_map( 'absint', $exclude ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			'meta_key'            => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'             => array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			),
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);
	$products = array();
	foreach ( $query->posts as $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() || preg_match( '/\bfilters?\b/i', $product->get_name() ) ) {
			continue;
		}
		$products[] = $product;
		if ( count( $products ) >= $limit ) {
			break;
		}
	}
	return $products;
}

/**
 * Hero: four products, from the Customizer ("Home page" → hero product IDs) or, by default,
 * the most popular in-stock product of four different departments.
 *
 * @return WC_Product[]
 */
function qr_minimal_store_hero_products() {
	$products = array();
	$ids      = array_filter( array_map( 'absint', explode( ',', (string) get_theme_mod( 'qr_home_hero_products', '' ) ) ) );
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( $product && $product->is_visible() && $product->is_purchasable() && $product->is_in_stock() ) {
			$products[] = $product;
		}
	}
	if ( count( $products ) < 4 ) {
		// Same rules as "Popular right now" (no accessories or filters), one per department first.
		$candidates = qr_minimal_store_popular_products( 24, array_map( fn( $p ) => $p->get_id(), $products ) );
		$used       = array();
		$rest       = array();
		foreach ( $candidates as $product ) {
			$department = qr_minimal_store_product_department_info( $product );
			$key        = $department ? $department['key'] : 'other-' . $product->get_id();
			if ( isset( $used[ $key ] ) ) {
				$rest[] = $product;
				continue;
			}
			$used[ $key ] = true;
			$products[]   = $product;
			if ( count( $products ) >= 4 ) {
				break;
			}
		}
		$products = array_merge( $products, $rest );
	}
	return array_slice( $products, 0, 4 );
}

/**
 * One short line for a product card: the "Home page tagline" field, else the short description.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function qr_minimal_store_product_tagline( $product ) {
	$tagline = trim( (string) $product->get_meta( '_qr_tagline' ) );
	if ( '' === $tagline ) {
		$tagline = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 9, '…' );
	}
	return $tagline;
}

/**
 * Department of a product (first matching department), for hero tags.
 *
 * @param WC_Product $product Product.
 * @return array|null
 */
function qr_minimal_store_product_department_info( $product ) {
	$slugs = wp_get_post_terms( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
	if ( is_wp_error( $slugs ) ) {
		return null;
	}
	foreach ( qr_minimal_store_departments() as $key => $department ) {
		if ( array_intersect( $slugs, $department['cats'] ) ) {
			return array_merge( $department, array( 'key' => $key ) );
		}
	}
	return null;
}

/**
 * Brands we sell (product_brand terms with products).
 *
 * @return WP_Term[]
 */
function qr_minimal_store_home_brands() {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_brand',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 8,
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Brands strip (home page, between the sign-up and the footer).
 */
function qr_minimal_store_brands_strip() {
	$brands = qr_minimal_store_home_brands();
	if ( ! $brands ) {
		return;
	}
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	?>
	<section class="qr-brands" aria-label="<?php esc_attr_e( 'Brands', 'qr-minimal-store' ); ?>">
		<div class="container qr-brands__inner">
			<span class="qr-brands__label"><?php esc_html_e( 'Authorised brands', 'qr-minimal-store' ); ?></span>
			<ul>
				<?php foreach ( $brands as $brand ) : ?>
					<?php $link = get_term_link( $brand ); ?>
					<li><a href="<?php echo esc_url( is_wp_error( $link ) ? $shop_url : $link ); ?>"><?php echo esc_html( $brand->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
}

/**
 * Mobicred and RCS logos from qr-payment-badges.
 *
 * @return array<string, string> alt => url
 */
function qr_minimal_store_credit_logos() {
	$logos = array();
	foreach ( array(
		'Mobicred' => 'mobicred.png',
		'RCS'      => 'rcs.png',
	) as $alt => $file ) {
		if ( file_exists( WP_PLUGIN_DIR . '/qr-payment-badges/assets/images/' . $file ) ) {
			$logos[ $alt ] = plugins_url( 'qr-payment-badges/assets/images/' . $file );
		}
	}
	return $logos;
}

/**
 * Click-to-load YouTube player. Nothing loads from YouTube until the visitor presses play
 * and has allowed marketing cookies (qr-analytics); otherwise they get the cookie settings
 * and a "Watch on YouTube" link. The poster is a store image.
 *
 * @param string $video_id YouTube video ID.
 * @param string $title    Video title.
 * @param int    $image_id Poster attachment ID.
 * @return string
 */
function qr_minimal_store_youtube_html( $video_id, $title, $image_id = 0 ) {
	$video_id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $video_id );
	if ( 11 !== strlen( $video_id ) ) {
		return '';
	}
	$poster = $image_id ? wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'qr-video__poster', 'alt' => '', 'loading' => 'lazy' ) ) : '';
	ob_start();
	?>
	<div class="qr-video" data-qr-youtube="<?php echo esc_attr( $video_id ); ?>" data-qr-title="<?php echo esc_attr( $title ); ?>">
		<?php echo $poster; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attachment image HTML. ?>
		<div class="qr-video__overlay">
			<button type="button" class="qr-video__play" data-qr-video-play>
				<svg width="80" height="80" viewBox="0 0 80 80" aria-hidden="true" focusable="false"><circle cx="40" cy="40" r="39" fill="#000" fill-opacity="0.35" stroke="#fff" stroke-width="2"/><path d="M33 26l24 14-24 14z" fill="#fff"/></svg>
				<span class="screen-reader-text">
					<?php
					/* translators: %s: video title */
					echo esc_html( sprintf( __( 'Play video: %s', 'qr-minimal-store' ), $title ) );
					?>
				</span>
			</button>
			<p class="qr-video__title"><?php echo esc_html( $title ); ?></p>
			<p class="qr-video__note"><?php esc_html_e( 'Plays from YouTube when you press play', 'qr-minimal-store' ); ?></p>
		</div>
		<div class="qr-video__consent" hidden>
			<p><?php esc_html_e( 'This video plays from YouTube, which uses marketing cookies. Allow marketing cookies to play it here, or watch it on YouTube.', 'qr-minimal-store' ); ?></p>
			<div class="qr-video__actions">
				<button type="button" class="qr-consent-open qr-btn qr-btn--light"><?php esc_html_e( 'Cookie settings', 'qr-minimal-store' ); ?></button>
				<a class="qr-btn qr-btn--ghost-light" href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $video_id ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Watch on YouTube', 'qr-minimal-store' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'qr-minimal-store' ); ?></span></a>
			</div>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Home 3D printing feature: the product set in the Customizer, while it can be bought.
 *
 * @return WC_Product|null
 */
function qr_minimal_store_home_feature_product() {
	$id      = absint( get_theme_mod( 'qr_home_feature_product', 0 ) );
	$product = $id ? wc_get_product( $id ) : null;
	if ( ! $product || ! $product->is_visible() || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return null;
	}
	return $product;
}

/**
 * Store TikTok and Instagram profiles (Customizer → Home page).
 *
 * @return array<string, array{url: string, handle: string}>
 */
function qr_minimal_store_social_profiles() {
	$profiles = array();
	foreach ( array(
		'TikTok'    => array( 'qr_social_tiktok', 'https://www.tiktok.com/@yehuda_solutions' ),
		'Instagram' => array( 'qr_social_instagram', 'https://www.instagram.com/yehuda_store22/' ),
	) as $network => $setting ) {
		$url = (string) get_theme_mod( $setting[0], $setting[1] );
		if ( '' === $url ) {
			continue;
		}
		$path                 = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		$profiles[ $network ] = array(
			'url'    => $url,
			'handle' => '@' . ltrim( strtok( $path, '/' ), '@' ),
		);
	}
	return $profiles;
}

/**
 * TikTok or Instagram post URL, or '' for anything else.
 *
 * @param mixed $value Meta value.
 * @return array{url: string, network: string}|null
 */
function qr_minimal_store_social_post( $value ) {
	$value = trim( (string) $value );
	$host  = strtolower( (string) wp_parse_url( $value, PHP_URL_HOST ) );
	if ( 'https' !== wp_parse_url( $value, PHP_URL_SCHEME ) ) {
		return null;
	}
	if ( preg_match( '/(^|\.)tiktok\.com$/', $host ) ) {
		return array( 'url' => esc_url_raw( $value ), 'network' => 'TikTok' );
	}
	if ( preg_match( '/(^|\.)instagram\.com$/', $host ) ) {
		return array( 'url' => esc_url_raw( $value ), 'network' => 'Instagram' );
	}
	return null;
}

/**
 * Social reels: products with a "Home page reel" video uploaded to the store, or a link to
 * the TikTok/Instagram post. Nothing loads from TikTok or Instagram on the page; a post
 * link opens the post there. Up to four, in stock.
 *
 * @return array<int, array{product: WC_Product, src: string, post: array|null, hook: string, ai: bool}>
 */
function qr_minimal_store_home_reels() {
	$query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => 12,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_qr_reel_video',
					'value'   => '',
					'compare' => '!=',
				),
				array(
					'key'     => '_qr_reel_link',
					'value'   => '',
					'compare' => '!=',
				),
			),
			'tax_query'           => array( qr_minimal_store_visible_in_stock_tax_query() ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);
	$reels = array();
	foreach ( $query->posts as $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || ! $product->is_purchasable() ) {
			continue;
		}
		$src  = qr_minimal_store_local_video_url( $product->get_meta( '_qr_reel_video' ) );
		$post = qr_minimal_store_social_post( $product->get_meta( '_qr_reel_link' ) );
		if ( '' === $src && ! $post ) {
			continue;
		}
		$reels[] = array(
			'product' => $product,
			'src'     => $src,
			'post'    => $post,
			'hook'    => (string) $product->get_meta( '_qr_reel_hook' ),
			'ai'      => 'yes' === $product->get_meta( '_qr_reel_ai' ),
		);
		if ( count( $reels ) >= 4 ) {
			break;
		}
	}
	return $reels;
}

/**
 * Attachment ID or a URL on this site; anything else is ignored.
 *
 * @param mixed $value Meta value.
 * @return string
 */
function qr_minimal_store_local_video_url( $value ) {
	$value = trim( (string) $value );
	if ( ctype_digit( $value ) ) {
		$url = wp_get_attachment_url( (int) $value );
		return $url ? $url : '';
	}
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	return ( $value && wp_parse_url( $value, PHP_URL_HOST ) === $host ) ? esc_url_raw( $value ) : '';
}

/**
 * Product card for the home rows.
 *
 * @param WC_Product $product Product.
 */
function qr_minimal_store_home_product_card( $product ) {
	$url = $product->get_permalink();
	?>
	<li class="qr-card">
		<a class="qr-card__img" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'alt' => $product->get_name(), 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?>
		</a>
		<p class="qr-card__stock"><?php echo $product->is_on_backorder() ? esc_html__( 'Available on backorder', 'qr-minimal-store' ) : esc_html__( 'In stock · delivery in 2–3 working days', 'qr-minimal-store' ); ?></p>
		<h3 class="qr-card__name"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
		<p class="qr-card__spec"><?php echo esc_html( qr_minimal_store_product_tagline( $product ) ); ?></p>
		<p class="qr-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
		<?php if ( $product->is_type( 'simple' ) && $product->supports( 'ajax_add_to_cart' ) ) : ?>
			<a class="qr-card__add button add_to_cart_button ajax_add_to_cart" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-product_id="<?php echo esc_attr( (string) $product->get_id() ); ?>" data-quantity="1" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" aria-label="<?php echo esc_attr( $product->add_to_cart_description() ); ?>" rel="nofollow"><?php echo esc_html( $product->add_to_cart_text() ); ?></a>
		<?php else : ?>
			<a class="qr-card__add button" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Choose options', 'qr-minimal-store' ); ?></a>
		<?php endif; ?>
	</li>
	<?php
}

/**
 * Footer and home newsletter form: posts to qr-account (double opt-in), with an explicit
 * marketing consent tick box.
 */
function qr_minimal_store_newsletter_form() {
	if ( ! class_exists( 'QR_Account_Newsletter' ) ) {
		return;
	}
	$notice = isset( $_GET['nl'] ) ? sanitize_key( wp_unslash( $_GET['nl'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<form class="qr-signup__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="qr_account_newsletter">
		<?php wp_nonce_field( 'qr_account_newsletter', 'qr_account_newsletter_nonce' ); ?>
		<p class="qr-account-hp" aria-hidden="true">
			<label for="qr-signup-website"><?php esc_html_e( 'Website', 'qr-minimal-store' ); ?></label>
			<input type="text" name="qr_website" id="qr-signup-website" value="" tabindex="-1" autocomplete="off">
		</p>
		<div class="qr-signup__row">
			<label class="screen-reader-text" for="qr-signup-email"><?php esc_html_e( 'Email address', 'qr-minimal-store' ); ?></label>
			<input id="qr-signup-email" type="email" name="newsletter_email" placeholder="<?php esc_attr_e( 'you@example.co.za', 'qr-minimal-store' ); ?>" required autocomplete="email">
			<button type="submit"><?php esc_html_e( 'Sign up', 'qr-minimal-store' ); ?></button>
		</div>
		<label class="qr-signup__consent">
			<input type="checkbox" name="qr_marketing_consent" value="1" required>
			<?php esc_html_e( 'I agree to receive marketing emails from Yehuda Store.', 'qr-minimal-store' ); ?>
		</label>
		<?php if ( 'sent' === $notice ) : ?>
			<p class="qr-signup__notice" role="status"><?php esc_html_e( 'Check your inbox to confirm your subscription.', 'qr-minimal-store' ); ?></p>
		<?php elseif ( 'error' === $notice ) : ?>
			<p class="qr-signup__notice qr-signup__notice--error" role="alert"><?php esc_html_e( 'Could not subscribe. Please try again shortly.', 'qr-minimal-store' ); ?></p>
		<?php endif; ?>
	</form>
	<?php
}

/**
 * Footer "Help" links: delivery and returns policies, warranty and terms.
 *
 * @return array<int, array{label: string, url: string}>
 */
function qr_minimal_store_help_links() {
	$legal = qr_minimal_store_legal_pages();
	$rule  = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
	$links = array();
	if ( $rule ) {
		$links[] = array(
			/* translators: 1: delivery fee, 2: free delivery threshold */
			'label' => sprintf( __( 'Delivery: %1$s, free from %2$s', 'qr-minimal-store' ), qr_minimal_store_money_whole( $rule['fee'] ), qr_minimal_store_money_whole( $rule['threshold'] ) ),
			'url'   => $legal['terms']['url'],
		);
	}
	$links[] = array(
		'label' => __( 'Returns within 30 days', 'qr-minimal-store' ),
		'url'   => $legal['returns']['url'],
	);
	$links[] = array(
		'label' => __( 'Warranty', 'qr-minimal-store' ),
		'url'   => $legal['returns']['url'],
	);
	$links[] = array(
		'label' => __( 'Terms', 'qr-minimal-store' ),
		'url'   => $legal['terms']['url'],
	);
	$links[] = array(
		'label' => __( 'Privacy', 'qr-minimal-store' ),
		'url'   => $legal['privacy']['url'],
	);
	return $links;
}

add_action( 'wp_enqueue_scripts', 'qr_minimal_store_home_assets', 30 );
function qr_minimal_store_home_assets() {
	if ( ! is_front_page() ) {
		return;
	}
	$file = get_template_directory() . '/assets/js/home.js';
	wp_enqueue_script( 'qr-minimal-store-home', get_template_directory_uri() . '/assets/js/home.js', array(), file_exists( $file ) ? (string) filemtime( $file ) : wp_get_theme()->get( 'Version' ), true );
}

/* ---------- Customizer: Home page ---------- */

add_action( 'customize_register', 'qr_minimal_store_home_customizer', 20 );
function qr_minimal_store_home_customizer( $wp_customize ) {
	$wp_customize->add_section(
		'qr_home_section',
		array(
			'title'       => __( 'Home page', 'qr-minimal-store' ),
			'priority'    => 29,
			'description' => __( 'Sections only show while their products are in stock.', 'qr-minimal-store' ),
		)
	);
	$fields = array(
		'qr_home_hero_products'   => array( __( 'Hero product IDs (4, comma-separated; empty = most popular per department)', 'qr-minimal-store' ), 'text', 'sanitize_text_field', '' ),
		'qr_home_feature_product' => array( __( '3D printing feature: product ID (uses its YouTube video)', 'qr-minimal-store' ), 'number', 'absint', 0 ),
		'qr_home_feature_title'   => array( __( '3D printing feature: headline', 'qr-minimal-store' ), 'text', 'sanitize_text_field', __( 'Print in 16 colours, at home.', 'qr-minimal-store' ) ),
		'qr_home_feature_text'    => array( __( '3D printing feature: text', 'qr-minimal-store' ), 'textarea', 'sanitize_textarea_field', __( 'The Kobra S1 Ace 2 Pro Combo prints up to 16 colours with the included ACE 2 Pro, up to 600 mm/s, fully enclosed with AI print monitoring. Plus resin printers, filament and spares.', 'qr-minimal-store' ) ),
		'qr_social_tiktok'        => array( __( 'TikTok profile URL', 'qr-minimal-store' ), 'url', 'esc_url_raw', 'https://www.tiktok.com/@yehuda_solutions' ),
		'qr_social_instagram'     => array( __( 'Instagram profile URL', 'qr-minimal-store' ), 'url', 'esc_url_raw', 'https://www.instagram.com/yehuda_store22/' ),
	);
	foreach ( $fields as $id => $field ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $field[3],
				'sanitize_callback' => $field[2],
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				'label'   => $field[0],
				'section' => 'qr_home_section',
				'type'    => $field[1],
			)
		);
	}
}

/* ---------- Product fields: tagline and reel ---------- */

add_action( 'woocommerce_product_options_general_product_data', 'qr_minimal_store_home_product_fields' );
function qr_minimal_store_home_product_fields() {
	echo '<div class="options_group">';
	woocommerce_wp_text_input(
		array(
			'id'          => '_qr_tagline',
			'label'       => __( 'Home page tagline', 'qr-minimal-store' ),
			'description' => __( 'One short line for product cards, e.g. "Up to 80% less oil".', 'qr-minimal-store' ),
			'desc_tip'    => true,
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'          => '_qr_reel_video',
			'label'       => __( 'Home page reel (video)', 'qr-minimal-store' ),
			'description' => __( 'Media library ID or URL of an MP4 uploaded to this store (9:16). Shown under "See them in action".', 'qr-minimal-store' ),
			'desc_tip'    => true,
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'          => '_qr_reel_link',
			'label'       => __( 'Home page reel (TikTok/Instagram post)', 'qr-minimal-store' ),
			'description' => __( 'Link to the TikTok or Instagram post about this product. Used when no reel video is uploaded; opens the post in a new tab.', 'qr-minimal-store' ),
			'desc_tip'    => true,
			'type'        => 'url',
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'    => '_qr_reel_hook',
			'label' => __( 'Reel hook text', 'qr-minimal-store' ),
		)
	);
	woocommerce_wp_checkbox(
		array(
			'id'          => '_qr_reel_ai',
			'label'       => __( 'Reel is AI-generated', 'qr-minimal-store' ),
			'description' => __( 'Shows an "AI-generated" label on the reel.', 'qr-minimal-store' ),
		)
	);
	echo '</div>';
}

add_action( 'woocommerce_admin_process_product_object', 'qr_minimal_store_save_home_product_fields' );
function qr_minimal_store_save_home_product_fields( $product ) {
	// Nonce and capability are checked by WooCommerce before this action runs.
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	foreach ( array( '_qr_tagline', '_qr_reel_video', '_qr_reel_hook' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$product->update_meta_data( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
	if ( isset( $_POST['_qr_reel_link'] ) ) {
		$post = qr_minimal_store_social_post( wp_unslash( $_POST['_qr_reel_link'] ) );
		$product->update_meta_data( '_qr_reel_link', $post ? $post['url'] : '' );
	}
	$product->update_meta_data( '_qr_reel_ai', isset( $_POST['_qr_reel_ai'] ) ? 'yes' : 'no' );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}
