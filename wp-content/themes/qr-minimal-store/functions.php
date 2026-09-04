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

function qr_minimal_store_render_category_cards( $limit = 5 ) {
	$terms = qr_minimal_store_get_top_product_categories( $limit );
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

