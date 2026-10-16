<?php
/** Optional callback requests. Native purchases remain WooCommerce's responsibility. */
defined( 'ABSPATH' ) || exit;

/** Variations always inherit the parent product's purchase mode. */
function voiceweb_wc_product_is_inquiry( $product ): bool {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product instanceof WC_Product ) { return false; }
	if ( $product->is_type( 'variation' ) ) { $product = wc_get_product( $product->get_parent_id() ); }
	if ( ! $product || ! $product->is_type( array( 'simple', 'variable' ) ) ) { return false; }
	$mode = (string) $product->get_meta( '_voiceweb_purchase_mode' );
	if ( ! in_array( $mode, array( 'purchase', 'inquiry' ), true ) ) { $mode = get_option( 'voiceweb_wc_purchase_mode', 'purchase' ); }
	return $mode === 'inquiry';
}

/** Catalog variable buttons lead to the native variation selection first. */
function voiceweb_wc_inquiry_button( $product, string $class = '' ): string {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product instanceof WC_Product || ! voiceweb_wc_product_is_inquiry( $product ) ) { return ''; }
	$class = trim( 'vw-inquiry-trigger ' . $class );
	if ( $product->is_type( 'variable' ) ) {
		return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $product->get_permalink() ) . '">انتخاب مدل و درخواست تماس</a>';
	}
	return '<button type="button" class="' . esc_attr( $class ) . '" data-vw-inquiry-product="' . $product->get_id() . '" data-vw-inquiry-title="' . esc_attr( $product->get_name() ) . '">درخواست تماس</button>';
}

function voiceweb_wc_inquiry_loop_button( string $html, WC_Product $product ): string {
	return voiceweb_wc_product_is_inquiry( $product ) ? voiceweb_wc_inquiry_button( $product, 'button' ) : $html;
}

function voiceweb_wc_inquiry_settings( array $settings, string $section = '' ): array {
	if ( $section !== '' ) { return $settings; }
	$settings[] = array( 'title' => 'درخواست تماس ویس وب', 'type' => 'title', 'id' => 'voiceweb_inquiry_settings', 'desc' => 'حالت پیش‌فرض محصولات ساده و متغیر. هر محصول می‌تواند در بخش اطلاعات محصول این تنظیم را تغییر دهد. درخواست تماس، سفارش مالی یا پرداخت ایجاد نمی‌کند.' );
	$settings[] = array( 'title' => 'روش ثبت درخواست', 'id' => 'voiceweb_wc_purchase_mode', 'type' => 'select', 'default' => 'purchase', 'options' => array( 'purchase' => 'خرید و پرداخت معمولی', 'inquiry' => 'شماره تماس و پیگیری فروشگاه' ), 'desc_tip' => true, 'desc' => 'تغییر حالت، پرداخت سفارش‌های قبلاً ثبت‌شده را غیرفعال نمی‌کند.' );
	$settings[] = array( 'type' => 'sectionend', 'id' => 'voiceweb_inquiry_settings' );
	return $settings;
}

function voiceweb_wc_inquiry_product_field(): void {
	woocommerce_wp_select( array( 'id' => '_voiceweb_purchase_mode', 'label' => 'روش خرید ویس وب', 'wrapper_class' => 'show_if_simple show_if_variable', 'options' => array( '' => 'پیش‌فرض فروشگاه', 'purchase' => 'خرید و پرداخت', 'inquiry' => 'درخواست تماس' ), 'desc_tip' => true, 'description' => 'حالت درخواست تماس، افزودن محصول به سبد و پرداخت آن را مسدود می‌کند؛ سفارش‌های قبلی محفوظ می‌مانند.' ) );
}

function voiceweb_wc_inquiry_save_product( WC_Product $product ): void {
	$mode = isset( $_POST['_voiceweb_purchase_mode'] ) && is_scalar( $_POST['_voiceweb_purchase_mode'] ) ? sanitize_key( wp_unslash( $_POST['_voiceweb_purchase_mode'] ) ) : '';
	$product->update_meta_data( '_voiceweb_purchase_mode', in_array( $mode, array( 'purchase', 'inquiry' ), true ) ? $mode : '' );
}

