<?php
/**
 * Shop, category, brand and search listings (design board E).
 *
 * Filters live in the URL (stock, price, brand, sort) and are applied to the main product
 * query, so every filtered page is a plain cacheable URL. Option counts come from one
 * cached list of the listing's products. Sold-out products are hidden unless the shopper
 * includes them, and are always listed last.
 *
 * @package qr-minimal-store
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Price bands; the top band starts at the free-delivery threshold.
 *
 * @return array<string, array{label: string, min: float, max: float}>
 */
function qr_minimal_store_price_bands() {
	$rule = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
	$free = $rule ? (float) $rule['threshold'] : 2500.0;
	return array(
		'under-1000'                      => array(
			/* translators: %s: amount */
			'label' => sprintf( __( 'Under %s', 'qr-minimal-store' ), qr_minimal_store_money_whole( 1000 ) ),
			'min'   => 0.0,
			'max'   => 1000.0,
		),
		'1000-' . (int) $free             => array(
			/* translators: 1: amount, 2: amount */
			'label' => sprintf( __( '%1$s to %2$s', 'qr-minimal-store' ), qr_minimal_store_money_whole( 1000 ), qr_minimal_store_money_whole( $free ) ),
			'min'   => 1000.0,
			'max'   => $free,
		),
		(int) $free . '-plus'             => array(
			/* translators: %s: free delivery threshold */
			'label' => sprintf( __( '%s and up (free delivery)', 'qr-minimal-store' ), qr_minimal_store_money_whole( $free ) ),
			'min'   => $free,
			'max'   => 0.0,
		),
	);
}

/**
 * @return array<string, string> sort key => label
 */
function qr_minimal_store_sort_options() {
	return array(
		'recommended' => __( 'Recommended', 'qr-minimal-store' ),
		'price'       => __( 'Price: low to high', 'qr-minimal-store' ),
		'price-desc'  => __( 'Price: high to low', 'qr-minimal-store' ),
		'newest'      => __( 'Newest', 'qr-minimal-store' ),
	);
}

/**
 * Current filters from the URL.
 *
 * @return array{stock_all: bool, price: string[], brand: string[], sort: string}
 */
function qr_minimal_store_shop_filters() {
	static $filters = null;
	if ( null !== $filters ) {
		return $filters;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only listing filters.
	$list    = static function ( $name ) {
		if ( ! isset( $_GET[ $name ] ) ) {
			return array();
		}
		$raw = wp_unslash( $_GET[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised below.
		$raw = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
		return array_values( array_unique( array_filter( array_map( 'sanitize_title', $raw ) ) ) );
	};
	$sort    = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'recommended';
	$filters = array(
		'stock_all' => isset( $_GET['stock'] ) && 'all' === sanitize_key( wp_unslash( $_GET['stock'] ) ),
		'price'     => array_values( array_intersect( $list( 'price' ), array_keys( qr_minimal_store_price_bands() ) ) ),
		'brand'     => $list( 'brand' ),
		'sort'      => isset( qr_minimal_store_sort_options()[ $sort ] ) ? $sort : 'recommended',
	);
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	return $filters;
}

function qr_minimal_store_is_product_listing() {
	return function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) ) );
}

/* ---------- Main query ---------- */

add_filter( 'loop_shop_per_page', 'qr_minimal_store_shop_per_page', 20 );
function qr_minimal_store_shop_per_page() {
	return 24;
}

// The default sort is ours ("Recommended"); keep WooCommerce from adding its own order clauses.
add_filter( 'woocommerce_default_catalog_orderby', 'qr_minimal_store_default_orderby', 20 );
function qr_minimal_store_default_orderby() {
	return 'menu_order';
}

