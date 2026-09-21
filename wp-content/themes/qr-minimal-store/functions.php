<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ONE-TIME FIX: Add wc_post_id_ prefix to CTX Feed product IDs.
 * This runs once on the next page load, then self-deletes.
 * Safe to remove this block after it has run.
 */
add_action( 'init', function() {
	if ( get_transient( 'ctx_feed_prefix_fix_done' ) ) {
		return;
	}
	$option_name = 'wf_feed_yehuda-store-facebook-catalog';
	$feed = get_option( $option_name );
	if ( $feed && isset( $feed['feedrules']['prefix'] ) ) {
		$feed['feedrules']['prefix'][0] = 'wc_post_id_';  // Product Id (id)
		$feed['feedrules']['prefix'][3] = 'wc_post_id_';  // Item Group Id (item_group_id)
		update_option( $option_name, $feed );
		set_transient( 'ctx_feed_prefix_fix_done', 1, DAY_IN_SECONDS );
	}
} );

add_action( 'after_setup_theme', 'qr_minimal_store_setup' );
function qr_minimal_store_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 320,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'qr-minimal-store' ),
			'footer'  => __( 'Footer Menu', 'qr-minimal-store' ),
		)
	);
}

/**
 * Brand asset files shipped with the theme.
 *
 * @return array<string, string>
 */
function qr_minimal_store_brand_files() {
	return array(
		'favicon'   => 'favicon.png',
		'icon-dark' => 'icon-dark.png',
		'monogram'  => 'monogram.png',
		'wordmark'  => 'wordmark.png',
	);
}

/**
 * Absolute URL for a theme brand asset.
 *
 * @param string $key Asset key.
 * @return string
 */
function qr_minimal_store_brand_url( $key ) {
	$files = qr_minimal_store_brand_files();
	if ( ! isset( $files[ $key ] ) ) {
		return '';
	}

	return get_template_directory_uri() . '/assets/images/brand/' . $files[ $key ];
}

/**
 * Print a brand <img> tag.
 *
 * @param string               $key   Asset key.
 * @param string               $class CSS class.
 * @param string               $alt   Alt text.
 * @param array<string,string> $extra Extra attributes.
 */
function qr_minimal_store_brand_img( $key, $class, $alt = '', $extra = array() ) {
	$files = qr_minimal_store_brand_files();
	if ( ! isset( $files[ $key ] ) ) {
		return;
	}

	$path = get_template_directory() . '/assets/images/brand/' . $files[ $key ];
	if ( ! file_exists( $path ) ) {
		return;
	}

	$display = array(
		'favicon'   => array( 36, 36 ),
		'icon-dark' => array( 36, 36 ),
		'monogram'  => array( 36, 36 ),
		'wordmark'  => array( 213, 28 ),
	);
	$dims = isset( $display[ $key ] ) ? $display[ $key ] : array( '', '' );

	$atts = array(
		'class'    => $class,
		'src'      => qr_minimal_store_brand_url( $key ),
		'alt'      => $alt,
		'width'    => (string) $dims[0],
		'height'   => (string) $dims[1],
		'decoding' => 'async',
	);

	foreach ( $extra as $name => $value ) {
		$atts[ $name ] = $value;
	}

	$html = '<img';
	foreach ( $atts as $name => $value ) {
		if ( '' === $value && 'alt' !== $name ) {
			continue;
		}
		$html .= ' ' . $name . '="' . ( 'src' === $name ? esc_url( $value ) : esc_attr( $value ) ) . '"';
	}
	$html .= '>';

	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes escaped above.
}

/**
 * Header/footer brand lockup: blue mark + wordmark, or Customizer logo.
 *
 * @param string $variant header|footer.
 */
function qr_minimal_store_the_brand( $variant = 'header' ) {
	$class = 'footer' === $variant ? 'brand brand--footer' : 'brand';
	$name  = get_bloginfo( 'name' );
	$extra = 'header' === $variant ? array( 'fetchpriority' => 'high' ) : array();
	?>
	<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php
		if ( has_custom_logo() ) {
			$logo_id = (int) get_theme_mod( 'custom_logo' );
			echo wp_get_attachment_image(
				$logo_id,
				'full',
				false,
				array(
					'class'    => 'custom-logo brand__wordmark',
					'alt'      => $name,
					'decoding' => 'async',
				)
			);
		} else {
			qr_minimal_store_brand_img( 'favicon', 'brand__mark', $name, $extra );
			qr_minimal_store_brand_img( 'wordmark', 'brand__wordmark', $name, $extra );
		}
		?>
	</a>
	<?php
}

