<?php
/**
 * Local WordPress/WooCommerce integration checks for occasion landing pages.
 * Run: php -d mysqli.default_port=10113 wp-content/themes/voiceweb/tools/test-gift-landings.php
 * Creates uniquely named fixtures and deletes only those IDs in finally.
 * No orders, users, messages, remote requests, or persistent option changes.
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }

$test_host = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.local)$/i', $test_host ) ) {
	fwrite( STDERR, "Refusing to run outside a local hostname.\n" ); exit( 1 );
}
define( 'DISABLE_WP_CRON', true );
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-gift-tests-' . bin2hex( random_bytes( 5 ) ) );
$_SERVER['HTTP_HOST'] = $test_host;
$_SERVER['REQUEST_URI'] = '/';
// Preinitialised hooks block integration traffic during WordPress bootstrap too.
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array( 'function' => static fn() => false, 'accepted_args' => 2 );
$GLOBALS['wp_filter']['pre_http_request'][PHP_INT_MAX][] = array( 'function' => static fn() => new WP_Error( 'gift_test_no_network', 'Network disabled in gift landing tests.' ), 'accepted_args' => 3 );
require dirname( __DIR__, 4 ) . '/wp-load.php';
$site_host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.local)$/i', $site_host ) ) {
	fwrite( STDERR, "Refusing fixture writes: configured site is not local.\n" ); exit( 1 );
}
if ( ! function_exists( 'voiceweb_gift_products' ) || ! class_exists( 'WC_Product_Simple' ) ) {
	fwrite( STDERR, "Gift landing helpers and WooCommerce must be active.\n" ); exit( 1 );
}

$prefix = 'vw_gift_test_' . bin2hex( random_bytes( 6 ) );
$fixture_posts = array(); $fixture_terms = array(); $checks = 0; $failure = null;
$original_query = $GLOBALS['wp_query'];
$original_post = $GLOBALS['post'] ?? null;
$hide_stock = 'no';
$stock_filter = static function () use ( &$hide_stock ) { return $hide_stock; };
add_filter( 'pre_option_woocommerce_hide_out_of_stock_items', $stock_filter );

function vwg_check( bool $condition, string $message ): void {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	$GLOBALS['checks']++;
}
function vwg_meta( int $page, string $name, $value ): void {
	$key = 'vw_gift_' . $name;
	update_post_meta( $page, $key, $value );
	if ( function_exists( 'acf_flush_value_cache' ) ) { acf_flush_value_cache( $page, $key ); }
}
function vwg_page( string $suffix, string $status = 'draft', string $password = '' ): int {
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => $status, 'post_title' => $GLOBALS['prefix'] . '_' . $suffix, 'post_name' => $GLOBALS['prefix'] . '-' . $suffix, 'post_password' => $password ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$GLOBALS['fixture_posts'][] = $id;
	return $id;
}
function vwg_term( string $suffix, int $parent = 0 ): int {
	$term = wp_insert_term( $GLOBALS['prefix'] . '_' . $suffix, 'product_cat', array( 'slug' => $GLOBALS['prefix'] . '-' . $suffix, 'parent' => $parent ) );
	if ( is_wp_error( $term ) ) { throw new RuntimeException( $term->get_error_message() ); }
	$GLOBALS['fixture_terms'][] = (int) $term['term_id'];
	return (int) $term['term_id'];
}
function vwg_product( string $suffix, int $category, array $props = array() ): int {
	$product = new WC_Product_Simple();
	$product->set_props( array_merge( array( 'name' => $GLOBALS['prefix'] . '_' . $suffix, 'slug' => $GLOBALS['prefix'] . '-' . $suffix, 'status' => 'publish', 'regular_price' => '1000', 'catalog_visibility' => 'visible', 'stock_status' => 'instock', 'category_ids' => array( $category ) ), $props ) );
	$id = $product->save();
	if ( ! $id ) { throw new RuntimeException( 'Could not create fixture product.' ); }
	$GLOBALS['fixture_posts'][] = $id;
	return $id;
}
function vwg_result_ids( int $page ): array { return array_map( static fn( $item ) => $item->get_id(), voiceweb_gift_products( $page ) ); }
function vwg_query_page( int $id ): void {
	$query = new WP_Query();
	$query->is_page = true; $query->is_singular = true; $query->is_home = false;
	$query->queried_object = get_post( $id ); $query->queried_object_id = $id;
	$query->post = get_post( $id ); $query->posts = array( $query->post ); $query->post_count = 1;
	$GLOBALS['wp_query'] = $query; $GLOBALS['post'] = $query->post;
}

try {
	$page = vwg_page( 'landing' );
	update_post_meta( $page, '_wp_page_template', 'page-templates/gift-landing.php' );
	vwg_check( voiceweb_gift_value( 'missing', 'fallback', $page ) === 'fallback', 'Missing metadata uses its supplied fallback.' );
	vwg_meta( $page, 'intro', '' );
	vwg_check( voiceweb_gift_value( 'intro', 'fallback', $page ) === 'fallback', 'An empty text field uses its supplied fallback.' );
	vwg_meta( $page, 'in_stock', 0 );
	vwg_check( in_array( voiceweb_gift_value( 'in_stock', true, $page ), array( 0, '0', false ), true ), 'Explicit false/zero survives the default-true stock fallback.' );
	vwg_meta( $page, 'products', array() );
	vwg_check( voiceweb_gift_value( 'products', array( 123 ), $page ) === array(), 'An explicitly empty relationship is not replaced by fallback products.' );
	vwg_meta( $page, 'tips', 1 );
	update_post_meta( $page, 'vw_gift_tips_0_title', 'Useful gift' ); update_post_meta( $page, 'vw_gift_tips_0_text', 'Match the recipient.' );
	vwg_check( voiceweb_gift_value( 'tips', array(), $page ) === array( array( 'title' => 'Useful gift', 'text' => 'Match the recipient.' ) ), 'Raw ACF repeater rows remain readable.' );
	vwg_meta( $page, 'faq', 1 );
	update_post_meta( $page, 'vw_gift_faq_0_question', 'Which gift?' ); update_post_meta( $page, 'vw_gift_faq_0_answer', 'A useful one.' );
	vwg_check( voiceweb_gift_value( 'faq', array(), $page ) === array( array( 'question' => 'Which gift?', 'answer' => 'A useful one.' ) ), 'FAQ repeater rows preserve question and answer fields.' );
	vwg_check( voiceweb_gift_products( $page ) === array(), 'An unconfigured category source never queries the full catalogue.' );
	vwg_meta( $page, 'product_source', 'manual' );
	vwg_check( voiceweb_gift_products( $page ) === array(), 'An empty manual collection never falls back to the catalogue.' );
	vwg_meta( $page, 'product_source', 'unexpected' );
	vwg_check( voiceweb_gift_products( $page ) === array(), 'Unknown collection modes fail closed.' );

	$parent = vwg_term( 'parent' ); $child = vwg_term( 'child', $parent ); $other = vwg_term( 'other' );
	$first = vwg_product( 'visible', $parent, array( 'regular_price' => '100', 'menu_order' => 2, 'total_sales' => 2 ) );
	$child_product = vwg_product( 'child', $child, array( 'regular_price' => '200', 'menu_order' => 3, 'total_sales' => 3 ) );
	$catalog = vwg_product( 'catalog-only', $parent, array( 'catalog_visibility' => 'catalog', 'regular_price' => '300', 'menu_order' => 1, 'total_sales' => 1 ) );
	$hidden = vwg_product( 'hidden', $parent, array( 'catalog_visibility' => 'hidden' ) );
	$search = vwg_product( 'search-only', $parent, array( 'catalog_visibility' => 'search' ) );
	$draft = vwg_product( 'draft', $parent, array( 'status' => 'draft' ) );
	$private = vwg_product( 'private', $parent, array( 'status' => 'private' ) );
	$out = vwg_product( 'out-of-stock', $parent, array( 'stock_status' => 'outofstock' ) );
	$backorder = vwg_product( 'backorder', $parent, array( 'stock_status' => 'onbackorder', 'backorders' => 'yes' ) );
	$protected = vwg_product( 'password', $parent ); wp_update_post( array( 'ID' => $protected, 'post_password' => 'fixture-only' ) );
	$unrelated = vwg_product( 'unrelated', $other );
	$original_categories = wc_get_product( $child_product )->get_category_ids();
	vwg_meta( $page, 'product_source', 'categories' ); vwg_meta( $page, 'categories', array( $parent ) );
	vwg_meta( $page, 'product_limit', 24 ); vwg_meta( $page, 'product_order', 'price' ); vwg_meta( $page, 'in_stock', 1 );
	$ids = vwg_result_ids( $page );
	vwg_check( $ids === array( $first, $child_product, $catalog ), 'Category selection includes descendants and catalog-only products, sorted by price; excludes hidden/search/draft/private/password/out-of-stock/unrelated products.' );
	delete_post_meta( $page, 'vw_gift_in_stock' );
	if ( function_exists( 'acf_flush_value_cache' ) ) { acf_flush_value_cache( $page, 'vw_gift_in_stock' ); }
	vwg_check( vwg_result_ids( $page ) === array( $first, $child_product, $catalog ), 'An unset stock preference defaults to strictly in-stock products, excluding backorders.' );
	vwg_check( wc_get_product( $child_product )->get_category_ids() === $original_categories, 'A landing query does not change original product categories.' );
	vwg_meta( $page, 'product_order', 'popularity' );
	vwg_check( vwg_result_ids( $page ) === array( $child_product, $first, $catalog ), 'Popularity order follows WooCommerce total sales.' );
	vwg_meta( $page, 'product_order', 'menu_order' );
	vwg_check( vwg_result_ids( $page ) === array( $catalog, $first, $child_product ), 'Menu order follows WooCommerce product ordering.' );
	vwg_meta( $page, 'in_stock', 0 );
	vwg_check( in_array( $out, vwg_result_ids( $page ), true ), 'Disabling the collection stock filter allows out-of-stock products.' );
	$hide_stock = 'yes';
	vwg_check( ! in_array( $out, vwg_result_ids( $page ), true ), 'The global WooCommerce stock visibility setting remains authoritative.' );
	vwg_check( in_array( $backorder, vwg_result_ids( $page ), true ), 'Global hide-out-of-stock preserves available backorders when the collection stock filter is disabled.' );
	$hide_stock = 'no';
	vwg_meta( $page, 'product_source', 'manual' );
	vwg_meta( $page, 'products', array( $catalog, $hidden, $first, $draft, $child_product, $protected, $search, $private, $out ) );
	vwg_meta( $page, 'in_stock', 1 );
	vwg_check( vwg_result_ids( $page ) === array( $catalog, $first, $child_product ), 'Manual selection preserves editor order and applies the same visibility safeguards.' );
	vwg_check( voiceweb_gift_ids( array( get_post( $first ), get_term( $child, 'product_cat' ), $first, 0, -1, 'invalid', new stdClass() ) ) === array( $first, $child ), 'Relationship IDs accept WordPress objects, deduplicate, and reject malformed values.' );

	for ( $i = 0; $i < 22; $i++ ) { vwg_product( 'limit-' . $i, $parent, array( 'regular_price' => (string) ( 2000 + $i ) ) ); }
	vwg_meta( $page, 'product_source', 'categories' ); vwg_meta( $page, 'product_order', 'price' ); vwg_meta( $page, 'product_limit', 999 );
	vwg_check( count( vwg_result_ids( $page ) ) === 24, 'Oversized collections are bounded to 24 products.' );
	vwg_meta( $page, 'product_limit', 0 );
	vwg_check( vwg_result_ids( $page ) === array( $first ), 'A zero product limit is clamped to one, never unlimited.' );

	$related_a = vwg_page( 'related-a', 'publish' ); $related_b = vwg_page( 'related-b', 'publish' );
	$related_draft = vwg_page( 'related-draft' ); $related_protected = vwg_page( 'related-password', 'publish', 'fixture-only' );
	vwg_meta( $page, 'related_pages', array( $related_b, $related_draft, $page, $related_protected, $related_a ) );
	vwg_check( wp_list_pluck( voiceweb_gift_related( $page ), 'ID' ) === array( $related_b, $related_a ), 'Related pages preserve order and exclude self, drafts, and password-protected pages.' );

	add_filter( 'voiceweb/gift/seo-plugin-active', '__return_false', 999 );
	vwg_query_page( $page );
	vwg_check( ! voiceweb_gift_seo_context(), 'Draft copy is excluded from fallback SEO metadata.' );
	wp_update_post( array( 'ID' => $page, 'post_status' => 'publish' ) ); vwg_query_page( $page );
	vwg_meta( $page, 'seo_title', 'Custom <b>gift</b> title' ); vwg_meta( $page, 'seo_description', 'Gift "selection" & <b>advice</b>.' );
	vwg_check( voiceweb_gift_seo_title( 'Original title' ) === 'Custom gift title', 'Fallback SEO title strips markup and uses editor content.' );
	ob_start(); voiceweb_gift_seo_head(); $head = ob_get_clean();
	vwg_check( substr_count( $head, '<meta name="description"' ) === 1 && strpos( $head, '&quot;selection&quot; &amp; advice.' ) !== false, 'SEO description is present once and safely attribute-escaped.' );
	preg_match( '/<script[^>]*>(.*?)<\/script>/s', $head, $match ); $schema = json_decode( $match[1] ?? '', true );
	vwg_check( is_array( $schema ) && $schema['@graph'][0]['@type'] === 'CollectionPage' && $schema['@graph'][1]['@type'] === 'BreadcrumbList', 'Fallback structured data parses as a collection and breadcrumb graph.' );
	vwg_check( strpos( $head, 'rel="canonical"' ) === false, 'Fallback metadata leaves canonical output to WordPress.' );
	add_filter( 'voiceweb/gift/seo-plugin-active', '__return_true', 1000 );
	ob_start(); voiceweb_gift_seo_head(); $plugin_head = ob_get_clean();
	vwg_check( $plugin_head === '' && voiceweb_gift_seo_title( 'Plugin title' ) === 'Plugin title', 'An SEO plugin suppresses theme SEO title, metadata, and schema.' );
	remove_filter( 'voiceweb/gift/seo-plugin-active', '__return_true', 1000 );
	wp_update_post( array( 'ID' => $page, 'post_password' => 'fixture-only' ) ); vwg_query_page( $page );
	vwg_check( ! voiceweb_gift_seo_context(), 'Password-protected content is excluded from fallback SEO metadata.' );
	remove_filter( 'voiceweb/gift/seo-plugin-active', '__return_false', 999 );
} catch ( Throwable $error ) {
	$failure = $error;
} finally {
	$GLOBALS['wp_query'] = $original_query; $GLOBALS['post'] = $original_post;
	remove_filter( 'pre_option_woocommerce_hide_out_of_stock_items', $stock_filter );
	foreach ( array_reverse( $fixture_posts ) as $id ) {
		$fixture = get_post( $id );
		if ( ! $fixture || strpos( $fixture->post_title, $prefix . '_' ) !== 0 ) { continue; }
		if ( $fixture->post_type === 'product' ) { $product = wc_get_product( $id ); if ( $product ) { $product->delete( true ); } }
		else { wp_delete_post( $id, true ); }
	}
	foreach ( array_reverse( $fixture_terms ) as $id ) {
		$fixture = get_term( $id, 'product_cat' );
		if ( $fixture instanceof WP_Term && strpos( $fixture->name, $prefix . '_' ) === 0 ) { wp_delete_term( $id, 'product_cat' ); }
	}
}
$remaining = array_filter( $fixture_posts, static fn( $id ) => get_post( $id ) !== null );
$remaining_terms = array_filter( $fixture_terms, static fn( $id ) => get_term( $id, 'product_cat' ) instanceof WP_Term );
if ( $remaining || $remaining_terms ) { fwrite( STDERR, 'FAIL: fixture cleanup incomplete: ' . wp_json_encode( array( 'posts' => array_values( $remaining ), 'terms' => array_values( $remaining_terms ) ) ) . PHP_EOL ); exit( 1 ); }
if ( $failure ) { fwrite( STDERR, 'FAIL after ' . $checks . ' checks: ' . $failure->getMessage() . PHP_EOL ); exit( 1 ); }
echo 'PASS: ' . $checks . ' gift landing integration checks; all ' . count( $fixture_posts ) . ' post/product and ' . count( $fixture_terms ) . ' category fixtures removed. Mail and network blocked; no orders, users, or persistent option writes.' . PHP_EOL;