add_action( 'woocommerce_product_query', 'qr_minimal_store_shop_query', 20 );
function qr_minimal_store_shop_query( $q ) {
	$filters   = qr_minimal_store_shop_filters();
	$tax_query = (array) $q->get( 'tax_query' );
	$meta      = (array) $q->get( 'meta_query' );

	if ( ! $filters['stock_all'] ) {
		$tax_query[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'outofstock' ),
			'operator' => 'NOT IN',
		);
	}
	if ( $filters['brand'] && taxonomy_exists( 'product_brand' ) ) {
		$tax_query[] = array(
			'taxonomy' => 'product_brand',
			'field'    => 'slug',
			'terms'    => $filters['brand'],
		);
	}
	if ( $filters['price'] ) {
		$bands = qr_minimal_store_price_bands();
		$or    = array( 'relation' => 'OR' );
		foreach ( $filters['price'] as $key ) {
			$band = $bands[ $key ];
			$or[] = $band['max'] > 0
				? array(
					'relation' => 'AND',
					array(
						'key'     => '_price',
						'value'   => $band['min'],
						'compare' => '>=',
						'type'    => 'DECIMAL(10,2)',
					),
					array(
						'key'     => '_price',
						'value'   => $band['max'],
						'compare' => '<',
						'type'    => 'DECIMAL(10,2)',
					),
				)
				: array(
					'key'     => '_price',
					'value'   => $band['min'],
					'compare' => '>=',
					'type'    => 'DECIMAL(10,2)',
				);
		}
		$meta['qr_price_band'] = $or;
	}

	// In stock first, then backorder, then sold out (instock < onbackorder < outofstock).
	$meta['qr_stock'] = array(
		'key'     => '_stock_status',
		'compare' => 'EXISTS',
	);
	switch ( $filters['sort'] ) {
		case 'price':
		case 'price-desc':
			$meta['qr_price'] = array(
				'key'     => '_price',
				'compare' => 'EXISTS',
				'type'    => 'DECIMAL(10,2)',
			);
			$orderby          = array(
				'qr_stock' => 'ASC',
				'qr_price' => 'price' === $filters['sort'] ? 'ASC' : 'DESC',
			);
			break;
		case 'newest':
			$orderby = array(
				'qr_stock' => 'ASC',
				'date'     => 'DESC',
			);
			break;
		default:
			$meta['qr_sales'] = array(
				'key'     => 'total_sales',
				'compare' => 'EXISTS',
				'type'    => 'NUMERIC',
			);
			$orderby          = array(
				'qr_stock' => 'ASC',
				'qr_sales' => 'DESC',
				'date'     => 'DESC',
			);
	}

	$q->set( 'tax_query', $tax_query );
	$q->set( 'meta_query', $meta );
	$q->set( 'orderby', $orderby );
	$q->set( 'order', '' );
}

// Filtered and sorted variants of a listing are not separate pages for search engines.
add_filter( 'wp_robots', 'qr_minimal_store_shop_robots' );
function qr_minimal_store_shop_robots( $robots ) {
	$filters = qr_minimal_store_shop_filters();
	if ( qr_minimal_store_is_product_listing() && ( $filters['stock_all'] || $filters['price'] || $filters['brand'] || 'recommended' !== $filters['sort'] ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}

/* ---------- Listing context: breadcrumb, title, intro, chips ---------- */

/**
 * Short category name: "Peripherals / Mouse" → "Mouse".
 *
 * @param WP_Term $term Term.
 * @return string
 */
function qr_minimal_store_category_label( $term ) {
	$parts = array_map( 'trim', explode( '/', html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ) ) );
	return (string) end( $parts );
}

/**
 * "Smart Home" → "smart home", keeping words with digits ("3D Printing" → "3D printing").
 *
 * @param string $title Title.
 * @return string
 */
function qr_minimal_store_lower_title( $title ) {
	return (string) preg_replace_callback(
		'/\S+/u',
		static fn( $m ) => preg_match( '/\d/', $m[0] ) ? $m[0] : ( function_exists( 'mb_strtolower' ) ? mb_strtolower( $m[0] ) : strtolower( $m[0] ) ),
		$title
	);
}

/**
 * In-stock (or backorder) product count for categories; cached with the home data.
 *
 * @param string[] $slugs Category slugs.
 * @return int
 */
function qr_minimal_store_category_stock_count( $slugs ) {
	$key   = 'qr_cat_count_' . md5( implode( ',', $slugs ) . '|' . (int) get_option( 'qr_minimal_store_cache_gen', 0 ) );
	$count = get_transient( $key );
	if ( false !== $count ) {
		return (int) $count;
	}
	$query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $slugs,
				),
				qr_minimal_store_visible_in_stock_tax_query(),
			),
		)
	);
	$count = (int) $query->found_posts;
	set_transient( $key, $count, 30 * MINUTE_IN_SECONDS );
	return $count;
}

add_action( 'save_post_product', 'qr_minimal_store_bump_cache_gen' );
add_action( 'woocommerce_product_set_stock_status', 'qr_minimal_store_bump_cache_gen' );
add_action( 'created_product_cat', 'qr_minimal_store_bump_cache_gen' );
add_action( 'edited_product_cat', 'qr_minimal_store_bump_cache_gen' );
function qr_minimal_store_bump_cache_gen() {
	update_option( 'qr_minimal_store_cache_gen', (int) get_option( 'qr_minimal_store_cache_gen', 0 ) + 1, false );
}

