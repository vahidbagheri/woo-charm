<?php
/** Backed-up, idempotent catalog copy update; --apply writes, --restore restores the saved originals. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local'; $_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
add_filter( 'pre_wp_mail', '__return_false' );
add_filter( 'pre_http_request', static fn() => new WP_Error( 'launch_copy_no_network', 'No external requests.' ), PHP_INT_MAX );
$copy = require dirname( __DIR__ ) . '/config/launch-product-copy.php';
$apply = in_array( '--apply', $argv, true ); $restore = in_array( '--restore', $argv, true );
$result = array( 'updated' => 0, 'skipped' => 0, 'restored' => 0, 'missing' => array() );
foreach ( wc_get_products( array( 'limit' => -1, 'status' => 'publish' ) ) as $product ) {
	$key = (string) $product->get_meta( '_voiceweb_avan_catalog_key' );
	if ( ! $key ) { continue; }
	if ( $restore ) {
		$backup = $product->get_meta( '_voiceweb_launch_copy_backup' );
		if ( is_array( $backup ) ) { $product->set_description( $backup['description'] ); $product->set_short_description( $backup['short'] ); $product->delete_meta_data( '_voiceweb_launch_copy_version' ); $product->save(); $result['restored']++; }
		continue;
	}
	if ( ! isset( $copy[ $key ] ) ) { $result['missing'][] = $key; continue; }
	if ( $product->get_meta( '_voiceweb_launch_copy_version' ) === '2026-11-21-v1' ) { $result['skipped']++; continue; }
	[ $intro, $detail ] = $copy[ $key ];
	$terms = wp_get_post_terms( $product->get_id(), 'product_cat' );
	$related = '';
	if ( ! is_wp_error( $terms ) && $terms ) {
		$term = reset( $terms ); $url = get_term_link( $term );
		if ( ! is_wp_error( $url ) ) { $related = '<p>برای مقایسهٔ فرم، رنگ و طرح، <a href="' . esc_url( $url ) . '">مدل‌های دیگر ' . esc_html( $term->name ) . '</a> را هم ببینید.</p>'; }
	}
	$description = '<h2>' . esc_html( $product->get_name() ) . '</h2><p>' . esc_html( $intro ) . '</p><p>' . esc_html( $detail ) . '</p>';
	$description .= '<h3>پیش از سفارش این مدل</h3><p>ابعاد، جنس دقیق چرم، رنگ قابل سفارش و جزئیات داخل مدل انتخابی را با آوان چرم هماهنگ کنید. رنگ تصویر ممکن است با توجه به نور عکس و نمایشگر متفاوت دیده شود. اگر تغییر طرح یا ترکیب دیگری در نظر دارید، درخواستتان را پیش از نهایی‌کردن سفارش مطرح کنید تا امکان ساخت بررسی شود.</p>' . $related;
	if ( $apply ) {
		if ( ! $product->get_meta( '_voiceweb_launch_copy_backup' ) ) { $product->update_meta_data( '_voiceweb_launch_copy_backup', array( 'description' => $product->get_description(), 'short' => $product->get_short_description() ) ); }
		$product->set_short_description( $intro ); $product->set_description( $description );
		$product->update_meta_data( '_voiceweb_launch_copy_version', '2026-11-21-v1' ); $product->save();
	}
	$result['updated']++;
}
echo wp_json_encode( array_merge( array( 'mode' => $restore ? 'restore' : ( $apply ? 'apply' : 'preview' ) ), $result ), JSON_UNESCAPED_UNICODE ) . PHP_EOL;
