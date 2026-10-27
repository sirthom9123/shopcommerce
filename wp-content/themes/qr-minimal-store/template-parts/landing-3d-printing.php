<?php
/**
 * 3D Printing department page, /3d-printing/ (design board B). Sections without products
 * are not shown.
 *
 * @package qr-minimal-store
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$printers  = qr_minimal_store_3d_printers();
$materials = qr_minimal_store_3d_products( array( 'filament', 'resin' ), 12 );
$video     = qr_minimal_store_3d_video_product();
$rule      = function_exists( 'qr_minimal_store_free_delivery_rule' ) ? qr_minimal_store_free_delivery_rule() : null;
$all_free  = $rule && $printers && min( array_map( static fn( $p ) => (float) wc_get_price_to_display( $p ), $printers ) ) >= $rule['threshold'];
$cat_link  = static function ( $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term instanceof WP_Term || qr_minimal_store_category_stock_count( array( $slug ) ) < 1 ) {
		return '';
	}
	$link = get_term_link( $term );
	return is_wp_error( $link ) ? '' : $link;
};

// Chips: sub-categories with products.
$chips = array();
foreach ( qr_minimal_store_3d_categories() as $slug => $category ) {
	$link = $cat_link( $slug );
	if ( $link ) {
		$term    = get_term_by( 'slug', $slug, 'product_cat' );
		$chips[] = array( html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ), $link );
	}
}
?>
<div class="qr-print">
	<div class="container">
		<nav class="qr-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'qr-minimal-store' ); ?>">
			<ol>
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'qr-minimal-store' ); ?></a></li>
				<li><span aria-current="page"><?php esc_html_e( '3D Printing', 'qr-minimal-store' ); ?></span></li>
			</ol>
		</nav>
	</div>

	<section class="container qr-print__hero">
		<div class="qr-print__copy">
			<p class="qr-eyebrow"><?php esc_html_e( 'Anycubic · From an authorised SA distributor', 'qr-minimal-store' ); ?></p>
			<h1><?php esc_html_e( 'Your first 3D printer, or your next one.', 'qr-minimal-store' ); ?></h1>
			<p class="qr-print__lead"><?php esc_html_e( 'FDM printers for colourful, everyday prints and resin printers for fine detail, plus the filament, resin and spares to keep them going. Local warranty, delivered nationwide.', 'qr-minimal-store' ); ?></p>
			<?php if ( $printers || $materials ) : ?>
				<div class="qr-actions">
					<?php if ( $printers ) : ?>
						<a class="qr-btn" href="#printers"><?php esc_html_e( 'Compare printers', 'qr-minimal-store' ); ?></a>
					<?php endif; ?>
					<?php if ( $materials ) : ?>
						<a class="qr-btn qr-btn--outline" href="#materials"><?php esc_html_e( 'Filament and resin', 'qr-minimal-store' ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="qr-print__media">
			<?php
			$hero_video = qr_minimal_store_3d_hero_video_id();
			if ( $hero_video ) {
				echo qr_minimal_store_youtube_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in qr_minimal_store_youtube_html().
					$hero_video,
					__( 'Anycubic 3D printing in action · official Anycubic video', 'qr-minimal-store' ),
					$printers ? $printers[0]->get_image_id() : 0,
					true
				);
			} elseif ( $video ) {
				echo qr_minimal_store_youtube_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in qr_minimal_store_youtube_html().
					qr_minimal_store_youtube_id( $video->get_meta( '_product_video_youtube' ) ),
					/* translators: %s: product name */
					sprintf( __( '%s in action · official Anycubic video', 'qr-minimal-store' ), $video->get_name() ),
					$video->get_image_id()
				);
			} elseif ( $printers ) {
				echo '<a class="qr-print__img" href="' . esc_url( $printers[0]->get_permalink() ) . '">' . $printers[0]->get_image( 'large', array( 'alt' => $printers[0]->get_name() ) ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image HTML.
			}
			?>
		</div>
	</section>

	<?php if ( $chips ) : ?>
		<nav class="qr-print__chips" aria-label="<?php esc_attr_e( 'Browse 3D printing', 'qr-minimal-store' ); ?>">
			<div class="container">
				<ul class="qr-chips">
					<?php foreach ( $chips as $chip ) : ?>
						<li><a class="qr-chip" href="<?php echo esc_url( $chip[1] ); ?>"><?php echo esc_html( $chip[0] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</nav>
	<?php endif; ?>

	<section class="container qr-section">
		<h2 class="qr-h2"><?php esc_html_e( 'FDM or resin?', 'qr-minimal-store' ); ?></h2>
		<div class="qr-print__types">
			<div class="qr-print__type">
				<h3><?php esc_html_e( 'FDM (filament)', 'qr-minimal-store' ); ?></h3>
				<p><?php esc_html_e( 'Melts a spool of plastic layer by layer. Best for household parts, toys, organisers, cosplay and multi-colour prints. Easy to start with.', 'qr-minimal-store' ); ?></p>
				<?php $link = $cat_link( 'fdm-printers' ); ?>
				<?php if ( $link ) : ?>
					<a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Shop FDM printers', 'qr-minimal-store' ); ?></a>
				<?php endif; ?>
			</div>
			<div class="qr-print__type">
				<h3><?php esc_html_e( 'Resin (MSLA)', 'qr-minimal-store' ); ?></h3>
				<p><?php esc_html_e( 'Cures liquid resin with light. Best for miniatures, jewellery models and very fine detail. Needs a wash-and-cure step afterwards.', 'qr-minimal-store' ); ?></p>
				<?php $link = $cat_link( 'resin-printers' ); ?>
				<?php if ( $link ) : ?>
					<a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Shop resin printers', 'qr-minimal-store' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( $printers ) : ?>
		<section id="printers" class="container qr-section">
			<div class="qr-section__head">
				<h2 class="qr-h2"><?php esc_html_e( 'Printers', 'qr-minimal-store' ); ?></h2>
				<p class="qr-section__sub">
					<?php
					echo esc_html(
						$all_free
							? __( 'No VAT added at checkout. Every printer ships free.', 'qr-minimal-store' )
							: __( 'No VAT added at checkout.', 'qr-minimal-store' )
					);
					?>
				</p>
			</div>
			<ul class="qr-cards qr-print__printers">
				<?php
				foreach ( $printers as $product ) {
					qr_minimal_store_3d_printer_card( $product );
				}
				?>
			</ul>
			<p class="qr-print__footnote"><?php esc_html_e( '*Mobicred or RCS store card at Payfast checkout, subject to approval. Same price as cash.', 'qr-minimal-store' ); ?></p>
		</section>
	<?php endif; ?>

	<?php if ( $materials ) : ?>
		<section id="materials" class="qr-band">
			<div class="container qr-section">
				<h2 class="qr-h2"><?php esc_html_e( 'Filament and resin', 'qr-minimal-store' ); ?></h2>
				<p class="qr-section__sub"><?php esc_html_e( 'Pick a material, then the colour on the product page.', 'qr-minimal-store' ); ?></p>
				<ul class="qr-print__materials">
					<?php foreach ( $materials as $product ) : ?>
						<?php
						$colours = qr_minimal_store_colour_count( $product );
						$unit    = trim( (string) $product->get_meta( '_qr_price_unit' ) );
						$price   = $product->is_type( 'variable' ) ? $product->get_variation_price( 'min', true ) : wc_get_price_to_display( $product );
						?>
						<li>
							<a class="qr-material" href="<?php echo esc_url( $product->get_permalink() ); ?>">
								<span class="qr-material__head">
									<span class="qr-material__name"><?php echo esc_html( $product->get_name() ); ?></span>
									<?php if ( $colours > 1 ) : ?>
										<span class="qr-material__colours">
											<?php
											/* translators: %d: number of colours */
											echo esc_html( sprintf( _n( '%d colour', '%d colours', $colours, 'qr-minimal-store' ), $colours ) );
											?>
										</span>
									<?php endif; ?>
								</span>
								<span class="qr-material__use"><?php echo esc_html( qr_minimal_store_3d_blurb( $product ) ); ?></span>
								<span class="qr-material__price">
									<?php
									echo esc_html( html_entity_decode( wp_strip_all_tags( wc_price( $price ) ), ENT_QUOTES, 'UTF-8' ) );
									if ( $unit ) {
										echo ' <span>' . esc_html( $unit ) . '</span>';
									}
									?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! $printers && ! $materials ) : ?>
		<section class="container qr-section">
			<div class="qr-shop__empty">
				<p class="qr-shop__empty-title"><?php esc_html_e( 'The Anycubic range is on its way.', 'qr-minimal-store' ); ?></p>
				<p><a href="<?php echo esc_url( 'mailto:orders@shop.yehudasolutions.com?subject=' . rawurlencode( __( '3D printing', 'qr-minimal-store' ) ) ); ?>"><?php esc_html_e( 'Email us what you are looking for', 'qr-minimal-store' ); ?></a></p>
			</div>
		</section>
	<?php endif; ?>

	<section class="container qr-section qr-print__info">
		<div>
			<h2><?php esc_html_e( 'How backorder works', 'qr-minimal-store' ); ?></h2>
			<p><?php esc_html_e( 'Out of stock items show "Available on backorder". Order and pay as usual; we deliver as soon as our supplier ships and let you know when it is on the way.', 'qr-minimal-store' ); ?></p>
		</div>
		<div>
			<h2><?php esc_html_e( 'Warranty and returns', 'qr-minimal-store' ); ?></h2>
			<p><?php esc_html_e( 'Local warranty through an authorised distributor. Unused items can be returned within 30 days of delivery.', 'qr-minimal-store' ); ?></p>
		</div>
		<?php if ( $rule ) : ?>
			<div>
				<h2><?php esc_html_e( 'Delivery', 'qr-minimal-store' ); ?></h2>
				<p>
					<?php
					echo esc_html(
						sprintf(
							$all_free
								/* translators: 1: delivery fee, 2: free delivery threshold */
								? __( '%1$s per order, free from %2$s, so every printer ships free. Usually 2–3 working days for items in stock.', 'qr-minimal-store' )
								/* translators: 1: delivery fee, 2: free delivery threshold */
								: __( '%1$s per order, free from %2$s. Usually 2–3 working days for items in stock.', 'qr-minimal-store' ),
							qr_minimal_store_money_whole( $rule['fee'] ),
							qr_minimal_store_money_whole( $rule['threshold'] )
						)
					);
					?>
				</p>
			</div>
		<?php endif; ?>
	</section>
</div>
<?php
get_footer( 'shop' );