/** Keep native variable forms and Variation Swatches; replace only their purchase action. */
function voiceweb_wc_inquiry_product_setup(): void {
	if ( ! is_product() ) { return; }
	$product = wc_get_product( get_queried_object_id() );
	if ( ! voiceweb_wc_product_is_inquiry( $product ) ) { return; }
	if ( $product->is_type( 'variable' ) ) {
		remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
		add_action( 'woocommerce_single_variation', 'voiceweb_wc_inquiry_variable_button', 20 );
	} else {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		add_action( 'woocommerce_single_product_summary', 'voiceweb_wc_inquiry_simple_form', 30 );
	}
}

function voiceweb_wc_inquiry_simple_form(): void {
	global $product;
	if ( ! $product || ! $product->is_in_stock() ) { echo '<p class="stock out-of-stock">در حال حاضر موجود نیست.</p>'; return; }
	do_action( 'woocommerce_before_add_to_cart_form' );
	echo '<div class="vw-inquiry-purchase"><p>شماره‌تان را بگذارید؛ برای راهنمایی و هماهنگی خرید با شما تماس می‌گیریم.</p>' . voiceweb_wc_inquiry_button( $product, 'button alt' ) . '<small>بدون پرداخت آنلاین و بدون ثبت سفارش مالی</small></div>';
	do_action( 'woocommerce_after_add_to_cart_form' );
}

function voiceweb_wc_inquiry_variable_button(): void {
	global $product;
	echo '<div class="woocommerce-variation-add-to-cart variations_button vw-inquiry-purchase"><p>ابتدا گزینه‌های محصول را انتخاب کنید، سپس درخواست تماس بدهید.</p><button type="button" class="button alt vw-inquiry-trigger" data-vw-inquiry-product="' . absint( $product->get_id() ) . '" data-vw-inquiry-title="' . esc_attr( $product->get_name() ) . '" data-vw-inquiry-variable="1" disabled>انتخاب مدل و درخواست تماس</button><input type="hidden" name="product_id" value="' . absint( $product->get_id() ) . '"><input type="hidden" name="variation_id" class="variation_id" value="0"><small>درخواست تماس به معنی خرید یا رزرو موجودی نیست.</small></div>';
}

function voiceweb_wc_inquiry_cart_validation( $passed, $product_id, $quantity, $variation_id = 0 ) {
	if ( voiceweb_wc_product_is_inquiry( $variation_id ?: $product_id ) ) {
		wc_add_notice( 'این محصول با درخواست تماس ارائه می‌شود. از صفحهٔ محصول، گزینه‌ها را انتخاب و درخواست تماس ثبت کنید.', 'error' );
		return false;
	}
	return $passed;
}

function voiceweb_wc_inquiry_store_cart_validation( WC_Product $product ): void {
	if ( voiceweb_wc_product_is_inquiry( $product ) ) { throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'voiceweb_inquiry_product', 'این محصول فقط با درخواست تماس ارائه می‌شود.', 400 ); }
}

/** Products already in a cart when an administrator changes mode cannot bypass checkout. */
function voiceweb_wc_inquiry_cart_error(): string {
	if ( ! WC()->cart ) { return ''; }
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( voiceweb_wc_product_is_inquiry( $item['data'] ?? null ) ) { return 'روش خرید یکی از محصولات سبد به درخواست تماس تغییر کرده است. آن محصول را از سبد بردارید و از صفحهٔ محصول درخواست تماس بدهید؛ بقیهٔ خریدتان قابل پرداخت است.'; }
	}
	return '';
}

function voiceweb_wc_inquiry_check_cart(): void {
	$message = voiceweb_wc_inquiry_cart_error();
	if ( $message && ! wc_has_notice( $message, 'error' ) ) { wc_add_notice( $message, 'error' ); }
}

function voiceweb_wc_inquiry_store_errors( WP_Error $errors ): void {
	$message = voiceweb_wc_inquiry_cart_error();
	if ( $message ) { $errors->add( 'voiceweb_inquiry_cart', $message ); }
}