add_action( 'admin_head', 'wp_site_icon', 99 );
add_filter( 'get_site_icon_url', 'qr_minimal_store_site_icon_url', 10, 1 );
function qr_minimal_store_site_icon_url( $url ) {
	if ( $url ) {
		return $url;
	}

	$icon = qr_minimal_store_brand_url( 'favicon' );
	return $icon ? $icon : $url;
}

add_action( 'login_enqueue_scripts', 'qr_minimal_store_login_logo' );
function qr_minimal_store_login_logo() {
	$icon = qr_minimal_store_brand_url( 'favicon' );
	if ( '' === $icon ) {
		return;
	}

	$css = sprintf(
		'.login h1 a { background-image: url(%s) !important; background-size: 84px 84px; width: 84px; height: 84px; }',
		esc_url( $icon )
	);
	wp_add_inline_style( 'login', $css );
}

add_filter( 'login_headerurl', 'qr_minimal_store_login_header_url' );
function qr_minimal_store_login_header_url() {
	return home_url( '/' );
}

add_filter( 'login_headertext', 'qr_minimal_store_login_header_text' );
function qr_minimal_store_login_header_text() {
	return get_bloginfo( 'name' );
}

add_filter( 'option_woocommerce_email_header_image', 'qr_minimal_store_email_header_image' );
function qr_minimal_store_email_header_image( $value ) {
	if ( ! empty( $value ) ) {
		return $value;
	}

	$wordmark = qr_minimal_store_brand_url( 'wordmark' );
	return $wordmark ? $wordmark : $value;
}

add_action( 'init', 'qr_minimal_store_ensure_primary_menu' );
function qr_minimal_store_ensure_primary_menu() {
	$locations = get_theme_mod( 'nav_menu_locations' );
	if ( ! is_array( $locations ) ) {
		$locations = array();
	}

	if ( ! empty( $locations['primary'] ) && is_nav_menu( (int) $locations['primary'] ) ) {
		return;
	}

	$menu_name = 'Primary';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $menu_name );

	if ( $menu_id < 1 ) {
		return;
	}

	$existing_items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $existing_items ) ) {
		$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
		$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
		$links       = array(
			array(
				'title' => __( 'Home', 'qr-minimal-store' ),
				'url'   => home_url( '/' ),
			),
			array(
				'title' => __( 'Shop', 'qr-minimal-store' ),
				'url'   => $shop_url,
			),
			array(
				'title' => __( 'My Account', 'qr-minimal-store' ),
				'url'   => $account_url,
			),
		);

		foreach ( $links as $index => $link ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => $link['title'],
					'menu-item-url'    => $link['url'],
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
					'menu-item-position' => $index + 1,
				)
			);
		}
	}

	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

