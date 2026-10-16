<?php
/** Read-only local archive metadata checks. No fixtures, option updates, or external requests. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
$host = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.(?:local|test))$/i', $host ) ) { exit( "Local hosts only.\n" ); }
define( 'DISABLE_WP_CRON', true ); define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-catalog-seo-test' );
$_SERVER['HTTP_HOST'] = $host; $_SERVER['REQUEST_URI'] = '/';
$GLOBALS['wp_filter']['pre_http_request'][PHP_INT_MAX][] = array( 'function' => static fn() => new WP_Error( 'test_offline', 'Network blocked.' ), 'accepted_args' => 3 );
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array( 'function' => static fn() => false, 'accepted_args' => 2 );
require dirname( __DIR__, 4 ) . '/wp-load.php';
$checks = 0; $failure = null; $old_query = $GLOBALS['wp_query']; $old_get = $_GET;
$old_structure = $wp_rewrite->permalink_structure; $canonical_priority = has_action( 'wp_head', 'rel_canonical' );
$visibility = array( get_option( 'blog_public' ), get_option( 'woocommerce_coming_soon' ) );
function vwcs_check( bool $condition, string $message ): void { if ( ! $condition ) { throw new RuntimeException( $message ); } $GLOBALS['checks']++; }
function vwcs_shop( int $page = 1, array $params = array() ): void {
	$q = new WP_Query(); $q->is_post_type_archive = true; $q->is_archive = true; $q->is_paged = $page > 1;
	$q->set( 'post_type', 'product' ); $q->set( 'paged', $page > 1 ? $page : 0 ); $GLOBALS['wp_query'] = $q; $_GET = $params;
}
try {
	vwcs_check( function_exists( 'voiceweb_shop_seo_head' ) && function_exists( 'wc_get_page_permalink' ), 'Shop SEO and WooCommerce must be available.' );
	vwcs_check( wp_parse_url( home_url(), PHP_URL_HOST ) === $host, 'HTTP checks must stay on the configured local host.' );
	add_filter( 'voiceweb/gift/seo-plugin-active', '__return_false', 999 );
	$base = wc_get_page_permalink( 'shop' );
	vwcs_shop();
	vwcs_check( voiceweb_shop_seo_context() && voiceweb_catalog_page_canonical( $base ) === $base, 'The first shop page must be self-canonical.' );
	vwcs_check( voiceweb_shop_robots( array( 'index' => true, 'follow' => true ) ) === array( 'index' => true, 'follow' => true ), 'An unfiltered shop must preserve production index/follow directives.' );
	ob_start(); voiceweb_shop_seo_head(); $head = ob_get_clean();
	vwcs_check( substr_count( $head, 'rel="canonical"' ) === 1 && strpos( $head, esc_url( $base ) ) !== false, 'Shop emits exactly one canonical.' );
	vwcs_check( strpos( $head, 'name="description"' ) === false && strpos( $head, '<script' ) === false, 'Shop canonical must not duplicate existing descriptions or structured data.' );
	vwcs_check( has_action( 'wp_head', 'rel_canonical' ) === false, 'Shop removes the later core singular canonical to prevent duplication.' );
	vwcs_shop( 2 );
	vwcs_check( voiceweb_catalog_page_canonical( $base ) === trailingslashit( $base ) . 'page/2/', 'Real pretty pagination must have a page-specific canonical.' );
	vwcs_check( voiceweb_catalog_page_canonical( $base . '?lang=en' ) === trailingslashit( $base ) . 'page/2/?lang=en', 'Language query parameters must follow the pagination path.' );
	$wp_rewrite->permalink_structure = '';
	vwcs_check( voiceweb_catalog_page_canonical( home_url( '/?post_type=product' ) ) === home_url( '/?post_type=product&paged=2' ), 'Plain permalinks must use the paged query parameter.' );
	$wp_rewrite->permalink_structure = $old_structure;
	foreach ( array( array( 'orderby' => 'price' ), array( 'min_price' => '1' ), array( 'vw_stock' => '1' ), array( 'vw_search' => 'leather' ) ) as $params ) {
		vwcs_shop( 2, $params );
		vwcs_check( voiceweb_catalog_page_canonical( $base ) === $base, 'Shop filters must canonicalise to the unfiltered first page.' );
		$robots = voiceweb_shop_robots( array( 'index' => true, 'follow' => true ) );
		vwcs_check( ! isset( $robots['index'] ) && ! empty( $robots['noindex'] ) && ! empty( $robots['follow'] ), 'Shop filters must be noindex without disabling link following.' );
	}
	vwcs_shop( 2, array( 'lang' => 'en' ) );
	vwcs_check( ! voiceweb_category_has_filters() && empty( voiceweb_shop_robots( array() )['noindex'] ), 'Language selection alone must not noindex an archive.' );
	add_filter( 'voiceweb/gift/seo-plugin-active', '__return_true', 1000 );
	ob_start(); voiceweb_shop_seo_head(); $plugin_head = ob_get_clean();
	vwcs_check( $plugin_head === '' && voiceweb_shop_robots( array() ) === array(), 'Active SEO plugins must keep canonical and robots ownership.' );
	remove_filter( 'voiceweb/gift/seo-plugin-active', '__return_true', 1000 );
	$GLOBALS['wp_query'] = new WP_Query(); $_GET = array( 'orderby' => 'price' );
	ob_start(); voiceweb_shop_seo_head(); $unrelated_head = ob_get_clean();
	vwcs_check( $unrelated_head === '' && voiceweb_shop_robots( array() ) === array(), 'Shop metadata must not affect unrelated requests.' );
	$GLOBALS['wp_query'] = $old_query; $_GET = $old_get;
	foreach ( array( $base, add_query_arg( 'orderby', 'price', $base ) ) as $url ) {
		$curl = curl_init( $url ); curl_setopt_array( $curl, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_NOPROXY => '*', CURLOPT_TIMEOUT => 30 ) );
		$html = curl_exec( $curl ); $status = curl_getinfo( $curl, CURLINFO_HTTP_CODE ); curl_close( $curl );
		vwcs_check( is_string( $html ) && $status === 200, 'Live shop must return HTTP 200.' );
		$doc = new DOMDocument(); libxml_use_internal_errors( true ); $doc->loadHTML( '<?xml encoding="UTF-8">' . $html ); libxml_clear_errors(); $x = new DOMXPath( $doc );
		vwcs_check( $x->query( '//link[@rel="canonical"]' )->length === 1 && $x->evaluate( 'string(//link[@rel="canonical"]/@href)' ) === $base, 'Live shop and sorting URLs must each emit one primary shop canonical.' );
		vwcs_check( $x->query( '//meta[@name="description"]' )->length === 1, 'Existing shop description must remain singular.' );
		vwcs_check( $x->query( '//*[@data-vw-catalog]' )->length === 1, 'Metadata checks must exercise the real catalogue, not a coming-soon page.' );
	}
	vwcs_check( array( get_option( 'blog_public' ), get_option( 'woocommerce_coming_soon' ) ) === $visibility, 'Read-only checks must preserve site visibility options.' );
} catch ( Throwable $error ) { $failure = $error; }
finally {
	$GLOBALS['wp_query'] = $old_query; $_GET = $old_get; $wp_rewrite->permalink_structure = $old_structure;
	remove_filter( 'voiceweb/gift/seo-plugin-active', '__return_false', 999 ); remove_filter( 'voiceweb/gift/seo-plugin-active', '__return_true', 1000 );
	if ( $canonical_priority !== false ) { add_action( 'wp_head', 'rel_canonical', $canonical_priority ); }
}
if ( $failure ) { fwrite( STDERR, 'FAIL after ' . $checks . ' checks: ' . $failure->getMessage() . PHP_EOL ); exit( 1 ); }
echo 'PASS: ' . $checks . " read-only shop SEO checks, including production robots semantics, pagination, plugin ownership and live HTTP.\n";