/** Private records and durable atomic rate counters. No order, stock, mail or SMS writes. */
function voiceweb_wc_inquiry_install(): void {
	if ( get_option( 'voiceweb_inquiries_version' ) === '1' ) { return; }
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$collate = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE {$wpdb->prefix}voiceweb_inquiries (
	 id char(36) NOT NULL,
	 user_id bigint(20) unsigned NOT NULL DEFAULT 0,
	 product_id bigint(20) unsigned NOT NULL,
	 variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
	 snapshot longtext NOT NULL,
	 phone varchar(11) NOT NULL,
	 customer_name varchar(120) NOT NULL DEFAULT '',
	 customer_note text NOT NULL,
	 status varchar(16) NOT NULL DEFAULT 'new',
	 staff_note text NOT NULL,
	 revision int(10) unsigned NOT NULL DEFAULT 1,
	 reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
	 created_at datetime NOT NULL,
	 updated_at datetime NOT NULL,
	 PRIMARY KEY  (id),
	 KEY status_created (status,created_at),
	 KEY user_id (user_id)
	) ENGINE=InnoDB {$collate};" );
	dbDelta( "CREATE TABLE {$wpdb->prefix}voiceweb_inquiry_limits (
	 key_hash char(64) NOT NULL,
	 bucket bigint(20) unsigned NOT NULL DEFAULT 0,
	 hits int(10) unsigned NOT NULL DEFAULT 0,
	 expires bigint(20) unsigned NOT NULL,
	 PRIMARY KEY  (key_hash,bucket),
	 KEY expires (expires)
	) ENGINE=InnoDB {$collate};" );
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'voiceweb_inquiries' ) ) ) && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'voiceweb_inquiry_limits' ) ) ) ) { update_option( 'voiceweb_inquiries_version', '1', false ); }
}

function voiceweb_wc_inquiry_cleanup(): void {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}voiceweb_inquiry_limits WHERE expires < %d LIMIT 10000", time() ) );
}

function voiceweb_wc_inquiry_schedule(): void {
	if ( ! wp_next_scheduled( 'voiceweb_inquiry_cleanup' ) ) { wp_schedule_event( time() + 3600, 'daily', 'voiceweb_inquiry_cleanup' ); }
}