add_action( 'init', 'qr_minimal_store_ensure_footer_menu' );
function qr_minimal_store_ensure_footer_menu() {
	$locations = get_theme_mod( 'nav_menu_locations' );
	if ( ! is_array( $locations ) ) {
		$locations = array();
	}

	if ( ! empty( $locations['footer'] ) && is_nav_menu( (int) $locations['footer'] ) ) {
		return;
	}

	$menu_name = 'Footer';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $menu_name );

	if ( $menu_id < 1 ) {
		return;
	}

	$existing_items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $existing_items ) ) {
		$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
		$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
		$cart_url    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
		$links       = array(
			array(
				'title' => __( 'Home', 'qr-minimal-store' ),
				'url'   => home_url( '/' ),
			),
			array(
				'title' => __( 'Shop', 'qr-minimal-store' ),
				'url'   => $shop_url,
			),
			array(
				'title' => __( 'Cart', 'qr-minimal-store' ),
				'url'   => $cart_url,
			),
			array(
				'title' => __( 'My Account', 'qr-minimal-store' ),
				'url'   => $account_url,
			),
		);

		foreach ( $links as $index => $link ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'    => $link['title'],
					'menu-item-url'      => $link['url'],
					'menu-item-status'   => 'publish',
					'menu-item-type'     => 'custom',
					'menu-item-position' => $index + 1,
				)
			);
		}
	}

	$locations['footer'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

add_action( 'init', 'qr_minimal_store_ensure_spring_bonanza_page', 20 );
/**
 * Publish the campaign landing page and assign its template.
 * Theme files alone do not create /spring-bonanza/ — WordPress needs a Page post.
 */
function qr_minimal_store_ensure_spring_bonanza_page() {
	if ( get_option( 'qr_minimal_store_bonanza_ready' ) === '1' ) {
		if ( get_option( 'qr_minimal_store_bonanza_flush' ) ) {
			flush_rewrite_rules( false );
			delete_option( 'qr_minimal_store_bonanza_flush' );
		}
		return;
	}

	if ( ! is_readable( get_theme_file_path( 'page-spring-bonanza.php' ) ) ) {
		return;
	}

	$page = get_page_by_path( 'spring-bonanza' );
	if ( ! $page instanceof WP_Post ) {
		$found = get_posts(
			array(
				'post_type'      => 'page',
				'title'          => 'Spring Bonanza',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
			)
		);
		$page = ! empty( $found ) ? $found[0] : null;
	}

	if ( $page instanceof WP_Post ) {
		$id = wp_update_post(
			array(
				'ID'          => (int) $page->ID,
				'post_name'   => 'spring-bonanza',
				'post_status' => 'publish',
				'post_type'   => 'page',
			),
			true
		);
	} else {
		$id = wp_insert_post(
			array(
				'post_title'   => 'Spring Bonanza',
				'post_name'    => 'spring-bonanza',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
	}

	if ( is_wp_error( $id ) || (int) $id < 1 ) {
		return;
	}

	update_post_meta( (int) $id, '_wp_page_template', 'page-spring-bonanza.php' );
	update_option( 'qr_minimal_store_bonanza_ready', '1' );
	update_option( 'qr_minimal_store_bonanza_flush', 1 );
}

/**
 * Visible product IDs from the active qr-promos sale, else WooCommerce on-sale products.
 *
 * @return int[]
 */
function qr_minimal_store_promo_product_ids() {
	$ids = array();

	if ( class_exists( 'QR_Promos' ) ) {
		$promo = QR_Promos::get_active_promo();
		if ( $promo ) {
			$meta = QR_Promos::get_promo_meta( $promo->ID );
			$ids  = $meta['product_ids'];
			if ( $meta['featured'] ) {
				$ids = array_merge( array( $meta['featured'] ), $ids );
			}
		}
	}

	if ( empty( $ids ) && function_exists( 'wc_get_product_ids_on_sale' ) ) {
		$ids = wc_get_product_ids_on_sale();
	}

	$visible = array();
	foreach ( $ids as $id ) {
		$id = absint( $id );
		if ( $id < 1 || in_array( $id, $visible, true ) ) {
			continue;
		}
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}
		$visible[] = $id;
	}

	return $visible;
}

/**
 * Unix timestamp for the active promo end, or 0.
 *
 * @return int
 */
function qr_minimal_store_active_promo_end() {
	if ( ! class_exists( 'QR_Promos' ) ) {
		return 0;
	}
	$promo = QR_Promos::get_active_promo();
	if ( ! $promo ) {
		return 0;
	}
	$meta = QR_Promos::get_promo_meta( $promo->ID );
	return (int) $meta['end'];
}

/**
 * Render a WooCommerce product grid for specific IDs, preserving promo order.
 *
 * @param int[] $ids     Product IDs.
 * @param int   $columns Grid columns.
 */
function qr_minimal_store_render_product_ids( $ids, $columns = 4 ) {
	$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
	if ( empty( $ids ) || ! function_exists( 'WC' ) ) {
		return;
	}

	echo do_shortcode(
		sprintf(
			'[products ids="%s" columns="%d" limit="%d" orderby="post__in"]',
			esc_attr( implode( ',', $ids ) ),
			max( 1, (int) $columns ),
			count( $ids )
		)
	);
}

add_filter( 'wp_nav_menu_objects', 'qr_minimal_store_footer_omit_sample_page', 10, 2 );
function qr_minimal_store_footer_omit_sample_page( $items, $args ) {
	if ( empty( $args->theme_location ) || 'footer' !== $args->theme_location || empty( $items ) ) {
		return $items;
	}

	$filtered = array();
	foreach ( $items as $item ) {
		$slug  = isset( $item->object_id ) ? (string) get_post_field( 'post_name', (int) $item->object_id ) : '';
		$title = isset( $item->title ) ? (string) $item->title : '';
		$url   = isset( $item->url ) ? (string) $item->url : '';
		if ( 'sample-page' === $slug || 0 === strcasecmp( $title, 'Sample Page' ) || false !== strpos( $url, '/sample-page' ) ) {
			continue;
		}
		$filtered[] = $item;
	}

	return $filtered;
}

function qr_minimal_store_primary_menu_fallback() {
	$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	?>
	<ul class="site-nav__menu">
		<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'qr-minimal-store' ); ?></a></li>
		<li><a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop', 'qr-minimal-store' ); ?></a></li>
		<li><a href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'My Account', 'qr-minimal-store' ); ?></a></li>
	</ul>
	<?php
}

