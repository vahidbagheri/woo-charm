<?php
/** Dollar discounts, timezone boundaries and native prices; disposable local fixtures only. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-pricing-tests' );
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! in_array( wp_get_environment_type(), array( 'local', 'development', 'staging' ), true ) ) { exit( 'Local/staging only.' ); }
add_filter( 'pre_wp_mail', '__return_false' );
add_filter( 'pre_http_request', static fn() => new WP_Error( 'test_no_network', 'No external requests.' ), PHP_INT_MAX );
add_filter( 'pre_option_' . VOICEWEB_WC_USD_RATE, static fn() => '100000' );
$ids = array(); $checks = 0; $failure = null;
$admin = get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1 ) );
wp_set_current_user( $admin[0] ?? 0 );
function vwcp_check( bool $value, string $message ): void {
	if ( ! $value ) { throw new RuntimeException( $message ); }
	$GLOBALS['checks']++;
}
function vwcp_rejected( callable $callback, string $message ): void {
	try { $callback(); } catch ( InvalidArgumentException $error ) { vwcp_check( true, $message ); return; }
	throw new RuntimeException( $message );
}
function vwcp_row( int $id, string $base, string $sale, string $from = '', string $to = '' ): array {
	return array( 'usd_amount' => array( $id => $base ), 'usd_sale_amount' => array( $id => $sale ), 'sale_from' => array( $id => $from ), 'sale_to' => array( $id => $to ) );
}
try {
	vwcp_check( voiceweb_wc_pricing_has_jalali(), 'The installed Jalali calendar is available.' );
	$known_date = voiceweb_wc_pricing_date( '۱۴۰۵/۰۸/۳۰ ۱۲:۳۴:۵۶' );
	vwcp_check( ( new DateTimeImmutable( '@' . $known_date ) )->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' ) === '2026-11-21 12:34:56', 'Persian digits and Jalali dates convert to the correct store wall-clock time.' );
	vwcp_check( voiceweb_wc_pricing_date_input( $known_date ) === '1405/08/30 12:34:56', 'The Jalali input round-trips without calendar filters changing its seconds.' );
	vwcp_check( voiceweb_wc_pricing_date_input( voiceweb_wc_pricing_date( '1399/12/30 23:59:59' ) ) === '1399/12/30 23:59:59', 'Esfand 30 is accepted in a Jalali leap year.' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_date( '1400/12/30 12:00:00' ), 'Esfand 30 is rejected in a non-leap year.' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_date( '1405/07/31 12:00:00' ), 'Day 31 is rejected in a thirty-day Jalali month.' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_date( '1405/08/30 24:00:00' ), 'An invalid Jalali wall-clock time is rejected.' );
	$p = new WC_Product_Simple(); $p->set_name( 'vw_pricing_test_' . bin2hex( random_bytes( 5 ) ) ); $p->set_status( 'publish' );
	$p->set_regular_price( '900000' ); $p->set_sale_price( '850000' ); $p->set_date_on_sale_to( time() + DAY_IN_SECONDS );
	$p->set_manage_stock( true ); $p->set_stock_quantity( 7 ); $ids[] = $p->save(); $id = $p->get_id();
	$original_to = $p->get_date_on_sale_to()->getTimestamp();
	$from = time() - 3600; $to = time() + 3600;
	$row = vwcp_row( $id, '۵۰', '۴۰', voiceweb_wc_pricing_date_input( $from ), voiceweb_wc_pricing_date_input( $to ) );
	voiceweb_wc_pricing_apply( false, $row ); $p = wc_get_product( $id );
	vwcp_check( (int) $p->get_regular_price() === 50000000 && (int) $p->get_sale_price() === 40000000 && (int) $p->get_price() === 40000000, 'Dollar regular/sale prices convert to native IRR with Persian digits.' );
	vwcp_check( $p->get_date_on_sale_from()->getTimestamp() === $from && $p->get_date_on_sale_to()->getTimestamp() === $to, 'Store timezone date input round-trips to the exact native timestamps.' );
	vwcp_check( voiceweb_wc_product_discount( $p ) === 20 && voiceweb_wc_product_deadline( $p ) === $to, 'Discount and countdown use the active native price pair.' );
	$offer = voiceweb_wc_pricing_offer( $p );
	voiceweb_wc_pricing_set_amount( $p, '50', '120000' ); $p->save();
	vwcp_check( (int) $p->get_regular_price() === 60000000 && (int) $p->get_sale_price() === 48000000 && voiceweb_wc_pricing_offer( $p ) === $offer, 'Rate changes reprice both amounts while preserving the complete window.' );
	voiceweb_wc_pricing_apply( false, $row );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, vwcp_row( $id, '50', '50' ) ), 'Equal sale price is rejected.' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, vwcp_row( $id, '50', '60' ) ), 'Higher sale price is rejected.' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, vwcp_row( $id, '50', '40', '2026-12-02T12:00', '2026-12-01T12:00' ) ), 'Reversed dates are rejected.' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, vwcp_row( $id, '50', '40', '2026-02-30T12:00' ) ), 'Invalid calendar dates are rejected.' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, array( 'usd_sale_amount' => array( $id => array( '40' ) ) ) ), 'Non-scalar amount input is rejected.' );
	vwcp_check( (int) wc_get_product( $id )->get_price() === 48000000, 'Rejected submissions make no partial price changes.' );
	wp_set_current_user( 0 );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, vwcp_row( $id, '50', '40' ) ), 'Unauthorized product edits are rejected.' );
	wp_set_current_user( $admin[0] );
	$future = vwcp_row( $id, '50', '40', voiceweb_wc_pricing_date_input( time() + 3600 ), voiceweb_wc_pricing_date_input( time() + 7200 ) );
	voiceweb_wc_pricing_apply( false, $future ); $p = wc_get_product( $id );
	vwcp_check( ! $p->is_on_sale() && (int) $p->get_price() === 50000000 && voiceweb_wc_product_deadline( $p ) === 0, 'A future discount keeps regular pricing and has no active countdown.' );
	voiceweb_wc_pricing_apply( false, vwcp_row( $id, '50', '40' ) ); $p = wc_get_product( $id );
	vwcp_check( $p->is_on_sale() && ! $p->get_date_on_sale_to() && ! voiceweb_wc_product_deadline( $p ) && voiceweb_wc_pricing_offer( $p )['since'] <= time(), 'Clearing a future start activates the discount immediately with the correct timeline start.' );
	voiceweb_wc_pricing_apply( false, vwcp_row( $id, '50', '' ) ); $p = wc_get_product( $id );
	vwcp_check( ! $p->is_on_sale() && $p->get_sale_price() === '' && ! voiceweb_wc_pricing_offer( $p ) && (int) $p->get_price() === 50000000, 'Clearing the dollar sale removes the native discount and its schedule.' );
	voiceweb_wc_pricing_apply( false, vwcp_row( $id, '50', '40', voiceweb_wc_pricing_date_input( time() - 3600 ), voiceweb_wc_pricing_date_input( time() - 60 ) ) ); $p = wc_get_product( $id );
	vwcp_check( ! $p->is_on_sale() && $p->get_sale_price() === '' && voiceweb_wc_pricing_offer( $p )['amount'] === '40', 'Ended schedules remain editable without applying an ended sale.' );
	voiceweb_wc_pricing_set_amount( $p, '50', '120000' ); $p->save();
	vwcp_check( ! $p->is_on_sale() && (int) $p->get_price() === 60000000 && ! $p->get_date_on_sale_to(), 'A rate update cannot resurrect an ended discount.' );
	voiceweb_wc_pricing_apply( false, vwcp_row( $id, '', '' ) ); $p = wc_get_product( $id );
	vwcp_check( (int) $p->get_regular_price() === 900000 && (int) $p->get_sale_price() === 850000 && $p->get_date_on_sale_to()->getTimestamp() === $original_to && ! voiceweb_wc_pricing_offer( $p ), 'Leaving dollar pricing restores the original native prices and dates.' );
	$parent = new WC_Product_Variable(); $parent->set_name( 'vw_pricing_variable_test' ); $parent->set_status( 'publish' );
	$a = new WC_Product_Attribute(); $a->set_name( 'model' ); $a->set_options( array( 'standard', 'large' ) ); $a->set_variation( true ); $parent->set_attributes( array( $a ) ); $ids[] = $parent->save();
	foreach ( array( 'standard', 'large' ) as $model ) {
		$v = new WC_Product_Variation(); $v->set_parent_id( $parent->get_id() ); $v->set_attributes( array( 'model' => $model ) ); $v->set_status( 'publish' ); $v->set_regular_price( '1000' ); $ids[] = $v->save();
		voiceweb_wc_pricing_apply( false, vwcp_row( $v->get_id(), $model === 'standard' ? '50' : '100', $model === 'standard' ? '40' : '70', voiceweb_wc_pricing_date_input( $from ), voiceweb_wc_pricing_date_input( $model === 'standard' ? $to : $to + 600 ) ) );
	}
	$parent = wc_get_product( $parent->get_id() );
	voiceweb_wc_pricing_apply( false, array( 'display_priority' => array( $id => '۲', $parent->get_id() => '۱۰' ) ) );
	vwcp_check( wc_get_product( $id )->get_menu_order( 'edit' ) === 2 && wc_get_product( $parent->get_id() )->get_menu_order( 'edit' ) === 10, 'Priority saves independently for simple and variable parents using Persian digits.' );
	$ordering = WC()->query->get_catalog_ordering_args( 'menu_order' );
	$ordered = new WP_Query( array_merge( $ordering, array( 'post_type' => 'product', 'post__in' => array( $id, $parent->get_id() ), 'fields' => 'ids', 'posts_per_page' => 1 ) ) );
	$second_page = new WP_Query( array_merge( $ordering, array( 'post_type' => 'product', 'post__in' => array( $id, $parent->get_id() ), 'fields' => 'ids', 'posts_per_page' => 1, 'paged' => 2 ) ) );
	vwcp_check( $ordered->posts === array( $parent->get_id() ) && $second_page->posts === array( $id ), 'Numeric priority 10 precedes 2 across real SQL pagination.' );
	vwcp_check( apply_filters( 'woocommerce_default_catalog_orderby', 'date' ) === 'menu_order' && WC()->query->get_catalog_ordering_args( 'date' )['orderby'] === 'date ID', 'Priority is the default and explicit newest sorting still works.' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, array( 'display_priority' => array( $id => '-1' ) ) ), 'A negative priority is rejected.' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, array( 'display_priority' => array( $id => '1.5' ) ) ), 'A fractional priority is rejected.' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, array( 'display_priority' => array( $v->get_id() => '100' ) ) ), 'Variations cannot override their parent display priority.' );
	$invalid_priority_row = vwcp_row( $id, '50', '60' ); $invalid_priority_row['display_priority'] = array( $id => '99' );
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, $invalid_priority_row ), 'An invalid price rejects the priority change in the same submission.' );
	vwcp_check( wc_get_product( $id )->get_menu_order( 'edit' ) === 2, 'No partial priority write occurs before validation finishes.' );
	voiceweb_wc_pricing_apply( false, array( 'display_priority' => array( $id => '۰' ) ) );
	vwcp_check( wc_get_product( $id )->get_menu_order( 'edit' ) === 0, 'Priority can be reset to zero without changing prices.' );
	vwcp_check( (int) $parent->get_variation_price( 'min' ) === 40000000 && voiceweb_wc_product_discount( $parent ) === 30, 'Variation saves synchronize native parent prices and paired discounts.' );
	$card_offer = voiceweb_wc_product_offer_data( $parent, true );
	vwcp_check( $card_offer['discount'] === 30 && $card_offer['end'] === $to + 600, 'Variable card dates belong to the exact variation whose discount is shown.' );
	$rows = vwcp_row( $id, '60', '50' ); $rows['usd_amount'][ $v->get_id() ] = '100'; $rows['usd_sale_amount'][ $v->get_id() ] = '110';
	vwcp_rejected( static fn() => voiceweb_wc_pricing_apply( false, $rows ), 'A bad later row rejects the whole submission.' );
	vwcp_check( (int) wc_get_product( $id )->get_regular_price() === 900000, 'Earlier valid rows are not persisted if another row fails.' );
	$begins = time() + 3; $ends = $begins + 60;
	$window = vwcp_row( $id, '50', '40', voiceweb_wc_pricing_date_input( $begins ), voiceweb_wc_pricing_date_input( $ends ) );
	$child_window = vwcp_row( $v->get_id(), '100', '70', voiceweb_wc_pricing_date_input( $begins ), voiceweb_wc_pricing_date_input( $ends ) );
	foreach ( $child_window as $key => $values ) { $window[ $key ][ $v->get_id() ] = $values[ $v->get_id() ]; }
	voiceweb_wc_pricing_apply( false, $window );
	$cached_simple = wc_get_product( $id ); $cached_child = wc_get_product( $v->get_id() ); $cached_parent = wc_get_product( $parent->get_id() );
	$cached_parent->get_variation_prices( true );
	vwcp_check( ! $cached_simple->is_on_sale() && ! $cached_child->is_on_sale() && voiceweb_wc_product_discount( $cached_parent ) === 20, 'A scheduled simple/variation discount is inactive before its real start.' );
	while ( time() <= $begins ) { usleep( 200000 ); }
	vwcp_check( (int) $cached_simple->get_price() === 40000000 && (int) $cached_child->get_price() === 70000000 && voiceweb_wc_product_discount( $cached_parent ) === 30, 'The real start boundary activates native simple and cached variation prices without cron or another save.' );
	vwcp_check( (int) wc_get_product( $id )->get_stock_quantity() === 7, 'Price edits preserve stock.' );
} catch ( Throwable $error ) { $failure = $error; }
finally {
	foreach ( array_reverse( $ids ) as $id ) {
		as_unschedule_all_actions( 'wc_product_start_scheduled_sale', array( 'product_id' => $id ), 'woocommerce-sales' );
		as_unschedule_all_actions( 'wc_product_end_scheduled_sale', array( 'product_id' => $id ), 'woocommerce-sales' );
		$product = wc_get_product( $id ); if ( $product ) { $product->delete( true ); }
	}
}
if ( $failure ) { fwrite( STDERR, 'FAIL: ' . $failure->getMessage() . PHP_EOL ); exit( 1 ); }
echo 'PASS: ' . $checks . ' catalog pricing checks; fixture products removed.' . PHP_EOL;
