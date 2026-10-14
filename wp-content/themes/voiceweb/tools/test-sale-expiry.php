<?php
/** Scheduled-sale boundaries with delayed cron. Products only, no order/payment/mail/SMS. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-sale-tests-' . bin2hex( random_bytes( 5 ) ) );
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'localhost'; $_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! function_exists( 'voiceweb_wc_variation_sale_hash' ) ) { require dirname( __DIR__ ) . '/inc/woocommerce/sale-expiry.php'; }
add_filter( 'pre_wp_mail', '__return_false' );
add_filter( 'pre_http_request', static fn() => new WP_Error( 'test_no_network', 'External requests disabled.' ), PHP_INT_MAX );
$prefix = 'vw_sale_test_' . bin2hex( random_bytes( 4 ) ); $ids = array(); $checks = 0; $failure = null;
$old_lease = get_option( 'voiceweb_sale_expiry_lease', null );
function vws_check( bool $value, string $message ): void { if ( ! $value ) { throw new RuntimeException( $message ); } $GLOBALS['checks']++; }
try {
	$before_orders = wc_get_orders( array( 'limit' => 1, 'paginate' => true ) )->total;
	$ends = time() + 5;
	$simple = new WC_Product_Simple(); $simple->set_name( $prefix . '_simple' ); $simple->set_status( 'publish' ); $simple->set_regular_price( '1000000' ); $simple->set_sale_price( '750000' ); $simple->set_date_on_sale_from( time() - 60 ); $simple->set_date_on_sale_to( $ends ); $simple->set_manage_stock( true ); $simple->set_stock_quantity( 7 ); $ids[] = $simple->save();
	$parent = new WC_Product_Variable(); $parent->set_name( $prefix . '_variable' ); $parent->set_status( 'publish' ); $attribute = new WC_Product_Attribute(); $attribute->set_name( 'model' ); $attribute->set_options( array( 'standard' ) ); $attribute->set_variation( true ); $parent->set_attributes( array( $attribute ) ); $ids[] = $parent->save();
	$variation = new WC_Product_Variation(); $variation->set_parent_id( $parent->get_id() ); $variation->set_name( $prefix . '_variation' ); $variation->set_status( 'publish' ); $variation->set_attributes( array( 'model' => 'standard' ) ); $variation->set_regular_price( '2000000' ); $variation->set_sale_price( '1500000' ); $variation->set_date_on_sale_from( time() - 60 ); $variation->set_date_on_sale_to( $ends ); $variation->set_manage_stock( true ); $variation->set_stock_quantity( 5 ); $ids[] = $variation->save(); WC_Product_Variable::sync( $parent->get_id() );
	$parent = wc_get_product( $parent->get_id() );
	$hash = voiceweb_wc_variation_sale_hash( array( 'base' ), $parent, false );
	vws_check( $hash === voiceweb_wc_variation_sale_hash( array( 'base' ), $parent, false ), 'The sale-window hash stays stable within one phase.' );
	vws_check( $parent->is_on_sale() && (int) $parent->get_variation_price( 'min' ) === 1500000, 'The active variable sale populates the native parent price cache.' );
	// Let the real boundary pass with cron/Action Scheduler disabled. No meta/save/cache
	// invalidation occurs at the boundary: this proves the phase protects an old transient.
	$parent->get_variation_prices( true );
	while ( time() <= $ends ) { usleep( 200000 ); }
	$simple = wc_get_product( $simple->get_id() ); $variation = wc_get_product( $variation->get_id() );
	vws_check( get_post_meta( $simple->get_id(), '_price', true ) === '750000', 'The simulated delayed runner leaves a stale simple stored price.' );
	vws_check( (int) $simple->get_price() === 1000000 && ! $simple->is_on_sale(), 'Native Woo 11.2 reconciles expired simple checkout pricing without a theme price override.' );
	vws_check( (int) $variation->get_price() === 2000000 && ! $variation->is_on_sale(), 'Native variation reads re-derive the expired checkout price.' );
	$parent = wc_get_product( $parent->get_id() );
	$expired_hash = voiceweb_wc_variation_sale_hash( array( 'base' ), $parent, false );
	vws_check( $hash !== $expired_hash, 'A real sale boundary changes the parent cache key.' );
	vws_check( (int) $parent->get_variation_price( 'min' ) === 2000000 && ! $parent->is_on_sale(), 'Expired already-cached variable range and badge use regular prices.' );
	vws_check( voiceweb_wc_reconcile_expired_sales( true, $ids ) === 2, 'The bounded fallback uses native handlers for expired simple and child products.' );
	vws_check( get_post_meta( $simple->get_id(), '_price', true ) === '1000000' && get_post_meta( $variation->get_id(), '_price', true ) === '2000000', 'Native sale-end repair updates persisted prices.' );
	global $wpdb;
	$lookup = $wpdb->get_results( 'SELECT product_id,min_price,max_price,onsale FROM ' . $wpdb->wc_product_meta_lookup . ' WHERE product_id IN (' . implode( ',', array_map( 'absint', $ids ) ) . ')', OBJECT_K );
	vws_check( (int) $lookup[ $simple->get_id() ]->min_price === 1000000 && (int) $lookup[ $simple->get_id() ]->onsale === 0, 'Simple SQL price lookup and sale flag are repaired.' );
	vws_check( (int) $lookup[ $parent->get_id() ]->min_price === 2000000 && (int) $lookup[ $parent->get_id() ]->max_price === 2000000 && (int) $lookup[ $parent->get_id() ]->onsale === 0, 'Parent SQL price range and sale flag match the expired variation.' );
	vws_check( voiceweb_wc_reconcile_expired_sales( true, $ids ) === 0, 'Repeating the repair is idempotent.' );
	vws_check( (int) wc_get_product( $simple->get_id() )->get_stock_quantity() === 7 && (int) wc_get_product( $variation->get_id() )->get_stock_quantity() === 5, 'Ending a discount does not change stock.' );
	vws_check( wc_get_orders( array( 'limit' => 1, 'paginate' => true ) )->total === $before_orders, 'Ending a discount creates no financial order.' );
	delete_option( 'voiceweb_sale_expiry_lease' );
	vws_check( voiceweb_wc_sale_expiry_lease() && ! voiceweb_wc_sale_expiry_lease(), 'The durable lease admits only one concurrent batch window.' );
} catch ( Throwable $error ) { $failure = $error; }
finally {
	foreach ( array_reverse( $ids ) as $id ) {
		as_unschedule_all_actions( 'wc_product_start_scheduled_sale', array( 'product_id' => $id ), 'woocommerce-sales' );
		as_unschedule_all_actions( 'wc_product_end_scheduled_sale', array( 'product_id' => $id ), 'woocommerce-sales' );
		$product = wc_get_product( $id ); if ( $product ) { $product->delete( true ); }
	}
	if ( $old_lease === null ) { delete_option( 'voiceweb_sale_expiry_lease' ); } else { update_option( 'voiceweb_sale_expiry_lease', $old_lease ); }
}
if ( $failure ) { fwrite( STDERR, 'FAIL: ' . $failure->getMessage() . PHP_EOL ); exit( 1 ); }
echo 'PASS: ' . $checks . ' sale-expiry checks; fixture prices/caches/lookup repaired, no orders/stock/mail/network changes.' . PHP_EOL;
