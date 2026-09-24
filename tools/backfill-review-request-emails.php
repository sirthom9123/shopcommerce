<?php
/**
 * Schedule the WooCommerce "Customer review request" email for orders that
 * were already marked Completed before the feature was turned on, so they
 * aren't skipped forever (the scheduler only listens for the live
 * `woocommerce_order_status_completed` transition, not past ones).
 *
 * Only considers orders completed within the last N days (default 21) so
 * customers don't get a review nudge for something they bought months ago.
 * Mirrors Automattic\WooCommerce\Internal\OrderReviews\Scheduler::handle_woocommerce_order_status_completed()
 * so backfilled orders are gated by the same eligibility checks (email
 * enabled, not already scheduled, has reviewable items).
 *
 * Usage: php tools/backfill-review-request-emails.php [days] [--dry-run]
 */
define( 'WP_USE_THEMES', false );
require dirname( __DIR__ ) . '/wp-load.php';

use Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility;
use Automattic\WooCommerce\Internal\OrderReviews\Scheduler;

if ( ! class_exists( 'WooCommerce' ) ) {
	fwrite( STDERR, "WooCommerce must be active.\n" );
	exit( 1 );
}

$args    = array_slice( $argv, 1 );
$dry_run = in_array( '--dry-run', $args, true );
$days    = 21;
foreach ( $args as $arg ) {
	if ( ctype_digit( $arg ) ) {
		$days = (int) $arg;
		break;
	}
}

$mailer = WC()->mailer();
$emails = $mailer ? $mailer->get_emails() : array();
$email  = $emails['WC_Email_Customer_Review_Request'] ?? null;

if ( ! $email instanceof WC_Email_Customer_Review_Request ) {
	fwrite( STDERR, "Review request email class not registered. Enable the 'customer_review_request' feature first (WooCommerce > Settings > Advanced > Features).\n" );
	exit( 1 );
}

if ( ! $email->is_enabled() ) {
	fwrite( STDERR, "Review request email is disabled. Enable it under WooCommerce > Settings > Emails > Review request first.\n" );
	exit( 1 );
}

$cutoff = time() - ( $days * DAY_IN_SECONDS );

$order_ids = wc_get_orders(
	array(
		'status'       => 'completed',
		'limit'        => -1,
		'return'       => 'ids',
		'date_modified' => '>' . $cutoff,
	)
);

$scheduled = 0;
$skipped   = 0;

foreach ( $order_ids as $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		++$skipped;
		continue;
	}

	$completed_at = $order->get_date_completed();
	$completed_ts = $completed_at ? $completed_at->getTimestamp() : (int) $order->get_date_modified()->getTimestamp();

	if ( $completed_ts < $cutoff ) {
		++$skipped;
		continue;
	}

	if ( $order->get_meta( Scheduler::SCHEDULED_META_KEY ) ) {
		printf( "Order #%d: already scheduled, skipping.\n", $order_id );
		++$skipped;
		continue;
	}

	if ( ! apply_filters( 'woocommerce_should_send_review_request', true, $order ) ) {
		printf( "Order #%d: opted out via filter, skipping.\n", $order_id );
		++$skipped;
		continue;
	}

	if ( ! ItemEligibility::has_actionable_items( $order ) ) {
		printf( "Order #%d: no reviewable items, skipping.\n", $order_id );
		++$skipped;
		continue;
	}

	// Keep the original cadence (completion + configured delay) when that
	// still falls in the future; otherwise send soon, staggered a few
	// minutes apart so the mailer isn't hit with a burst all at once.
	$target = $completed_ts + $email->get_delay_seconds();
	if ( $target <= time() ) {
		$target = time() + ( $scheduled * 3 * MINUTE_IN_SECONDS ) + MINUTE_IN_SECONDS;
	}

	if ( $dry_run ) {
		printf( "Order #%d: would schedule for %s.\n", $order_id, gmdate( 'Y-m-d H:i:s', $target ) . ' UTC' );
		++$scheduled;
		continue;
	}

	as_schedule_single_action( $target, Scheduler::ACTION_HOOK, array( $order_id ) );
	$order->update_meta_data( Scheduler::SCHEDULED_META_KEY, (string) $target );
	$order->save();

	printf( "Order #%d: scheduled for %s.\n", $order_id, gmdate( 'Y-m-d H:i:s', $target ) . ' UTC' );
	++$scheduled;
}

printf( "\nDone. Scheduled: %d  Skipped: %d  (window: last %d days%s)\n", $scheduled, $skipped, $days, $dry_run ? ', dry run' : '' );
