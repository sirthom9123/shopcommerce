<?php
/**
 * 3D Printing department (design board B): category tree, the /3d-printing/ landing page,
 * product videos (official YouTube, click to load after marketing consent) and backorder
 * lines in the cart.
 *
 * @package qr-minimal-store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 3D Printing sub-categories: slug => [name, description].
 *
 * @return array<string, array{0: string, 1: string}>
 */
function qr_minimal_store_3d_categories() {
	return array(
		'fdm-printers'   => array( __( 'FDM Printers', 'qr-minimal-store' ), __( 'Filament printers for everyday, multi-colour and large prints.', 'qr-minimal-store' ) ),
		'resin-printers' => array( __( 'Resin Printers', 'qr-minimal-store' ), __( 'Resin (MSLA) printers for miniatures and very fine detail.', 'qr-minimal-store' ) ),
		'wash-and-cure'  => array( __( 'Wash and Cure', 'qr-minimal-store' ), __( 'Wash and cure stations for finishing resin prints.', 'qr-minimal-store' ) ),
		'filament'       => array( __( 'Filament', 'qr-minimal-store' ), __( 'PLA, PLA+, PETG and silk filament. Pick the colour on the product page.', 'qr-minimal-store' ) ),
		'resin'          => array( __( 'Resin', 'qr-minimal-store' ), __( 'Standard and water-washable resin. Pick the colour on the product page.', 'qr-minimal-store' ) ),
		'spares'         => array( __( 'Spares', 'qr-minimal-store' ), __( 'Nozzles, build plates and spare parts.', 'qr-minimal-store' ) ),
	);
}

/**
 * Create the 3D Printing category tree once (wp-admin, shop managers). Existing categories
 * with these slugs are left as they are.
 */
