<?php
/**
 * Deals page, /deals/ (inc/deals.php). Lists the products whose sale price has an end date; with
 * none running it says so and points to Bundles and the home page.
 *
 * @package qr-minimal-store
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$running = qr_minimal_store_has_deals();
$deals   = $running ? qr_minimal_store_deals() : array();
$title   = qr_minimal_store_deals_title();
$groups  = qr_minimal_store_deal_groups( $deals );
$group   = qr_minimal_store_deals_group();
$shown   = '' === $group ? $deals : array_values( array_filter( $deals, static fn( $deal ) => $deal['group'] === $group ) );
$bundles = get_term_by( 'slug', 'bundle-deals', 'product_cat' );
$bundles = $bundles instanceof WP_Term && $bundles->count > 0 ? get_term_link( $bundles ) : '';
?>
<div class="container qr-shop qr-deals">
	<?php woocommerce_output_all_notices(); ?>

	<header class="qr-shop__head">
		<nav class="qr-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'qr-minimal-store' ); ?>">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'qr-minimal-store' ); ?></a></li>
				<li><span aria-current="page"><?php esc_html_e( 'Deals', 'qr-minimal-store' ); ?></span></li>
			</ol>
		</nav>
		<h1 class="qr-shop__title"><?php echo esc_html( $running ? $title : __( 'Deals', 'qr-minimal-store' ) ); ?></h1>
		<?php if ( $running ) : ?>
			<p class="qr-deals__lead"><?php echo esc_html( qr_minimal_store_deals_lead( $deals ) ); ?></p>
			<?php if ( count( $groups ) > 1 ) : ?>
				<ul class="qr-chips" aria-label="<?php esc_attr_e( 'Deal groups', 'qr-minimal-store' ); ?>">
					<li>
						<a class="qr-chip<?php echo '' === $group ? ' is-active' : ''; ?>" href="<?php echo esc_url( qr_minimal_store_deals_url() ); ?>"<?php echo '' === $group ? ' aria-current="page"' : ''; ?>>
							<?php esc_html_e( 'All deals', 'qr-minimal-store' ); ?> <span class="qr-chip__count">(<?php echo esc_html( (string) count( $deals ) ); ?>)</span>
						</a>
					</li>
					<?php foreach ( $groups as $key => $chip ) : ?>
						<li>
							<a class="qr-chip<?php echo $group === $key ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'group', $key, qr_minimal_store_deals_url() ) ); ?>"<?php echo $group === $key ? ' aria-current="page"' : ''; ?>>
								<?php echo esc_html( $chip[0] ); ?> <span class="qr-chip__count">(<?php echo esc_html( (string) $chip[1] ); ?>)</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endif; ?>
	</header>

	<?php if ( $running ) : ?>
		<div class="qr-bdeals qr-bdeals--page">
			<ul class="qr-deals__grid">
				<?php
				foreach ( $shown as $deal ) {
					qr_minimal_store_deal_card( $deal );
				}
				?>
			</ul>
		</div>
	<?php else : ?>
		<div class="qr-shop__empty">
			<p class="qr-shop__empty-title"><?php esc_html_e( 'No deals running right now.', 'qr-minimal-store' ); ?></p>
			<p>
				<?php if ( $bundles && ! is_wp_error( $bundles ) ) : ?>
					<a href="<?php echo esc_url( $bundles ); ?>"><?php esc_html_e( 'See bundle deals', 'qr-minimal-store' ); ?></a>
				<?php endif; ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to the home page', 'qr-minimal-store' ); ?></a>
			</p>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer( 'shop' );
