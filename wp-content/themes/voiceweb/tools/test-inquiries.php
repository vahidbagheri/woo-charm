<?php
/** Isolated callback-mode regression. Never sends mail/SMS or creates a financial order. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-inquiry-tests-' . bin2hex( random_bytes( 5 ) ) );
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
if ( ! function_exists( 'voiceweb_wc_product_is_inquiry' ) ) { require dirname( __DIR__ ) . '/inc/woocommerce/inquiries.php'; }
add_filter( 'pre_wp_mail', '__return_false' );
add_filter( 'pre_http_request', static fn() => new WP_Error( 'test_no_network', 'External requests disabled.' ), PHP_INT_MAX );
voiceweb_wc_inquiry_install();
$prefix = 'vwi_test_' . bin2hex( random_bytes( 4 ) );
$product_ids = array(); $users = array(); $leads = array(); $limit_hashes = array(); $checks = 0; $failure = null;
$old_mode = get_option( 'voiceweb_wc_purchase_mode', null );
$old_cookie = $_COOKIE['vw_inquiry_device'] ?? null;
$old_ip = $_SERVER['REMOTE_ADDR'] ?? null;
$_SERVER['REMOTE_ADDR'] = $prefix;
$device = bin2hex( random_bytes( 32 ) ); $_COOKIE['vw_inquiry_device'] = $device;
function vwi_check( bool $value, string $message ): void { if ( ! $value ) { throw new RuntimeException( $message ); } $GLOBALS['checks']++; }
function vwi_api( array $data, string $origin = '', bool $csrf = true, string $method = 'POST' ): WP_REST_Response {
	$request = new WP_REST_Request( $method, '/voiceweb/v1/inquiries' );
	$request->set_header( 'Origin', $origin ?: untrailingslashit( home_url( '/' ) ) );
	$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
	$request->set_header( 'X-VW-Inquiry-CSRF', $csrf ? voiceweb_wc_inquiry_token( voiceweb_wc_inquiry_device(), (int) floor( time() / 3600 ) ) : 'invalid' );
	$request->set_header( 'Content-Type', 'application/json' );
	$request->set_body( wp_json_encode( $data ) );
	return rest_get_server()->dispatch( $request );
}
function vwi_proof( int $issued ): string { return $issued . '.' . hash_hmac( 'sha256', 'inquiry-form|' . voiceweb_wc_inquiry_device() . '|' . $issued, wp_salt( 'auth' ) ); }
try {
	wp_set_current_user( 0 );
	update_option( 'voiceweb_wc_purchase_mode', 'purchase' );
	$simple = new WC_Product_Simple(); $simple->set_name( $prefix . '_simple' ); $simple->set_status( 'publish' ); $simple->set_regular_price( '1200000' ); $simple->set_manage_stock( true ); $simple->set_stock_quantity( 5 ); $product_ids[] = $simple->save();
	vwi_check( ! voiceweb_wc_product_is_inquiry( $simple ), 'Default mode remains normal purchasing.' );
	update_option( 'voiceweb_wc_purchase_mode', 'inquiry' );
	vwi_check( voiceweb_wc_product_is_inquiry( $simple ), 'Global callback mode applies to inherited products.' );
	$simple->update_meta_data( '_voiceweb_purchase_mode', 'purchase' ); $simple->save();
	vwi_check( ! voiceweb_wc_product_is_inquiry( $simple ), 'An explicit product purchase override wins over the global callback mode.' );
	$simple->update_meta_data( '_voiceweb_purchase_mode', 'inquiry' ); $simple->save();
	update_option( 'voiceweb_wc_purchase_mode', 'purchase' );
	vwi_check( voiceweb_wc_product_is_inquiry( $simple ), 'An explicit callback override wins over the default purchase mode.' );
	$parent = new WC_Product_Variable(); $parent->set_name( $prefix . '_variable' ); $parent->set_status( 'publish' ); $parent->update_meta_data( '_voiceweb_purchase_mode', 'inquiry' );
	$attribute = new WC_Product_Attribute(); $attribute->set_name( 'color' ); $attribute->set_options( array( 'green', 'black' ) ); $attribute->set_variation( true ); $parent->set_attributes( array( $attribute ) ); $product_ids[] = $parent->save();
	$variation = new WC_Product_Variation(); $variation->set_parent_id( $parent->get_id() ); $variation->set_attributes( array( 'color' => 'green' ) ); $variation->set_status( 'publish' ); $variation->set_regular_price( '1400000' ); $variation->set_manage_stock( true ); $variation->set_stock_quantity( 3 ); $product_ids[] = $variation->save(); WC_Product_Variable::sync( $parent->get_id() );
	vwi_check( voiceweb_wc_product_is_inquiry( $variation ), 'Variations inherit their parent callback mode.' );
	vwi_check( ! is_wp_error( voiceweb_wc_inquiry_product( $parent->get_id(), $variation->get_id() ) ), 'An available matching variation can be requested.' );
	vwi_check( is_wp_error( voiceweb_wc_inquiry_product( $parent->get_id(), 0 ) ), 'Variable requests require a selected variation.' );
	vwi_check( is_wp_error( voiceweb_wc_inquiry_product( $simple->get_id(), $variation->get_id() ) ), 'A variation cannot be attached to another product.' );
	vwi_check( voiceweb_wc_inquiry_phone( '۰۹۱۲۳۴۵۶۷۸۹' ) === '09123456789' && voiceweb_wc_inquiry_phone( '+98 912 345 6789' ) === '09123456789', 'Iranian, international and Persian-digit mobiles normalize.' );
	vwi_check( voiceweb_wc_inquiry_phone( '123456' ) === '' && voiceweb_wc_inquiry_phone( '02112345678' ) === '', 'Malformed mobile and landline values are rejected.' );
	$phone = '09' . str_pad( (string) random_int( 100000000, 999999999 ), 9, '0', STR_PAD_LEFT );
	$data = array( 'product_id' => $simple->get_id(), 'variation_id' => 0, 'phone' => $phone, 'name' => 'Test customer', 'note' => 'Fixture only', 'consent' => true, 'website' => '', 'proof' => vwi_proof( time() - 3 ) );
	vwi_check( vwi_api( $data, 'https://attacker.example' )->get_status() === 403, 'Foreign origins cannot submit private callback forms.' );
	vwi_check( vwi_api( $data, '', false )->get_status() === 403, 'A matching device-bound CSRF token is required.' );
	vwi_check( vwi_api( $data, '', true, 'GET' )->get_status() === 404, 'No public GET endpoint exposes callback leads.' );
	$bad = $data; $bad['proof'] = vwi_proof( time() ); vwi_check( vwi_api( $bad )->get_status() === 400, 'Instant form submissions are rejected.' );
	$bad = $data; $bad['proof'] = vwi_proof( time() - 1900 ); vwi_check( vwi_api( $bad )->get_status() === 400, 'Expired form proofs are rejected.' );
	$bad = $data; $bad['consent'] = false; vwi_check( vwi_api( $bad )->get_status() === 400, 'Customers explicitly agree to a callback.' );
	$bad = $data; $bad['phone'] = array( $phone ); vwi_check( vwi_api( $bad )->get_status() === 400, 'Array-shaped phone values are rejected.' );
	$bad = $data; $bad['website'] = 'spam'; vwi_check( vwi_api( $bad )->get_status() === 400, 'The honeypot rejects unsolicited automated submissions.' );
	$before_orders = wc_get_orders( array( 'limit' => 1, 'paginate' => true ) )->total;
	$response = vwi_api( $data );
	vwi_check( $response->get_status() === 201, 'A guest can submit a valid callback request.' );
	$leads[] = $response->get_data()['reference'];
	vwi_check( ! isset( $response->get_data()['phone'] ) && ! isset( $response->get_data()['name'] ), 'The public success response never returns private contact data.' );
	vwi_check( vwi_api( $data )->get_status() === 429, 'Immediate repeated requests for one mobile are rate-limited.' );
	global $wpdb;
	$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}voiceweb_inquiries WHERE id=%s", $leads[0] ), ARRAY_A );
	vwi_check( $record['phone'] === $phone && $record['status'] === 'new' && (int) $record['user_id'] === 0, 'The private queue records a guest request with its mobile and initial state.' );
	vwi_check( (int) wc_get_product( $simple->get_id() )->get_stock_quantity() === 5 && wc_get_orders( array( 'limit' => 1, 'paginate' => true ) )->total === $before_orders, 'A callback creates no order and reduces no stock.' );
	$user = wp_insert_user( array( 'user_login' => $prefix, 'user_pass' => wp_generate_password( 40 ), 'role' => 'customer', 'user_email' => '' ) );
	if ( is_wp_error( $user ) ) { throw new RuntimeException( 'Fixture customer failed.' ); } $users[] = $user;
	wp_set_current_user( $user );
	$phone2 = '09' . str_pad( (string) random_int( 100000000, 999999999 ), 9, '0', STR_PAD_LEFT );
	$variable_data = $data; $variable_data['phone'] = $phone2; $variable_data['product_id'] = $parent->get_id(); $variable_data['variation_id'] = $variation->get_id();
	$response = vwi_api( $variable_data );
	vwi_check( $response->get_status() === 201, 'A signed-in customer can request a matching variation.' );
	$leads[] = $response->get_data()['reference'];
	$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}voiceweb_inquiries WHERE id=%s", $leads[1] ), ARRAY_A );
	vwi_check( (int) $record['user_id'] === $user, 'The lead preserves the signed-in account.' );
	vwi_check( (int) $record['variation_id'] === $variation->get_id(), 'The lead preserves the selected variation id.' );
	vwi_check( str_contains( json_decode( $record['snapshot'], true )['variation'] ?? '', 'green' ), 'The lead preserves the selected variation label.' );
	vwi_check( ! voiceweb_wc_inquiry_staff_allowed(), 'Customers cannot view the staff callback queue.' );
	$variation->set_stock_quantity( 0 ); $variation->set_stock_status( 'outofstock' ); $variation->save();
	vwi_check( is_wp_error( voiceweb_wc_inquiry_product( $parent->get_id(), $variation->get_id() ) ), 'An out-of-stock variation cannot be requested.' );
	$variation->set_stock_quantity( 3 ); $variation->set_stock_status( 'instock' ); $variation->set_status( 'private' ); $variation->save();
	vwi_check( is_wp_error( voiceweb_wc_inquiry_product( $parent->get_id(), $variation->get_id() ) ), 'A disabled/private variation cannot be requested.' );
	$simple->set_catalog_visibility( 'hidden' ); $simple->save();
	vwi_check( is_wp_error( voiceweb_wc_inquiry_product( $simple->get_id() ) ), 'Hidden products cannot be used as contact-form spam targets.' );
	$simple->set_catalog_visibility( 'visible' ); $simple->save();
	vwi_check( voiceweb_wc_inquiry_cart_validation( true, $simple->get_id(), 1 ) === false, 'Classic add-to-cart refuses callback products.' ); wc_clear_notices();
	$thrown = false; try { voiceweb_wc_inquiry_store_cart_validation( $simple ); } catch ( \Automattic\WooCommerce\StoreApi\Exceptions\RouteException $error ) { $thrown = true; }
	vwi_check( $thrown, 'Store API also refuses callback products.' );
	$key = $prefix . '|atomic';
	vwi_check( voiceweb_wc_inquiry_limit( $key, 1, 600 ) && ! voiceweb_wc_inquiry_limit( $key, 1, 600 ), 'The durable conditional counter admits only its configured number.' );
	$limit_hashes = array_map( static fn( $key ) => hash_hmac( 'sha256', $key, wp_salt( 'auth' ) ), array( 'ip|' . $prefix, 'device|' . $device, 'phone|' . $phone, 'cooldown|' . $phone, 'phone|' . $phone2, 'cooldown|' . $phone2, $key ) );
} catch ( Throwable $error ) { $failure = $error; }
finally {
	global $wpdb;
	foreach ( $leads as $id ) { $wpdb->delete( $wpdb->prefix . 'voiceweb_inquiries', array( 'id' => $id ) ); }
	// Find all own fixture records even when a test fails before it collects the returned reference.
	if ( $product_ids ) { $wpdb->query( "DELETE FROM {$wpdb->prefix}voiceweb_inquiries WHERE product_id IN (" . implode( ',', array_map( 'absint', $product_ids ) ) . ')' ); }
	foreach ( array_filter( array_merge( array( 'ip|' . $prefix, 'device|' . $device, $prefix . '|atomic' ), isset( $phone ) ? array( 'phone|' . $phone, 'cooldown|' . $phone ) : array(), isset( $phone2 ) ? array( 'phone|' . $phone2, 'cooldown|' . $phone2 ) : array() ) ) as $key ) { $wpdb->delete( $wpdb->prefix . 'voiceweb_inquiry_limits', array( 'key_hash' => hash_hmac( 'sha256', $key, wp_salt( 'auth' ) ) ) ); }
	foreach ( array_reverse( $product_ids ) as $id ) { $product = wc_get_product( $id ); if ( $product ) { $product->delete( true ); } }
	wp_set_current_user( 0 ); foreach ( $users as $id ) { wp_delete_user( $id ); }
	if ( $old_mode === null ) { delete_option( 'voiceweb_wc_purchase_mode' ); } else { update_option( 'voiceweb_wc_purchase_mode', $old_mode ); }
	if ( $old_cookie === null ) { unset( $_COOKIE['vw_inquiry_device'] ); } else { $_COOKIE['vw_inquiry_device'] = $old_cookie; }
	if ( $old_ip === null ) { unset( $_SERVER['REMOTE_ADDR'] ); } else { $_SERVER['REMOTE_ADDR'] = $old_ip; }
}
if ( $failure ) { fwrite( STDERR, 'FAIL: ' . $failure->getMessage() . PHP_EOL ); exit( 1 ); }
echo 'PASS: ' . $checks . ' callback-mode checks; fixtures removed, no external mail/network or financial orders.' . PHP_EOL;
