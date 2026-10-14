<?php
/** Keep scheduled-sale presentation current if WordPress's scheduled runner is late. */
defined( 'ABSPATH' ) || exit;

/** An atomic database lease avoids running a sale batch on every simultaneous request. */
function voiceweb_wc_sale_expiry_lease(): bool {
	global $wpdb;
	$now = time(); $expires = $now + 15; $key = 'voiceweb_sale_expiry_lease';
	if ( add_option( $key, (string) $expires, '', false ) ) { return true; }
	$claimed = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND CAST(option_value AS UNSIGNED)<=%d", (string) $expires, $key, $now ) ) === 1;
	if ( $claimed ) { wp_cache_delete( $key, 'options' ); }
	return $claimed;
}

/**
 * Use Woo's own ending-sale handler: it updates _price, lookup rows and parent caches.
 * The bounded fallback changes products only; previously priced orders stay unchanged.
 */
function voiceweb_wc_reconcile_expired_sales( bool $force = false, array $only_ids = array() ): int {
	if ( ! function_exists( 'wc_handle_product_end_scheduled_sale' ) || ( ! $force && ! voiceweb_wc_sale_expiry_lease() ) ) { return 0; }
	global $wpdb;
	$scope = $only_ids ? ' AND p.ID IN (' . implode( ',', array_map( 'absint', array_slice( $only_ids, 0, 25 ) ) ) . ')' : '';
	// Derived variable/grouped parents have no individual sale price to reconcile.
	// This mirrors native get_ending_sales() but bounds work and skips those parents.
	$ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT p.ID FROM {$wpdb->posts} p
		 INNER JOIN {$wpdb->postmeta} ends ON ends.post_id=p.ID AND ends.meta_key='_sale_price_dates_to'
		 INNER JOIN {$wpdb->postmeta} price ON price.post_id=p.ID AND price.meta_key='_price'
		 INNER JOIN {$wpdb->postmeta} regular ON regular.post_id=p.ID AND regular.meta_key='_regular_price'
		 WHERE p.post_type IN ('product','product_variation') AND p.post_status IN ('publish','private','draft'){$scope}
		 AND CAST(ends.meta_value AS UNSIGNED)>0 AND CAST(ends.meta_value AS UNSIGNED)<%d
		 AND price.meta_value<>regular.meta_value
		 AND NOT EXISTS (SELECT 1 FROM {$wpdb->term_relationships} rel
		 INNER JOIN {$wpdb->term_taxonomy} tax ON tax.term_taxonomy_id=rel.term_taxonomy_id AND tax.taxonomy='product_type'
		 INNER JOIN {$wpdb->terms} term ON term.term_id=tax.term_id
		 WHERE rel.object_id=p.ID AND term.slug NOT IN ('simple'))
		 ORDER BY p.ID ASC LIMIT 25", time()
	) );
	$count = 0;
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || ! $product->is_type( array( 'simple', 'variation' ) ) ) { continue; }
		$date = $product->get_date_on_sale_to( 'edit' );
		if ( ! $date || $date->getTimestamp() >= time() ) { continue; }
		if ( $product->is_type( 'variation' ) && function_exists( 'wc_apply_sale_state_for_product' ) ) {
			// Variation reads already derive the regular price, so the scheduled handler's
			// edit-price equality guard can return while persisted _price/lookup are stale.
			// The same native shared applier repairs both and synchronizes the parent.
			wc_apply_sale_state_for_product( $product, 'end' );
		} else { wc_handle_product_end_scheduled_sale( $id ); }
		$count++;
	}
	if ( $count ) { delete_transient( 'wc_products_onsale' ); }
	return $count;
}

function voiceweb_wc_sale_expiry_tick(): void { voiceweb_wc_reconcile_expired_sales(); }

/**
 * Native variable-price cache lacks sale-window phases. Add dates + phase, not a clock
 * bucket: the key stays stable until the real start/end boundary or an editorial change.
 * Native variation reads calculate the date-correct price; no financial price override.
 */
function voiceweb_wc_variation_sale_hash( array $hash, WC_Product $product, bool $for_display ): array {
	$now = time(); $parts = array(); $ids = $product->get_children();
	if ( $ids ) { _prime_post_caches( $ids ); }
	foreach ( $ids as $id ) {
		$variation = wc_get_product( $id );
		if ( ! $variation instanceof WC_Product_Variation || $variation->get_sale_price( 'edit' ) === '' ) { continue; }
		$from = $variation->get_date_on_sale_from( 'edit' ); $to = $variation->get_date_on_sale_to( 'edit' );
		if ( ! $from && ! $to ) { continue; }
		$start = $from ? $from->getTimestamp() : 0; $end = $to ? $to->getTimestamp() : 0;
		$phase = $start > $now ? 'future' : ( $end && $end < $now ? 'ended' : 'active' );
		$regular = $variation->get_regular_price( 'edit' ); $sale = $variation->get_sale_price( 'edit' ); $current = $variation->get_price( 'edit' );
		$expected = $phase === 'active' && (float) $regular > (float) $sale ? $sale : $regular;
		if ( $current !== '' && ( (float) $current === (float) $regular || (float) $current === (float) $sale ) && (float) $current !== (float) $expected && class_exists( '\Automattic\WooCommerce\Internal\Caches\ProductCache' ) ) {
			// With Woo product-instance caching enabled, an old child object also needs a
			// fresh native read. Remove just the drifting child; prices are not rewritten.
			wc_get_container()->get( \Automattic\WooCommerce\Internal\Caches\ProductCache::class )->remove( $id );
			wc_get_product( $id );
		}
		$parts[] = $id . ':' . $start . ':' . $end . ':' . $phase;
	}
	if ( $parts ) { $hash['voiceweb_sale_windows'] = hash( 'sha256', implode( '|', $parts ) ); }
	return $hash;
}

add_action( 'init', 'voiceweb_wc_sale_expiry_tick', 9 );
add_filter( 'woocommerce_get_variation_prices_hash', 'voiceweb_wc_variation_sale_hash', 20, 3 );

// Woo's existing date-correct price service handles simple products at priority 99.
// Apply that same native service to cached variation views too; preserve its checks
// for third-party prices and edit-context values instead of copying financial logic.
if ( class_exists( '\Automattic\WooCommerce\Internal\ScheduledSalePriceReconciler' ) ) {
	add_filter( 'woocommerce_product_variation_get_price', array( wc_get_container()->get( \Automattic\WooCommerce\Internal\ScheduledSalePriceReconciler::class ), 'reconcile_price' ), 99, 2 );
}