function qr_minimal_store_footer_menu_fallback() {
	$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	$cart_url    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
	?>
	<ul class="footer-nav">
		<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'qr-minimal-store' ); ?></a></li>
		<li><a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop', 'qr-minimal-store' ); ?></a></li>
		<li><a href="<?php echo esc_url( $cart_url ); ?>"><?php esc_html_e( 'Cart', 'qr-minimal-store' ); ?></a></li>
		<li><a href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'My Account', 'qr-minimal-store' ); ?></a></li>
	</ul>
	<?php
}

/**
 * Published legal pages used in the footer and layout.
 *
 * @return array<string, array{title: string, url: string, id: int}>
 */
function qr_minimal_store_legal_pages() {
	$map = array(
		'terms'   => array(
			'title'  => __( 'Platform Terms', 'qr-minimal-store' ),
			'option' => 'woocommerce_terms_page_id',
			'slug'   => 'platform-terms',
		),
		'returns' => array(
			'title'  => __( 'Returns Policy', 'qr-minimal-store' ),
			'option' => 'woocommerce_refund_returns_page_id',
			'slug'   => 'returns-policy',
		),
		'privacy' => array(
			'title'  => __( 'Privacy Policy', 'qr-minimal-store' ),
			'option' => 'wp_page_for_privacy_policy',
			'slug'   => 'privacy-policy',
		),
	);

	$pages = array();
	foreach ( $map as $key => $item ) {
		$id  = (int) get_option( $item['option'] );
		$url = '';
		if ( $id && 'publish' === get_post_status( $id ) ) {
			$url = get_permalink( $id );
		} else {
			$page = get_page_by_path( $item['slug'] );
			if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
				$id  = (int) $page->ID;
				$url = get_permalink( $page );
			}
		}
		if ( ! $url ) {
			$url = home_url( '/' . $item['slug'] . '/' );
		}
		$pages[ $key ] = array(
			'title' => $item['title'],
			'url'   => $url,
			'id'    => $id,
		);
	}

	return $pages;
}

add_filter( 'body_class', 'qr_minimal_store_legal_body_class' );
function qr_minimal_store_legal_body_class( $classes ) {
	if ( ! is_page() ) {
		return $classes;
	}

	$id = get_queried_object_id();
	foreach ( qr_minimal_store_legal_pages() as $page ) {
		if ( $page['id'] && $page['id'] === $id ) {
			$classes[] = 'legal-page';
			break;
		}
	}

	return $classes;
}