/**
 * Department of the current category listing.
 *
 * @return array{key: string, department: array, whole: bool}|null whole = the listing is the whole department.
 */
function qr_minimal_store_current_department() {
	if ( ! is_tax( 'product_cat' ) ) {
		return null;
	}
	$all   = qr_minimal_store_departments();
	$slugs = array_filter( explode( ',', (string) get_query_var( 'product_cat' ) ) );
	$key   = isset( $_GET['qr_department'] ) ? sanitize_key( wp_unslash( $_GET['qr_department'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $key && isset( $all[ $key ] ) && count( $slugs ) > 1 ) {
		return array(
			'key'        => $key,
			'department' => $all[ $key ],
			'whole'      => true,
		);
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return null;
	}
	foreach ( $all as $dept_key => $department ) {
		if ( $term->slug === $dept_key || in_array( $term->slug, $department['cats'], true ) ) {
			$existing = array_values( array_filter( $department['cats'], static fn( $slug ) => (bool) term_exists( $slug, 'product_cat' ) ) );
			return array(
				'key'        => $dept_key,
				'department' => $department,
				'whole'      => $term->slug === $dept_key || $existing === array( $term->slug ),
			);
		}
	}
	return null;
}

/**
 * Breadcrumb, title, intro and chips for the current listing.
 *
 * @return array{title: string, intro: string, crumbs: array, chips: array, chips_label: string}
 */
function qr_minimal_store_shop_context() {
	$shop_url = wc_get_page_permalink( 'shop' );
	$crumbs   = array(
		array( __( 'Home', 'qr-minimal-store' ), home_url( '/' ) ),
		array( __( 'Shop', 'qr-minimal-store' ), $shop_url ),
	);
	$context  = array(
		'title'       => __( 'Shop', 'qr-minimal-store' ),
		'intro'       => __( 'Everything we sell, from authorised South African distributors, with local warranty.', 'qr-minimal-store' ),
		'crumbs'      => $crumbs,
		'chips'       => array(),
		'chips_label' => '',
	);
	$genuine  = __( 'Genuine stock from authorised South African distributors, with local warranty.', 'qr-minimal-store' );

	if ( is_search() ) {
		/* translators: %s: search terms */
		$context['title']    = sprintf( __( 'Results for “%s”', 'qr-minimal-store' ), get_search_query() );
		$context['intro']    = '';
		$context['crumbs'][] = array( __( 'Search', 'qr-minimal-store' ), '' );
		return $context;
	}

	if ( is_shop() ) {
		$context['crumbs']      = array( $crumbs[0], array( __( 'Shop', 'qr-minimal-store' ), '' ) );
		$context['chips_label'] = __( 'Departments', 'qr-minimal-store' );
		$departments            = qr_minimal_store_available_departments();
		$total                  = 0;
		$chips                  = array();
		foreach ( $departments as $department ) {
			$count   = qr_minimal_store_category_stock_count( $department['slugs'] );
			$total  += $count;
			$chips[] = array( $department['title'], $count, $department['url'], false );
		}
		if ( $chips ) {
			array_unshift( $chips, array( __( 'All products', 'qr-minimal-store' ), 0, $shop_url, true ) );
			$context['chips'] = $chips;
		}
		return $context;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return $context;
	}
	$description = trim( wp_strip_all_tags( term_description( $term ) ) );

	if ( is_tax( 'product_cat' ) ) {
		$current = qr_minimal_store_current_department();
		if ( $current ) {
			$department = $current['department'];
			$available  = qr_minimal_store_available_departments();
			$dept_url   = isset( $available[ $current['key'] ] ) ? $available[ $current['key'] ]['url'] : get_term_link( $term );
			$dept_slugs = isset( $available[ $current['key'] ] ) ? $available[ $current['key'] ]['slugs'] : array( $term->slug );
			if ( $current['whole'] ) {
				$context['title']    = $department['title'];
				$context['crumbs'][] = array( $department['title'], '' );
			} else {
				$context['title']    = qr_minimal_store_category_label( $term );
				$context['crumbs'][] = array( $department['title'], $dept_url );
				$context['crumbs'][] = array( $context['title'], '' );
			}
			$context['intro'] = $description ? $description : $department['sub'] . '. ' . $genuine;

			// Chips: the categories (and their sub-categories) inside the department.
			$chips = array();
			$seen  = array();
			foreach ( $dept_slugs as $slug ) {
				// The department's own parent category is the "All" chip.
				if ( $slug === $current['key'] ) {
					continue;
				}
				$cat = get_term_by( 'slug', $slug, 'product_cat' );
				if ( ! $cat instanceof WP_Term ) {
					continue;
				}
				$terms    = array( $cat );
				$children = get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'parent'     => $cat->term_id,
						'hide_empty' => true,
					)
				);
				if ( ! is_wp_error( $children ) ) {
					$terms = array_merge( $terms, $children );
				}
				foreach ( $terms as $chip_term ) {
					if ( isset( $seen[ $chip_term->term_id ] ) ) {
						continue;
					}
					$seen[ $chip_term->term_id ] = true;
					$count                       = qr_minimal_store_category_stock_count( array( $chip_term->slug ) );
					if ( $count < 1 && $chip_term->term_id !== $term->term_id ) {
						continue;
					}
					$chips[] = array( qr_minimal_store_category_label( $chip_term ), $count, get_term_link( $chip_term ), ! $current['whole'] && $chip_term->term_id === $term->term_id );
				}
			}
			if ( count( $chips ) > 1 ) {
				/* translators: %s: department name */
				array_unshift( $chips, array( sprintf( __( 'All %s', 'qr-minimal-store' ), qr_minimal_store_lower_title( $department['title'] ) ), 0, $dept_url, $current['whole'] ) );
				$context['chips'] = $chips;
				/* translators: %s: department name */
				$context['chips_label'] = sprintf( __( '%s categories', 'qr-minimal-store' ), $department['title'] );
			}
			return $context;
		}
	}

	$context['title']    = is_tax( 'product_cat' ) ? qr_minimal_store_category_label( $term ) : html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
	$context['crumbs'][] = array( $context['title'], '' );
	$context['intro']    = $description ? $description : $genuine;
	return $context;
}

