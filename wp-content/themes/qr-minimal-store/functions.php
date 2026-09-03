<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'qr_minimal_store_setup' );
function qr_minimal_store_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
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

add_action( 'wp_enqueue_scripts', 'qr_minimal_store_enqueue_assets' );
function qr_minimal_store_enqueue_assets() {
	$css_file = get_template_directory() . '/assets/css/site.css';
	$version  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'qr-minimal-store',
		get_template_directory_uri() . '/assets/css/site.css',
		array(),
		$version
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

function qr_minimal_store_get_top_product_categories( $limit = 8 ) {
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

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	return $terms;
}

function qr_minimal_store_render_category_links( $limit = 8 ) {
	$terms = qr_minimal_store_get_top_product_categories( $limit );
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
		<button type="submit"><?php esc_html_e( 'Search', 'qr-minimal-store' ); ?></button>
		<input type="hidden" name="post_type" value="product">
	</form>
	<?php
}