function voiceweb_wc_inquiry_phone( string $value ): string {
	$value = strtr( $value, array_combine( preg_split( '//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY ), str_split( '01234567890123456789' ) ) );
	$value = preg_replace( '/[\s()\-]+/u', '', trim( $value ) );
	if ( str_starts_with( $value, '+98' ) ) { $value = '0' . substr( $value, 3 ); }
	elseif ( str_starts_with( $value, '0098' ) ) { $value = '0' . substr( $value, 4 ); }
	elseif ( preg_match( '/^98\d{10}$/D', $value ) ) { $value = '0' . substr( $value, 2 ); }
	return preg_match( '/^09\d{9}$/D', $value ) ? $value : '';
}

function voiceweb_wc_inquiry_origin( WP_REST_Request $request ) {
	$source = wp_parse_url( $request->get_header( 'origin' ) ?: $request->get_header( 'referer' ) );
	$target = wp_parse_url( home_url( '/' ) );
	$port = static fn( $url ) => (int) ( $url['port'] ?? ( ( $url['scheme'] ?? '' ) === 'https' ? 443 : 80 ) );
	if ( ! is_array( $source ) || empty( $source['host'] ) || strtolower( $source['host'] ) !== strtolower( $target['host'] ?? '' ) || ( $source['scheme'] ?? '' ) !== ( $target['scheme'] ?? '' ) || $port( $source ) !== $port( $target ) ) { return new WP_Error( 'voiceweb_inquiry_origin', 'درخواست معتبر نیست. صفحه را تازه کنید.', array( 'status' => 403 ) ); }
	return true;
}

function voiceweb_wc_inquiry_device(): string {
	$value = $_COOKIE['vw_inquiry_device'] ?? '';
	return is_string( $value ) && preg_match( '/^[a-f0-9]{64}$/D', $value ) ? $value : '';
}

function voiceweb_wc_inquiry_token( string $device, int $bucket ): string {
	return hash_hmac( 'sha256', 'inquiry-csrf|' . $device . '|' . $bucket, wp_salt( 'auth' ) );
}

function voiceweb_wc_inquiry_permission( WP_REST_Request $request ) {
	$origin = voiceweb_wc_inquiry_origin( $request );
	if ( is_wp_error( $origin ) ) { return $origin; }
	$device = voiceweb_wc_inquiry_device(); $token = $request->get_header( 'x-vw-inquiry-csrf' ); $bucket = (int) floor( time() / 3600 );
	if ( ! $device || ! $token || ( ! hash_equals( voiceweb_wc_inquiry_token( $device, $bucket ), $token ) && ! hash_equals( voiceweb_wc_inquiry_token( $device, $bucket - 1 ), $token ) ) ) { return new WP_Error( 'voiceweb_inquiry_session', 'نشست فرم منقضی شده است. فرم را دوباره باز کنید.', array( 'status' => 403 ) ); }
	return true;
}

function voiceweb_wc_inquiry_session(): array {
	$device = voiceweb_wc_inquiry_device();
	if ( ! $device ) {
		$device = bin2hex( random_bytes( 32 ) );
		setcookie( 'vw_inquiry_device', $device, array( 'expires' => time() + MONTH_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		$_COOKIE['vw_inquiry_device'] = $device;
	}
	$issued = time();
	return array( 'csrf' => voiceweb_wc_inquiry_token( $device, (int) floor( $issued / 3600 ) ), 'proof' => $issued . '.' . hash_hmac( 'sha256', 'inquiry-form|' . $device . '|' . $issued, wp_salt( 'auth' ) ) );
}

/** Conditional UPDATE admits at most the configured count even for concurrent requests. */
function voiceweb_wc_inquiry_limit( string $key, int $max, int $seconds, bool $cooldown = false ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'voiceweb_inquiry_limits'; $now = time();
	$hash = hash_hmac( 'sha256', $key, wp_salt( 'auth' ) ); $bucket = $cooldown ? 0 : (int) floor( $now / $seconds );
	$expires = $cooldown ? $now + $seconds : ( $bucket + 1 ) * $seconds;
	if ( $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$table} (key_hash,bucket,hits,expires) VALUES (%s,%d,0,%d)", $hash, $bucket, $expires ) ) === false ) { return false; }
	if ( $cooldown ) { $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hits=0,expires=%d WHERE key_hash=%s AND bucket=0 AND expires<=%d", $expires, $hash, $now ) ); }
	return $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hits=hits+1 WHERE key_hash=%s AND bucket=%d AND hits<%d AND expires>%d", $hash, $bucket, $max, $now ) ) === 1;
}

function voiceweb_wc_inquiry_product( int $product_id, int $variation_id = 0 ) {
	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_type( array( 'simple', 'variable' ) ) || $product->get_status() !== 'publish' || $product->get_catalog_visibility() === 'hidden' || ! $product->is_visible() || ! $product->is_in_stock() || ! voiceweb_wc_product_is_inquiry( $product ) ) { return new WP_Error( 'voiceweb_inquiry_unavailable', 'این محصول در حال حاضر امکان ثبت درخواست تماس ندارد.', array( 'status' => 400 ) ); }
	if ( $product->is_type( 'variable' ) ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation instanceof WC_Product_Variation || $variation->get_parent_id() !== $product_id || $variation->get_status() !== 'publish' || ! $variation->variation_is_visible() || ! $variation->variation_is_active() || ! $variation->is_in_stock() ) { return new WP_Error( 'voiceweb_inquiry_variation', 'گزینه‌های موجود محصول را کامل انتخاب کنید.', array( 'status' => 400 ) ); }
		return $variation;
	}
	return $variation_id ? new WP_Error( 'voiceweb_inquiry_variation', 'گزینهٔ محصول معتبر نیست.', array( 'status' => 400 ) ) : $product;
}

function voiceweb_wc_inquiry_create( WP_REST_Request $request ) {
	if ( strlen( $request->get_body() ) > 8192 ) { return new WP_Error( 'voiceweb_inquiry_form', 'اطلاعات فرم بیش از حد مجاز است.', array( 'status' => 400 ) ); }
	$data = $request->get_json_params();
	if ( ! is_array( $data ) ) { return new WP_Error( 'voiceweb_inquiry_form', 'فرم معتبر نیست.', array( 'status' => 400 ) ); }
	foreach ( array( 'phone', 'name', 'note', 'website', 'proof' ) as $field ) {
		if ( isset( $data[ $field ] ) && ( ! is_string( $data[ $field ] ) || strlen( $data[ $field ] ) > 4096 ) ) { return new WP_Error( 'voiceweb_inquiry_form', 'اطلاعات فرم معتبر نیست.', array( 'status' => 400 ) ); }
	}
	$phone = voiceweb_wc_inquiry_phone( $data['phone'] ?? '' );
	$name = sanitize_text_field( $data['name'] ?? '' ); $note = sanitize_textarea_field( $data['note'] ?? '' );
	if ( ! $phone || mb_strlen( $name ) > 120 || mb_strlen( $note ) > 1000 || empty( $data['consent'] ) || $data['consent'] !== true || ! empty( $data['website'] ) ) { return new WP_Error( 'voiceweb_inquiry_form', 'شمارهٔ موبایل معتبر و رضایت برای تماس را وارد کنید. نام حداکثر ۱۲۰ و توضیح حداکثر ۱۰۰۰ کاراکتر است.', array( 'status' => 400 ) ); }
	$proof = explode( '.', $data['proof'] ?? '', 2 ); $issued = isset( $proof[0] ) && ctype_digit( $proof[0] ) ? (int) $proof[0] : 0;
	if ( ! $issued || time() - $issued < 2 || time() - $issued > 1800 || empty( $proof[1] ) || ! hash_equals( hash_hmac( 'sha256', 'inquiry-form|' . voiceweb_wc_inquiry_device() . '|' . $issued, wp_salt( 'auth' ) ), $proof[1] ) ) { return new WP_Error( 'voiceweb_inquiry_proof', 'چند لحظه بعد دوباره تلاش کنید؛ اگر فرم مدت زیادی باز بوده آن را دوباره باز کنید.', array( 'status' => 400 ) ); }
	foreach ( array( 'product_id', 'variation_id' ) as $field ) {
		if ( isset( $data[ $field ] ) && ( ! is_int( $data[ $field ] ) || $data[ $field ] < 0 ) ) { return new WP_Error( 'voiceweb_inquiry_product', 'محصول معتبر نیست.', array( 'status' => 400 ) ); }
	}
	$product_id = (int) ( $data['product_id'] ?? 0 ); $variation_id = (int) ( $data['variation_id'] ?? 0 );
	$product = voiceweb_wc_inquiry_product( $product_id, $variation_id );
	if ( is_wp_error( $product ) ) { return $product; }
	// Do not trust client-provided proxy headers. A trusted deployment can override this resolver.
	$ip = (string) apply_filters( 'voiceweb/inquiries/client-ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
	if ( ! voiceweb_wc_inquiry_limit( 'ip|' . $ip, 20, HOUR_IN_SECONDS ) || ! voiceweb_wc_inquiry_limit( 'device|' . voiceweb_wc_inquiry_device(), 10, HOUR_IN_SECONDS ) || ! voiceweb_wc_inquiry_limit( 'phone|' . $phone, 10, DAY_IN_SECONDS ) || ! voiceweb_wc_inquiry_limit( 'cooldown|' . $phone, 1, 60, true ) ) { return new WP_Error( 'voiceweb_inquiry_limit', 'درخواست‌ها زیاد شده است. کمی بعد دوباره تلاش کنید.', array( 'status' => 429 ) ); }
	$parent = $variation_id ? wc_get_product( $product_id ) : $product;
	$snapshot = array( 'product' => $parent->get_name(), 'sku' => $product->get_sku(), 'variation' => $variation_id ? wc_get_formatted_variation( $product, true, false, false ) : '', 'price' => $product->get_price(), 'currency' => get_woocommerce_currency() );
	global $wpdb;
	$id = wp_generate_uuid4(); $now = current_time( 'mysql', true );
	$saved = $wpdb->insert( $wpdb->prefix . 'voiceweb_inquiries', array( 'id' => $id, 'user_id' => get_current_user_id(), 'product_id' => $product_id, 'variation_id' => $variation_id, 'snapshot' => wp_json_encode( $snapshot ), 'phone' => $phone, 'customer_name' => $name, 'customer_note' => $note, 'status' => 'new', 'staff_note' => '', 'created_at' => $now, 'updated_at' => $now ) );
	if ( ! $saved ) { return new WP_Error( 'voiceweb_inquiry_save', 'ثبت درخواست ممکن نشد. بعداً دوباره تلاش کنید.', array( 'status' => 503 ) ); }
	return new WP_REST_Response( array( 'message' => 'درخواست شما ثبت شد. برای راهنمایی و هماهنگی خرید با شما تماس می‌گیریم.', 'reference' => $id ), 201 );
}

function voiceweb_wc_inquiry_routes(): void {
	register_rest_route( 'voiceweb/v1', '/inquiries/session', array( 'methods' => 'POST', 'permission_callback' => 'voiceweb_wc_inquiry_origin', 'callback' => 'voiceweb_wc_inquiry_session' ) );
	register_rest_route( 'voiceweb/v1', '/inquiries', array( 'methods' => 'POST', 'permission_callback' => 'voiceweb_wc_inquiry_permission', 'callback' => 'voiceweb_wc_inquiry_create' ) );
}

function voiceweb_wc_inquiry_private_response( $response, $server, WP_REST_Request $request ) {
	if ( str_starts_with( $request->get_route(), '/voiceweb/v1/inquiries' ) && $response instanceof WP_REST_Response ) { $response->header( 'Cache-Control', 'no-store, private, max-age=0' ); $response->header( 'Vary', 'Cookie, Origin' ); }
	return $response;
}

function voiceweb_wc_inquiry_asset_keys( array $keys ): array {
	if ( is_front_page() || is_woocommerce() || is_cart() || ( is_checkout() && ! is_wc_endpoint_url( 'order-pay' ) && ! is_order_received_page() ) ) { $keys[] = 'woocommerce-inquiry'; }
	return $keys;
}

function voiceweb_wc_inquiry_script_data(): void {
	if ( ! wp_script_is( 'cn-page-woocommerce-inquiry', 'enqueued' ) ) { return; }
	wp_add_inline_script( 'cn-page-woocommerce-inquiry', 'window.VoiceWebInquiry=' . wp_json_encode( array( 'api' => rest_url( 'voiceweb/v1/inquiries' ), 'nonce' => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '' ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
}

function voiceweb_wc_inquiry_script_dependencies( array $deps, string $handle, string $key ): array {
	if ( $key === 'woocommerce-inquiry' ) { $deps[] = 'jquery'; }
	return array_values( array_unique( $deps ) );
}

function voiceweb_wc_inquiry_modal(): void {
	if ( ! wp_script_is( 'cn-page-woocommerce-inquiry', 'enqueued' ) ) { return; }
	?>
	<dialog class="vw-inquiry-dialog" id="vw-inquiry-dialog" aria-labelledby="vw-inquiry-heading">
		<button class="vw-inquiry-close" type="button" data-vwi-close aria-label="بستن فرم">×</button>
		<div class="vw-inquiry-intro"><span>یک گفت‌وگوی کوتاه، یک انتخاب مطمئن</span><h2 id="vw-inquiry-heading">برای خرید راهنمایی می‌خواهید؟</h2><p>شماره‌تان را ثبت کنید؛ تیم فروش برای هماهنگی همین محصول با شما تماس می‌گیرد.</p><strong data-vwi-product-label></strong></div>
		<form data-vwi-form>
			<label>شمارهٔ موبایل <span aria-hidden="true">*</span><input name="phone" type="tel" inputmode="tel" autocomplete="tel" required maxlength="24" placeholder="۰۹۱۲۱۲۳۴۵۶۷" dir="ltr"></label>
			<label>نام شما <small>اختیاری</small><input name="name" type="text" autocomplete="name" maxlength="120"></label>
			<label>توضیح برای تماس <small>اختیاری</small><textarea name="note" rows="3" maxlength="1000" placeholder="مثلاً رنگ موردنظر یا زمان مناسب برای تماس"></textarea></label>
			<div class="vw-inquiry-honeypot" aria-hidden="true"><label>وب‌سایت<input name="website" type="text" tabindex="-1" autocomplete="off"></label></div>
			<label class="vw-inquiry-consent"><input name="consent" type="checkbox" required><span>با تماس فروشگاه دربارهٔ این درخواست موافقم.</span></label>
			<p class="vw-inquiry-feedback" data-vwi-feedback role="status" aria-live="polite" hidden></p>
			<button class="vw-inquiry-submit" type="submit">ثبت درخواست تماس <span aria-hidden="true">←</span></button>
			<small class="vw-inquiry-disclaimer">این فرم برای پیگیری خرید است؛ پرداخت، ثبت سفارش یا رزرو موجودی انجام نمی‌شود. شمارهٔ شما عمومی نمایش داده نمی‌شود.</small>
		</form>
	</dialog>
	<?php
}

function voiceweb_wc_inquiry_staff_allowed(): bool { return current_user_can( 'manage_woocommerce' ); }

function voiceweb_wc_inquiry_admin_menu(): void {
	add_submenu_page( 'woocommerce', 'درخواست‌های تماس', 'درخواست‌های تماس', 'manage_woocommerce', 'voiceweb-inquiries', 'voiceweb_wc_inquiry_admin_page' );
}

function voiceweb_wc_inquiry_statuses(): array { return array( 'new' => 'جدید', 'contacted' => 'تماس گرفته شده', 'closed' => 'بسته شده' ); }

function voiceweb_wc_inquiry_admin_page(): void {
	if ( ! voiceweb_wc_inquiry_staff_allowed() ) { wp_die( 'دسترسی مجاز نیست.', 403 ); }
	global $wpdb;
	$table = $wpdb->prefix . 'voiceweb_inquiries'; $statuses = voiceweb_wc_inquiry_statuses();
	$status = isset( $_GET['inquiry_status'] ) && is_scalar( $_GET['inquiry_status'] ) ? sanitize_key( wp_unslash( $_GET['inquiry_status'] ) ) : '';
	$page = max( 1, absint( $_GET['paged'] ?? 1 ) ); $where = isset( $statuses[ $status ] ) ? $wpdb->prepare( ' WHERE status=%s', $status ) : '';
	$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}{$where}" );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table}{$where} ORDER BY created_at DESC,id DESC LIMIT 20 OFFSET %d", ( $page - 1 ) * 20 ), ARRAY_A );
	echo '<div class="wrap"><h1>درخواست‌های تماس</h1><p>این درخواست‌ها سفارش مالی نیستند. قبل از هماهنگی خرید، قیمت و موجودی فعلی را بررسی کنید. شمارهٔ ثبت‌شده در این فرم با پیامک تأیید نشده است.</p><form method="get"><input type="hidden" name="page" value="voiceweb-inquiries"><select name="inquiry_status"><option value="">همهٔ وضعیت‌ها</option>';
	foreach ( $statuses as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>'; }
	echo '</select> <button class="button">فیلتر</button></form><p>' . esc_html( number_format_i18n( $count ) ) . ' درخواست</p>';
	if ( isset( $_GET['updated'] ) ) { echo '<div class="notice notice-success"><p>درخواست به‌روز شد.</p></div>'; }
	foreach ( $rows as $row ) {
		$snapshot = json_decode( $row['snapshot'], true ) ?: array();
		echo '<section style="padding:20px;margin:16px 0;background:#fff;border:1px solid #ddd;border-radius:12px"><h2>' . esc_html( $snapshot['product'] ?? 'محصول' ) . '</h2><p>' . esc_html( $snapshot['variation'] ?? '' ) . '</p><p><strong>' . esc_html( $row['customer_name'] ?: 'نام وارد نشده' ) . '</strong> · <a href="tel:' . esc_attr( $row['phone'] ) . '">' . esc_html( $row['phone'] ) . '</a></p><p>' . nl2br( esc_html( $row['customer_note'] ) ) . '</p><p>ثبت: ' . esc_html( get_date_from_gmt( $row['created_at'], 'Y/m/d H:i' ) ) . ' · <code>' . esc_html( $row['id'] ) . '</code></p>';
		if ( $row['user_id'] ) { echo '<p>شناسهٔ حساب: ' . absint( $row['user_id'] ) . '</p>'; }
		if ( get_post_status( $row['product_id'] ) ) { echo '<p><a href="' . esc_url( get_edit_post_link( $row['product_id'] ) ?: get_permalink( $row['product_id'] ) ) . '">بررسی محصول فعلی</a></p>'; }
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="voiceweb_review_inquiry"><input type="hidden" name="id" value="' . esc_attr( $row['id'] ) . '"><input type="hidden" name="revision" value="' . absint( $row['revision'] ) . '">';
		wp_nonce_field( 'voiceweb_review_inquiry_' . $row['id'] );
		echo '<label>وضعیت <select name="status">';
		foreach ( $statuses as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $row['status'], $key, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select></label><p><label>یادداشت داخلی پیگیری<br><textarea name="staff_note" rows="3" maxlength="2000" style="width:min(100%,700px)">' . esc_textarea( $row['staff_note'] ) . '</textarea></label></p><button class="button button-primary">ذخیرهٔ پیگیری</button> <button class="button" name="delete" value="1" onclick="return confirm(\'این درخواست و شمارهٔ تماس آن برای همیشه حذف شود؟\')">حذف درخواست</button></form>';
		if ( $row['reviewer_id'] ) { echo '<p>آخرین پیگیری: مدیر #' . absint( $row['reviewer_id'] ) . ' · ' . esc_html( get_date_from_gmt( $row['updated_at'], 'Y/m/d H:i' ) ) . '</p>'; }
		echo '</section>';
	}
	$links = paginate_links( array( 'base' => add_query_arg( array( 'page' => 'voiceweb-inquiries', 'inquiry_status' => $status, 'paged' => '%#%' ), admin_url( 'admin.php' ) ), 'current' => $page, 'total' => max( 1, (int) ceil( $count / 20 ) ) ) );
	echo '<div class="tablenav">' . wp_kses_post( $links ) . '</div></div>';
}

function voiceweb_wc_inquiry_review(): void {
	if ( ! voiceweb_wc_inquiry_staff_allowed() ) { wp_die( 'دسترسی مجاز نیست.', 403 ); }
	$id = isset( $_POST['id'] ) && is_string( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	if ( ! preg_match( '/^[a-f0-9-]{36}$/D', $id ) ) { wp_die( 'درخواست معتبر نیست.', 400 ); }
	check_admin_referer( 'voiceweb_review_inquiry_' . $id );
	$status = isset( $_POST['status'] ) && is_scalar( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
	$note = isset( $_POST['staff_note'] ) && is_string( $_POST['staff_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['staff_note'] ) ) : '';
	if ( ! isset( voiceweb_wc_inquiry_statuses()[ $status ] ) || mb_strlen( $note ) > 2000 ) { wp_die( 'اطلاعات پیگیری معتبر نیست.', 400 ); }
	global $wpdb;
	$where = array( 'id' => $id, 'revision' => absint( $_POST['revision'] ?? 0 ) );
	if ( ! empty( $_POST['delete'] ) ) { $result = $wpdb->delete( $wpdb->prefix . 'voiceweb_inquiries', $where ); }
	else { $result = $wpdb->update( $wpdb->prefix . 'voiceweb_inquiries', array( 'status' => $status, 'staff_note' => $note, 'reviewer_id' => get_current_user_id(), 'revision' => $where['revision'] + 1, 'updated_at' => current_time( 'mysql', true ) ), $where ); }
	if ( $result !== 1 ) { wp_die( 'این درخواست قبلاً تغییر کرده یا حذف شده است. فهرست را تازه کنید.', 409 ); }
	wp_safe_redirect( add_query_arg( array( 'page' => 'voiceweb-inquiries', 'updated' => 1 ), admin_url( 'admin.php' ) ) ); exit;
}

/** Keep private lead data out of account/order endpoints; remove the account link on deletion. */
function voiceweb_wc_inquiry_anonymize_user( int $user_id ): void {
	global $wpdb;
	$wpdb->update( $wpdb->prefix . 'voiceweb_inquiries', array( 'user_id' => 0 ), array( 'user_id' => $user_id ) );
}

add_filter( 'woocommerce_get_settings_products', 'voiceweb_wc_inquiry_settings', 20, 2 );
add_action( 'woocommerce_product_options_general_product_data', 'voiceweb_wc_inquiry_product_field' );
add_action( 'woocommerce_admin_process_product_object', 'voiceweb_wc_inquiry_save_product' );
add_action( 'wp', 'voiceweb_wc_inquiry_product_setup', 30 );
add_filter( 'woocommerce_loop_add_to_cart_link', 'voiceweb_wc_inquiry_loop_button', 25, 2 );
add_filter( 'woocommerce_add_to_cart_validation', 'voiceweb_wc_inquiry_cart_validation', 20, 4 );
add_action( 'woocommerce_store_api_validate_add_to_cart', 'voiceweb_wc_inquiry_store_cart_validation', 20 );
add_action( 'woocommerce_check_cart_items', 'voiceweb_wc_inquiry_check_cart', 20 );
add_action( 'woocommerce_store_api_cart_errors', 'voiceweb_wc_inquiry_store_errors', 20 );
add_action( 'init', 'voiceweb_wc_inquiry_install', 6 );
add_action( 'init', 'voiceweb_wc_inquiry_schedule', 8 );
add_action( 'voiceweb_inquiry_cleanup', 'voiceweb_wc_inquiry_cleanup' );
add_action( 'rest_api_init', 'voiceweb_wc_inquiry_routes' );
add_filter( 'rest_post_dispatch', 'voiceweb_wc_inquiry_private_response', 10, 3 );
add_filter( 'cn/page-keys', 'voiceweb_wc_inquiry_asset_keys', 25 );
add_filter( 'cn/asset/script-deps', 'voiceweb_wc_inquiry_script_dependencies', 25, 3 );
add_action( 'wp_enqueue_scripts', 'voiceweb_wc_inquiry_script_data', 25 );
add_action( 'wp_footer', 'voiceweb_wc_inquiry_modal', 26 );
add_action( 'admin_menu', 'voiceweb_wc_inquiry_admin_menu', 30 );
add_action( 'admin_post_voiceweb_review_inquiry', 'voiceweb_wc_inquiry_review' );
add_action( 'deleted_user', 'voiceweb_wc_inquiry_anonymize_user' );