/* ---------- Filter counts ---------- */

/**
 * Products of the current listing (before stock, price and brand filters), as
 * [sold out, price, brand slugs]. Cached for 30 minutes.
 *
 * @return array{rows: array, brands: array<string, string>}
 */
function qr_minimal_store_shop_facet_rows() {
	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => 500,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( is_search() ? 'exclude-from-search' : 'exclude-from-catalog' ),
				'operator' => 'NOT IN',
			),
		),
	);
	foreach ( array( 'product_cat', 'product_tag', 'product_brand' ) as $taxonomy ) {
		$value = (string) get_query_var( $taxonomy );
		if ( '' !== $value ) {
			$args[ $taxonomy ] = $value;
		}
	}
	if ( is_search() ) {
		$args['s'] = get_search_query( false );
	}
	$key    = 'qr_shop_facets_' . md5( wp_json_encode( $args ) . '|' . (int) get_option( 'qr_minimal_store_cache_gen', 0 ) );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$query  = new WP_Query( $args );
	$rows   = array();
	$brands = array();
	foreach ( $query->posts as $post ) {
		$prices = array_map( 'floatval', (array) get_post_meta( $post->ID, '_price' ) );
		$slugs  = array();
		if ( taxonomy_exists( 'product_brand' ) ) {
			$terms = get_the_terms( $post, 'product_brand' );
			foreach ( is_array( $terms ) ? $terms : array() as $brand ) {
				$slugs[]                = $brand->slug;
				$brands[ $brand->slug ] = html_entity_decode( $brand->name, ENT_QUOTES, 'UTF-8' );
			}
		}
		$rows[] = array(
			'outofstock' === get_post_meta( $post->ID, '_stock_status', true ) ? 1 : 0,
			$prices ? min( $prices ) : 0.0,
			$slugs,
		);
	}
	asort( $brands );
	$data = array(
		'rows'   => $rows,
		'brands' => $brands,
	);
	set_transient( $key, $data, 30 * MINUTE_IN_SECONDS );
	return $data;
}

/**
 * Counts for every filter option under the current selection (each group counted with
 * the other groups applied).
 *
 * @return array{in_stock: int, sold_out: int, price: array<string, int>, brand: array<string, int>, brands: array<string, string>, rows: array}
 */