add_action( 'admin_init', 'qr_minimal_store_ensure_3d_categories' );
function qr_minimal_store_ensure_3d_categories() {
	if ( '1' === get_option( 'qr_minimal_store_3d_categories' ) || ! current_user_can( 'manage_product_terms' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	$parent = term_exists( '3d-printing', 'product_cat' );
	if ( ! $parent ) {
		$parent = wp_insert_term(
			__( '3D Printing', 'qr-minimal-store' ),
			'product_cat',
			array(
				'slug'        => '3d-printing',
				'description' => __( 'Anycubic FDM and resin printers, filament, resin and spares, from an authorised South African distributor.', 'qr-minimal-store' ),
			)
		);
	}
	if ( is_wp_error( $parent ) ) {
		return;
	}
	$parent_id = (int) $parent['term_id'];
	foreach ( qr_minimal_store_3d_categories() as $slug => $category ) {
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
	update_option( 'qr_minimal_store_3d_categories', '1', false );
}

/* ---------- /3d-printing/ landing page ---------- */

add_action( 'init', 'qr_minimal_store_3d_rewrite' );
function qr_minimal_store_3d_rewrite() {
	add_rewrite_rule( '^3d-printing/?$', 'index.php?product_cat=3d-printing', 'top' );
	if ( get_option( 'qr_minimal_store_3d_rewrite' ) !== '1' ) {
		flush_rewrite_rules( false );
		update_option( 'qr_minimal_store_3d_rewrite', '1', false );
	}
}

add_filter( 'qr_minimal_store_department_url', 'qr_minimal_store_3d_department_url', 10, 2 );
function qr_minimal_store_3d_department_url( $url, $key ) {
	return '3d-printing' === $key && term_exists( '3d-printing', 'product_cat' ) ? home_url( '/3d-printing/' ) : $url;
}

/**
 * The landing page replaces the plain 3D Printing category listing; filtered, sorted and
 * paged views stay normal listings.
 */
function qr_minimal_store_is_3d_landing() {
	if ( ! is_tax( 'product_cat', '3d-printing' ) || is_paged() ) {
		return false;
	}
	foreach ( array( 'stock', 'price', 'brand', 'sort', 'qr_department' ) as $param ) {
		if ( isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}
	}
	return true;
}

add_action( 'template_redirect', 'qr_minimal_store_3d_canonical' );
function qr_minimal_store_3d_canonical() {
	if ( ! qr_minimal_store_is_3d_landing() || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
	if ( false !== strpos( $path, '/product-category/' ) ) {
		wp_safe_redirect( home_url( '/3d-printing/' ), 301 );
		exit;
	}
}

// Offer bar on the 3D Printing page (design board B).
add_filter( 'qr_minimal_store_offer_lines', 'qr_minimal_store_3d_offer_lines' );
function qr_minimal_store_3d_offer_lines( $lines ) {
	if ( ! qr_minimal_store_is_3d_landing() ) {
		return $lines;
	}
	$rule     = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
	$printers = qr_minimal_store_3d_printers();
	$all_free = $rule && $printers && min( array_map( static fn( $p ) => (float) wc_get_price_to_display( $p ), $printers ) ) >= $rule['threshold'];
	if ( $all_free ) {
		$lines[0] = array(
			'strong' => __( 'Free delivery', 'qr-minimal-store' ),
			/* translators: %s: free delivery threshold */
			'rest'   => sprintf( __( ' on every printer (orders from %s)', 'qr-minimal-store' ), qr_minimal_store_money_whole( $rule['threshold'] ) ),
			'short'  => true,
		);
	}
	$lines[1] = array(
		'strong' => __( 'Backorder', 'qr-minimal-store' ),
		'rest'   => __( ' anything that is not on the shelf', 'qr-minimal-store' ),
		'short'  => true,
	);
	return $lines;
}

/**
 * Slugs of the 3D categories that exist.
 *
 * @param string[] $slugs Slugs.
 * @return string[]
 */
function qr_minimal_store_existing_cats( $slugs ) {
	return array_values( array_filter( $slugs, static fn( $slug ) => (bool) term_exists( $slug, 'product_cat' ) ) );
}

/**
 * Buyable products (in stock or backorder) of the given categories.
 *
 * @param string[] $slugs Category slugs.
 * @param int      $limit Max products.
 * @return WC_Product[]
 */
function qr_minimal_store_3d_products( $slugs, $limit = 12 ) {
	$slugs = qr_minimal_store_existing_cats( $slugs );
	if ( ! $slugs ) {
		return array();
	}
	$query    = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'fields'              => 'ids',
			'orderby'             => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
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
	$products = array();
	foreach ( $query->posts as $id ) {
		$product = wc_get_product( $id );
		if ( $product && $product->is_purchasable() && $product->is_in_stock() ) {
			$products[] = $product;
		}
	}
	return $products;
}

/**
 * Printers: in stock first, then the highest price (flagships first).
 *
 * @return WC_Product[]
 */
function qr_minimal_store_3d_printers() {
	static $printers = null;
	if ( null === $printers ) {
		$printers = qr_minimal_store_3d_products( array( 'fdm-printers', 'resin-printers' ), 12 );
		usort(
			$printers,
			static function ( $a, $b ) {
				$stock = (int) $a->is_on_backorder() - (int) $b->is_on_backorder();
				return $stock ? $stock : (float) wc_get_price_to_display( $b ) <=> (float) wc_get_price_to_display( $a );
			}
		);
	}
	return $printers;
}

/**
 * Card label above a printer name: "Card label" field, else "FDM" / "Resin".
 *
 * @param WC_Product $product Product.
 * @return string
 */
function qr_minimal_store_3d_card_label( $product ) {
	$label = trim( (string) $product->get_meta( '_qr_card_label' ) );
	if ( '' !== $label ) {
		return $label;
	}
	return has_term( 'resin-printers', 'product_cat', $product->get_id() ) ? __( 'Resin', 'qr-minimal-store' ) : __( 'FDM', 'qr-minimal-store' );
}

/**
 * Two-line blurb: "Home page tagline", else the short description.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function qr_minimal_store_3d_blurb( $product ) {
	$tagline = trim( (string) $product->get_meta( '_qr_tagline' ) );
	return '' !== $tagline ? $tagline : wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 22, '…' );
}

/**
 * Printer card (board B).
 *
 * @param WC_Product $product Product.
 */
function qr_minimal_store_3d_printer_card( $product ) {
	$url       = $product->get_permalink();
	$backorder = $product->is_on_backorder();
	?>
	<li class="qr-card qr-printer product">
		<a class="qr-card__img" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $product->get_image( 'woocommerce_single', array( 'alt' => $product->get_name(), 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML. ?>
		</a>
		<p class="qr-printer__meta">
			<span class="qr-printer__type"><?php echo esc_html( qr_minimal_store_3d_card_label( $product ) ); ?></span>
			<span class="qr-printer__badge<?php echo $backorder ? ' is-backorder' : ''; ?>"><?php echo $backorder ? esc_html__( 'Backorder', 'qr-minimal-store' ) : esc_html__( 'In stock', 'qr-minimal-store' ); ?></span>
		</p>
		<h3 class="qr-card__name qr-printer__name"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
		<p class="qr-printer__blurb"><?php echo esc_html( qr_minimal_store_3d_blurb( $product ) ); ?></p>
		<p class="qr-printer__price"><span><?php echo wp_kses_post( $product->get_price_html() ); ?></span> <?php esc_html_e( 'or pay monthly*', 'qr-minimal-store' ); ?></p>
		<?php if ( $product->is_type( 'simple' ) && $product->supports( 'ajax_add_to_cart' ) ) : ?>
			<a class="qr-card__add button add_to_cart_button ajax_add_to_cart" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-product_id="<?php echo esc_attr( (string) $product->get_id() ); ?>" data-quantity="1" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" aria-label="<?php echo esc_attr( $product->add_to_cart_description() ); ?>" rel="nofollow"><?php echo esc_html( $backorder ? __( 'Backorder now', 'qr-minimal-store' ) : __( 'Add to cart', 'qr-minimal-store' ) ); ?></a>
		<?php else : ?>
			<a class="qr-card__add button" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Choose options', 'qr-minimal-store' ); ?><span class="screen-reader-text">: <?php echo esc_html( $product->get_name() ); ?></span></a>
		<?php endif; ?>
	</li>
	<?php
}

/**
 * Colour count of a variable material (buyable variations), or 0.
 *
 * @param WC_Product $product Product.
 * @return int
 */
function qr_minimal_store_colour_count( $product ) {
	if ( ! $product->is_type( 'variable' ) ) {
		return 0;
	}
	return count(
		array_filter(
			$product->get_children(),
			static function ( $id ) {
				$variation = wc_get_product( $id );
				return $variation && $variation->is_purchasable() && $variation->is_in_stock();
			}
		)
	);
}

/**
 * Hero video: the home feature product when it is a 3D product with a video, else the first
 * printer with one.
 *
 * @return WC_Product|null
 */
function qr_minimal_store_3d_video_product() {
	$candidates = qr_minimal_store_3d_printers();
	$feature    = absint( get_theme_mod( 'qr_home_feature_product', 0 ) );
	if ( $feature ) {
		$product = wc_get_product( $feature );
		if ( $product && has_term( qr_minimal_store_existing_cats( array_merge( array( '3d-printing' ), array_keys( qr_minimal_store_3d_categories() ) ) ), 'product_cat', $product->get_id() ) ) {
			array_unshift( $candidates, $product );
		}
	}
	foreach ( $candidates as $product ) {
		if ( qr_minimal_store_youtube_id( $product->get_meta( '_product_video_youtube' ) ) ) {
			return $product;
		}
	}
	return null;
}

/**
 * Hero video of the 3D Printing page: Customizer → Home page → "3D Printing hero video"; empty = the first
 * printer's own video.
 *
 * @return string YouTube ID or "".
 */
function qr_minimal_store_3d_hero_video_id() {
	return qr_minimal_store_youtube_id( get_theme_mod( 'qr_3d_hero_video', 'OGyNYgarPyQ' ) );
}

add_action( 'customize_register', 'qr_minimal_store_3d_customizer', 20 );
function qr_minimal_store_3d_customizer( $wp_customize ) {
	$wp_customize->add_setting(
		'qr_3d_hero_video',
		array(
			'default'           => 'OGyNYgarPyQ',
			'sanitize_callback' => 'qr_minimal_store_youtube_id',
		)
	);
	$wp_customize->add_control(
		'qr_3d_hero_video',
		array(
			'section'     => 'qr_home_section',
			'label'       => __( '3D Printing page: hero video', 'qr-minimal-store' ),
			'description' => __( 'YouTube link or ID of an official Anycubic video. Plays muted, without controls, once the shopper allows marketing cookies.', 'qr-minimal-store' ),
			'type'        => 'text',
		)
	);
}

/* ---------- Product videos ---------- */

/**
 * YouTube video ID from an ID or a youtube.com / youtu.be link.
 *
 * @param mixed $value Meta value.
 * @return string
 */
function qr_minimal_store_youtube_id( $value ) {
	$value = trim( (string) $value );
	if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $value ) ) {
		return $value;
	}
	if ( preg_match( '~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', $value, $m ) ) {
		return $m[1];
	}
	return '';
}

add_filter( 'qr_minimal_store_product_video_html', 'qr_minimal_store_product_video', 10, 2 );
function qr_minimal_store_product_video( $html, $product ) {
	if ( '' !== $html || ! $product instanceof WC_Product ) {
		return $html;
	}
	$id = qr_minimal_store_youtube_id( $product->get_meta( '_product_video_youtube' ) );
	/* translators: %s: product name */
	return $id ? qr_minimal_store_youtube_html( $id, sprintf( __( '%s: official video', 'qr-minimal-store' ), $product->get_name() ), $product->get_image_id() ) : '';
}

add_action( 'wp_enqueue_scripts', 'qr_minimal_store_register_video_script', 5 );
function qr_minimal_store_register_video_script() {
	$file = get_template_directory() . '/assets/js/video.js';
	wp_register_script( 'qr-minimal-store-video', get_template_directory_uri() . '/assets/js/video.js', array(), file_exists( $file ) ? (string) filemtime( $file ) : wp_get_theme()->get( 'Version' ), true );
}

/* ---------- Cart: backorder line ---------- */

add_filter( 'woocommerce_get_item_data', 'qr_minimal_store_cart_item_backorder', 11, 2 );
function qr_minimal_store_cart_item_backorder( $item_data, $cart_item ) {
	$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
	if ( ! $product instanceof WC_Product || ! $product->is_in_stock() || ! $product->is_on_backorder( isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1 ) || ! function_exists( 'qr_minimal_store_backorder_info' ) ) {
		return $item_data;
	}
	$info = qr_minimal_store_backorder_info( $product );
	if ( '' !== $info['eta'] ) {
		/* translators: %s: supplier's expected stock date */
		$value = sprintf( __( 'Ships when the supplier delivers (expected %s), then 2–3 working days', 'qr-minimal-store' ), $info['eta'] );
	} else {
		$value = __( 'Ships when the supplier delivers; lead time confirmed after you order', 'qr-minimal-store' );
	}
	$item_data[] = array(
		'key'     => __( 'Backorder', 'qr-minimal-store' ),
		'value'   => $value,
		'display' => esc_html( $value ),
	);
	return $item_data;
}