add_action( 'wp_enqueue_scripts', 'qr_minimal_store_enqueue_assets', 20 );
function qr_minimal_store_enqueue_assets() {
	$css_file = get_template_directory() . '/assets/css/site.css';
	$version  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'qr-minimal-store-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap',
		array(),
		null
	);

	$style_deps = array( 'qr-minimal-store-fonts' );
	if ( wp_style_is( 'woocommerce-general', 'registered' ) ) {
		$style_deps[] = 'woocommerce-general';
	}
	if ( wp_style_is( 'woocommerce-layout', 'registered' ) ) {
		$style_deps[] = 'woocommerce-layout';
	}

	wp_enqueue_style(
		'qr-minimal-store',
		get_template_directory_uri() . '/assets/css/site.css',
		$style_deps,
		$version
	);

	$nav_js = get_template_directory() . '/assets/js/nav-toggle.js';
	$nav_ver = file_exists( $nav_js ) ? (string) filemtime( $nav_js ) : wp_get_theme()->get( 'Version' );
	wp_enqueue_script(
		'qr-minimal-store-nav-toggle',
		get_template_directory_uri() . '/assets/js/nav-toggle.js',
		array(),
		$nav_ver,
		true
	);

	if ( is_front_page() ) {
		$js_file = get_template_directory() . '/assets/js/product-tabs.js';
		$js_ver  = file_exists( $js_file ) ? (string) filemtime( $js_file ) : wp_get_theme()->get( 'Version' );

		wp_enqueue_script(
			'qr-minimal-store-product-tabs',
			get_template_directory_uri() . '/assets/js/product-tabs.js',
			array(),
			$js_ver,
			true
		);

		$deal_js  = get_template_directory() . '/assets/js/deal-countdown.js';
		$deal_ver = file_exists( $deal_js ) ? (string) filemtime( $deal_js ) : wp_get_theme()->get( 'Version' );
		wp_enqueue_script(
			'qr-minimal-store-deal-countdown',
			get_template_directory_uri() . '/assets/js/deal-countdown.js',
			array(),
			$deal_ver,
			true
		);
	}

	if ( is_page_template( 'page-spring-bonanza.php' ) ) {
		$bonanza_css     = get_template_directory() . '/assets/css/spring-bonanza.css';
		$bonanza_css_ver = file_exists( $bonanza_css ) ? (string) filemtime( $bonanza_css ) : wp_get_theme()->get( 'Version' );
		wp_enqueue_style(
			'qr-spring-bonanza',
			get_template_directory_uri() . '/assets/css/spring-bonanza.css',
			array( 'qr-minimal-store' ),
			$bonanza_css_ver
		);

		$bonanza_js     = get_template_directory() . '/assets/js/spring-bonanza.js';
		$bonanza_js_ver = file_exists( $bonanza_js ) ? (string) filemtime( $bonanza_js ) : wp_get_theme()->get( 'Version' );
		wp_enqueue_script(
			'qr-spring-bonanza',
			get_template_directory_uri() . '/assets/js/spring-bonanza.js',
			array(),
			$bonanza_js_ver,
			true
		);
	}
}

add_action( 'after_setup_theme', 'qr_minimal_store_woocommerce_layout', 20 );
function qr_minimal_store_woocommerce_layout() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// Keep templates clean and full-width in the minimalist layout.
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
}

function qr_minimal_store_product_search_form() {
	if ( function_exists( 'get_product_search_form' ) ) {
		get_product_search_form();
		return;
	}

	get_search_form();
}

function qr_minimal_store_get_top_product_categories( $limit = 8, $exclude_slugs = array() ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => (int) $limit,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);


	// Filter out excluded categories by slug
	if ( ! empty( $exclude_slugs ) ) {
		$terms = array_filter( $terms, function( $term ) use ( $exclude_slugs ) {
			return ! in_array( $term->slug, $exclude_slugs, true );
		} );
	}

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	return $terms;
}