function qr_minimal_store_shop_facets() {
	$data    = qr_minimal_store_shop_facet_rows();
	$filters = qr_minimal_store_shop_filters();
	$bands   = qr_minimal_store_price_bands();
	$in_band = static function ( $price, $band ) {
		return $price >= $band['min'] && ( $band['max'] <= 0 || $price < $band['max'] );
	};
	$price_ok = static function ( $row ) use ( $filters, $bands, $in_band ) {
		if ( ! $filters['price'] ) {
			return true;
		}
		foreach ( $filters['price'] as $key ) {
			if ( $in_band( $row[1], $bands[ $key ] ) ) {
				return true;
			}
		}
		return false;
	};
	$brand_ok = static fn( $row ) => ! $filters['brand'] || array_intersect( $row[2], $filters['brand'] );
	$stock_ok = static fn( $row ) => $filters['stock_all'] || ! $row[0];

	$facets = array(
		'in_stock' => 0,
		'sold_out' => 0,
		'price'    => array_fill_keys( array_keys( $bands ), 0 ),
		'brand'    => array_fill_keys( array_keys( $data['brands'] ), 0 ),
		'brands'   => $data['brands'],
		'rows'     => $data['rows'],
	);
	foreach ( $data['rows'] as $row ) {
		if ( $price_ok( $row ) && $brand_ok( $row ) ) {
			$facets[ $row[0] ? 'sold_out' : 'in_stock' ]++;
		}
		if ( $stock_ok( $row ) && $brand_ok( $row ) ) {
			foreach ( $bands as $key => $band ) {
				if ( $in_band( $row[1], $band ) ) {
					$facets['price'][ $key ]++;
				}
			}
		}
		if ( $stock_ok( $row ) && $price_ok( $row ) ) {
			foreach ( $row[2] as $slug ) {
				$facets['brand'][ $slug ]++;
			}
		}
	}
	return $facets;
}

/**
 * URL of the current listing without filters or paging, plus the query arguments that
 * define the listing itself (kept as hidden fields in the filter forms).
 *
 * @return array{url: string, keep: array<string, string>}
 */
function qr_minimal_store_listing_base() {
	$keep = array();
	if ( is_search() ) {
		$keep = array(
			's'         => get_search_query( false ),
			'post_type' => 'product',
		);
		return array(
			'url'  => home_url( '/' ),
			'keep' => $keep,
		);
	}
	if ( is_shop() ) {
		return array(
			'url'  => wc_get_page_permalink( 'shop' ),
			'keep' => $keep,
		);
	}
	$current = qr_minimal_store_current_department();
	if ( $current && $current['whole'] && false !== strpos( (string) get_query_var( 'product_cat' ), ',' ) ) {
		return array(
			'url'  => home_url( '/' ),
			'keep' => array(
				'product_cat'   => (string) get_query_var( 'product_cat' ),
				'qr_department' => $current['key'],
			),
		);
	}
	$link = get_term_link( get_queried_object() );
	return array(
		'url'  => is_wp_error( $link ) ? wc_get_page_permalink( 'shop' ) : $link,
		'keep' => $keep,
	);
}

/**
 * URL of the listing with the given filters.
 *
 * @param array $filters Filters (same shape as qr_minimal_store_shop_filters()).
 * @return string
 */
function qr_minimal_store_listing_url( $filters ) {
	$base = qr_minimal_store_listing_base();
	$args = $base['keep'];
	if ( ! empty( $filters['stock_all'] ) ) {
		$args['stock'] = 'all';
	}
	if ( ! empty( $filters['price'] ) ) {
		$args['price'] = implode( ',', $filters['price'] );
	}
	if ( ! empty( $filters['brand'] ) ) {
		$args['brand'] = implode( ',', $filters['brand'] );
	}
	if ( ! empty( $filters['sort'] ) && 'recommended' !== $filters['sort'] ) {
		$args['sort'] = $filters['sort'];
	}
	return add_query_arg( array_map( 'rawurlencode', $args ), $base['url'] );
}

/* ---------- Assets ---------- */

add_action( 'wp_enqueue_scripts', 'qr_minimal_store_shop_assets', 30 );
function qr_minimal_store_shop_assets() {
	if ( ! qr_minimal_store_is_product_listing() ) {
		return;
	}
	$file = get_template_directory() . '/assets/js/shop.js';
	wp_enqueue_script( 'qr-minimal-store-shop', get_template_directory_uri() . '/assets/js/shop.js', array(), file_exists( $file ) ? (string) filemtime( $file ) : wp_get_theme()->get( 'Version' ), true );
	wp_localize_script(
		'qr-minimal-store-shop',
		'qrShop',
		array(
			/* translators: %d: number of products */
			'showOne'  => __( 'Show %d product', 'qr-minimal-store' ),
			/* translators: %d: number of products */
			'showMany' => __( 'Show %d products', 'qr-minimal-store' ),
			'loading'  => __( 'Loading…', 'qr-minimal-store' ),
			'error'    => __( 'Could not load more products. Please try again.', 'qr-minimal-store' ),
		)
	);
}