function qr_minimal_store_render_category_links( $limit = 8, $exclude_slugs = array() ) {
	$terms = qr_minimal_store_get_top_product_categories( $limit, $exclude_slugs );
	if ( empty( $terms ) ) {
		return;
	}
	?>
	<ul class="category-strip__list" aria-label="<?php esc_attr_e( 'Top product categories', 'qr-minimal-store' ); ?>">
		<?php foreach ( $terms as $term ) : ?>
			<li>
				<a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
					<?php echo esc_html( $term->name ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

function qr_minimal_store_render_category_cards( $limit = 5, $exclude_slugs = array() ) {
	$terms = qr_minimal_store_get_top_product_categories( $limit, $exclude_slugs );
	if ( empty( $terms ) ) {
		return;
	}
	?>
	<ul class="category-rail__list">
		<?php foreach ( $terms as $term ) : ?>
			<?php
			$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
			$term_link    = get_term_link( $term );
			if ( is_wp_error( $term_link ) ) {
				continue;
			}
			?>
			<li class="category-card">
				<a href="<?php echo esc_url( $term_link ); ?>">
					<span class="category-card__media">
						<?php
						if ( $thumbnail_id ) {
							echo wp_get_attachment_image( $thumbnail_id, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy' ) );
						} else {
							echo '<span class="category-card__placeholder" aria-hidden="true"></span>';
						}
						?>
					</span>
					<strong><?php echo esc_html( $term->name ); ?></strong>
					<span>
						<?php
						printf(
							esc_html( _n( '%s product', '%s products', $term->count, 'qr-minimal-store' ) ),
							esc_html( number_format_i18n( $term->count ) )
						);
						?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Resolve a product category by preferred slugs, else first available top category.
 *
 * @param string[] $slugs Preferred slugs.
 * @return WP_Term|null
 */
function qr_minimal_store_find_category( $slugs ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return null;
	}
	foreach ( (array) $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
	}
	$top = qr_minimal_store_get_top_product_categories( 1 );
	return ! empty( $top ) ? reset( $top ) : null;
}

/**
 * @param WP_Term $term Category.
 * @return string
 */
function qr_minimal_store_category_image_html( $term ) {
	$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( $thumbnail_id ) {
		return wp_get_attachment_image( $thumbnail_id, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy' ) );
	}
	if ( ! function_exists( 'wc_get_products' ) ) {
		return '';
	}
	$products = wc_get_products(
		array(
			'status'   => 'publish',
			'limit'    => 1,
			'category' => array( $term->slug ),
		)
	);
	if ( empty( $products ) ) {
		return '';
	}
	return $products[0]->get_image( 'woocommerce_thumbnail' );
}

/**
 * @param WP_Term $term Category.
 * @return string
 */
function qr_minimal_store_category_from_price( $term ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return '';
	}
	$products = wc_get_products(
		array(
			'status'   => 'publish',
			'limit'    => 1,
			'orderby'  => 'price',
			'order'    => 'ASC',
			'category' => array( $term->slug ),
		)
	);
	if ( empty( $products ) ) {
		return '';
	}
	return wp_strip_all_tags( wc_price( $products[0]->get_price() ) );
}

function qr_minimal_store_render_home_promo_banners() {
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$watches = qr_minimal_store_find_category( array( 'smart-watches-bands', 'smart-watches', 'watches' ) );
	$kitchen = qr_minimal_store_find_category( array( 'kitchen-appliances', 'kitchen', 'household-gadgets' ) );
	if ( $kitchen && $watches && (int) $kitchen->term_id === (int) $watches->term_id ) {
		$more    = qr_minimal_store_get_top_product_categories( 3 );
		$kitchen = isset( $more[1] ) ? $more[1] : $kitchen;
	}

	$watch_url = ( $watches && ! is_wp_error( get_term_link( $watches ) ) ) ? get_term_link( $watches ) : $shop;
	$kit_url   = ( $kitchen && ! is_wp_error( get_term_link( $kitchen ) ) ) ? get_term_link( $kitchen ) : $shop;
	$from      = $kitchen ? qr_minimal_store_category_from_price( $kitchen ) : '';
	$bonanza   = get_page_by_path( 'spring-bonanza' );
	$sale_url  = ( $bonanza instanceof WP_Post ) ? get_permalink( $bonanza ) : home_url( '/spring-bonanza/' );
	?>
	<section class="home-promo-banners" aria-label="<?php esc_attr_e( 'Promotions', 'qr-minimal-store' ); ?>">
		<div class="container home-promo-banners__grid">
			<article class="home-promo-banner home-promo-banner--watches">
				<div class="home-promo-banner__media">
					<?php echo $watches ? qr_minimal_store_category_image_html( $watches ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce/attachment image HTML. ?>
				</div>
				<div class="home-promo-banner__copy">
					<p><?php esc_html_e( 'Catch big deals on smart watches and bands', 'qr-minimal-store' ); ?></p>
					<a class="button home-promo-banner__cta" href="<?php echo esc_url( $watch_url ); ?>"><?php esc_html_e( 'Shop now', 'qr-minimal-store' ); ?></a>
				</div>
			</article>
			<article class="home-promo-banner home-promo-banner--kitchen">
				<div class="home-promo-banner__copy">
					<p><?php echo esc_html( $kitchen ? $kitchen->name : __( 'Kitchen and home', 'qr-minimal-store' ) ); ?></p>
					<?php if ( $from ) : ?>
						<p class="home-promo-banner__from"><?php echo esc_html( sprintf( __( 'From %s', 'qr-minimal-store' ), $from ) ); ?></p>
					<?php endif; ?>
					<a class="button home-promo-banner__cta" href="<?php echo esc_url( $kit_url ); ?>"><?php esc_html_e( 'Shop now', 'qr-minimal-store' ); ?></a>
				</div>
				<div class="home-promo-banner__media">
					<?php echo $kitchen ? qr_minimal_store_category_image_html( $kitchen ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce/attachment image HTML. ?>
				</div>
			</article>
			<article class="home-promo-banner home-promo-banner--deal">
				<div class="home-promo-banner__copy">
					<p><?php esc_html_e( 'Limited time offer', 'qr-minimal-store' ); ?></p>
					<p class="home-promo-banner__sub"><?php esc_html_e( 'Hurry — offers end when stock runs out.', 'qr-minimal-store' ); ?></p>
					<a class="button home-promo-banner__cta" href="<?php echo esc_url( $sale_url ); ?>"><?php esc_html_e( 'Shop the sale', 'qr-minimal-store' ); ?></a>
				</div>
			</article>
		</div>
	</section>
	<?php
}

function qr_minimal_store_product_search_bar() {
	$selected_category = '';
	if ( isset( $_GET['product_cat'] ) ) {
		$selected_category = sanitize_text_field( wp_unslash( $_GET['product_cat'] ) );
	}

	$search_value = '';
	if ( isset( $_GET['s'] ) ) {
		$search_value = sanitize_text_field( wp_unslash( $_GET['s'] ) );
	}

	$categories = qr_minimal_store_get_top_product_categories( 8 );
	?>
	<form role="search" method="get" class="product-search-bar" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="qr-product-search"><?php esc_html_e( 'Search products', 'qr-minimal-store' ); ?></label>
		<input id="qr-product-search" type="search" name="s" placeholder="<?php esc_attr_e( 'Search for products', 'qr-minimal-store' ); ?>" value="<?php echo esc_attr( $search_value ); ?>">
		<?php if ( ! empty( $categories ) ) : ?>
			<label class="screen-reader-text" for="qr-product-category"><?php esc_html_e( 'Product category', 'qr-minimal-store' ); ?></label>
			<select id="qr-product-category" name="product_cat">
				<option value=""><?php esc_html_e( 'All Categories', 'qr-minimal-store' ); ?></option>
				<?php foreach ( $categories as $category ) : ?>
					<option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $selected_category, $category->slug ); ?>>
						<?php echo esc_html( $category->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
		<button type="submit">
			<svg class="product-search-bar__icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
				<circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2" />
				<path d="M16.5 16.5L21 21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
			</svg>
			<span class="screen-reader-text"><?php esc_html_e( 'Search', 'qr-minimal-store' ); ?></span>
		</button>
		<input type="hidden" name="post_type" value="product">
	</form>
	<?php
}

add_action( 'customize_register', 'qr_minimal_store_customizer' );
function qr_minimal_store_customizer( $wp_customize ) {
	$wp_customize->add_section(
		'qr_hero_section',
		array(
			'title'    => __( 'Hero Banner', 'qr-minimal-store' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'qr_hero_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'qr_hero_image',
			array(
				'label'     => __( 'Hero Product Image', 'qr-minimal-store' ),
				'section'   => 'qr_hero_section',
				'mime_type' => 'image',
			)
		)
	);

	$wp_customize->add_setting(
		'qr_deal_product',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);

	$wp_customize->add_control(
		'qr_deal_product',
		array(
			'label'   => __( 'Deal of the Day — Product ID', 'qr-minimal-store' ),
			'section' => 'qr_hero_section',
			'type'    => 'number',
		)
	);
}

add_action( 'wp_footer', 'qr_minimal_store_sticky_add_to_cart' );
function qr_minimal_store_sticky_add_to_cart() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	?>
	<div class="sticky-add-to-cart" id="sticky-atc" hidden>
		<div class="container sticky-add-to-cart__inner">
			<div class="sticky-add-to-cart__info">
				<strong><?php echo esc_html( $product->get_name() ); ?></strong>
				<span><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce price HTML. ?></span>
			</div>
			<a class="button" href="#product-<?php echo esc_attr( (string) $product->get_id() ); ?>">
				<?php esc_html_e( 'Add to Cart', 'qr-minimal-store' ); ?>
			</a>
		</div>
	</div>
	<script>
	(function(){
	  var bar = document.getElementById('sticky-atc');
	  var form = document.querySelector('form.cart');
	  if (!bar || !form) return;
	  var io = new IntersectionObserver(function(entries){
	    bar.hidden = entries[0].isIntersecting;
	  }, { threshold: 0 });
	  io.observe(form);
	})();
	</script>
	<?php
}

function qr_minimal_store_trust_icon_svg( $name ) {
	$icons = array(
		'lock'   => '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><rect x="5" y="11" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 11V8a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
		'truck'  => '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="7" cy="18" r="1.7" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17.5" cy="18" r="1.7" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>',
		'return' => '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><path d="M7 7H4v3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 10a7 7 0 1 0 2-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
		'pay'    => '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><rect x="3" y="6" width="18" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M7 15h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
	);
	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

function qr_minimal_store_render_checkout_trust() {
	static $rendered = false;
	if ( $rendered ) {
		return;
	}
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}
	$rendered = true;
	$items    = array(
		array(
			'icon'  => 'lock',
			'title' => __( 'Secure transactions', 'qr-minimal-store' ),
			'text'  => __( 'Encrypted checkout with PayFast.', 'qr-minimal-store' ),
		),
		array(
			'icon'  => 'pay',
			'title' => __( 'Protected payments', 'qr-minimal-store' ),
			'text'  => __( 'Card and EFT processed by trusted gateways.', 'qr-minimal-store' ),
		),
		array(
			'icon'  => 'truck',
			'title' => __( 'Reliable delivery', 'qr-minimal-store' ),
			'text'  => __( 'Tracked shipping across South Africa.', 'qr-minimal-store' ),
		),
		array(
			'icon'  => 'return',
			'title' => __( 'Easy returns', 'qr-minimal-store' ),
			'text'  => __( '30-day returns on unused items.', 'qr-minimal-store' ),
		),
	);
	?>
	<section class="qr-checkout-trust" aria-label="<?php esc_attr_e( 'Checkout assurances', 'qr-minimal-store' ); ?>">
		<ul class="qr-checkout-trust__list">
			<?php foreach ( $items as $item ) : ?>
				<li>
					<span class="qr-checkout-trust__icon"><?php echo qr_minimal_store_trust_icon_svg( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG. ?></span>
					<span>
						<strong><?php echo esc_html( $item['title'] ); ?></strong>
						<small><?php echo esc_html( $item['text'] ); ?></small>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
}

add_action( 'woocommerce_before_cart', 'qr_minimal_store_render_checkout_trust', 5 );
add_action( 'woocommerce_before_checkout_form', 'qr_minimal_store_render_checkout_trust', 5 );
add_filter( 'render_block_woocommerce/cart', 'qr_minimal_store_prepend_checkout_trust_block', 10, 2 );
add_filter( 'render_block_woocommerce/checkout', 'qr_minimal_store_prepend_checkout_trust_block', 10, 2 );
function qr_minimal_store_prepend_checkout_trust_block( $content, $block ) {
	if ( ! is_cart() && ! is_checkout() ) {
		return $content;
	}
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return $content;
	}
	ob_start();
	qr_minimal_store_render_checkout_trust();
	return ob_get_clean() . $content;
}

/**
 * Footer Google Customer Reviews badge. PHP-rendered so it is visible locally
 * even when Google's widget script draws an empty 0×0 overlay.
 */
function qr_minimal_store_google_reviews_badge() {
	if ( class_exists( 'QR_Google_Customer_Reviews' ) ) {
		QR_Google_Customer_Reviews::instance()->badge_markup();
		return;
	}

	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	$url  = 'https://customerreviews.google.com/v/merchant?q=' . rawurlencode( (string) $host ) . '&c=ZA&v=19';
	?>
	<div id="qr-gcr-rating-badge" class="qr-gcr-rating-badge qr-gcr-rating-badge--fallback">
		<a class="qr-gcr-fallback" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
			<span class="qr-gcr-fallback__mark" aria-hidden="true">G</span>
			<span>
				<strong><?php esc_html_e( 'Google Customer Reviews', 'qr-minimal-store' ); ?></strong>
				<small><?php esc_html_e( 'Rating not available yet', 'qr-minimal-store' ); ?></small>
			</span>
		</a>
	</div>
	<?php
}

