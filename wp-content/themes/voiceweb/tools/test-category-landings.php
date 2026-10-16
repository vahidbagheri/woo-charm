<?php
/** Local integration checks; creates only its own term/page fixtures and always cleans them. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
$host = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.local)$/i', $host ) ) { exit( "Local hosts only.\n" ); }
define( 'DISABLE_WP_CRON', true ); define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-category-test' );
$_SERVER['HTTP_HOST'] = $host; $_SERVER['REQUEST_URI'] = '/';
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array( 'function' => static fn() => false, 'accepted_args' => 2 );
$GLOBALS['wp_filter']['pre_http_request'][PHP_INT_MAX][] = array( 'function' => static fn() => new WP_Error( 'test_no_network', 'Network blocked.' ), 'accepted_args' => 3 );
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.local)$/i', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) { fwrite( STDERR, "Configured site is not local.\n" ); exit( 1 ); }
if ( ! function_exists( 'voiceweb_category_value' ) ) { fwrite( STDERR, "Category helpers must be loaded by the theme.\n" ); exit( 1 ); }
$prefix = 'vw_cat_test_' . bin2hex( random_bytes( 6 ) ); $terms = array(); $pages = array(); $checks = 0; $failure = null;
$old_query = $GLOBALS['wp_query']; $old_get = $_GET;
function vwct_check( bool $condition, string $message ): void { if ( ! $condition ) { throw new RuntimeException( $message ); } $GLOBALS['checks']++; }
function vwct_meta( int $term, string $key, $value ): void { update_term_meta( $term, 'vw_cat_' . $key, $value ); if ( function_exists( 'acf_flush_value_cache' ) ) { acf_flush_value_cache( 'product_cat_' . $term, 'vw_cat_' . $key ); acf_flush_value_cache( 'term_' . $term, 'vw_cat_' . $key ); } }
function vwct_query( int $term, int $page = 1, array $params = array() ): void { $q = new WP_Query(); $q->is_tax = true; $q->is_archive = true; $q->is_paged = $page > 1; $q->queried_object = get_term( $term, 'product_cat' ); $q->queried_object_id = $term; $q->set( 'paged', $page > 1 ? $page : 0 ); $GLOBALS['wp_query'] = $q; $_GET = $params; }
try {
	$config = require dirname( __DIR__ ) . '/config/category-landings.php';
	vwct_check( count( $config ) === 6, 'Six existing product categories have editorial definitions.' );
	foreach ( $config as $slug => $spec ) {
		$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $spec['guide'] ) ) );
		vwct_check( count( $words ) >= 200 && count( $words ) <= 350, $slug . ' guide must contain 200–350 words; got ' . count( $words ) );
		vwct_check( count( $spec['tips'] ) === 3 && count( $spec['faq'] ) === 3, $slug . ' includes useful tips and FAQs.' );
	}
	foreach ( array( 'main', 'related' ) as $suffix ) { $result = wp_insert_term( $prefix . '_' . $suffix, 'product_cat', array( 'slug' => $prefix . '-' . $suffix ) ); if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); } $terms[] = (int) $result['term_id']; }
	$term = $terms[0];
	vwct_check( voiceweb_category_value( 'intro', 'fallback', $term ) === 'fallback', 'Missing term fields use their fallback.' );
	vwct_meta( $term, 'intro', '' ); vwct_check( voiceweb_category_value( 'intro', 'fallback', $term ) === 'fallback', 'Empty text uses its fallback.' );
	vwct_meta( $term, 'intro', 'Editable intro' );
	vwct_check( apply_filters( 'woocommerce_taxonomy_archive_description_raw', 'Native description', get_term( $term ) ) === 'Editable intro', 'The native Woo archive description prefers ACF intro edits.' );
	vwct_meta( $term, 'tips', 1 ); update_term_meta( $term, 'vw_cat_tips_0_title', 'Tip' ); update_term_meta( $term, 'vw_cat_tips_0_text', 'Practical advice' );
	vwct_check( voiceweb_category_value( 'tips', array(), $term ) === array( array( 'title' => 'Tip', 'text' => 'Practical advice' ) ), 'Raw term ACF repeater rows remain readable.' );
	vwct_meta( $term, 'related_categories', array( $terms[1], $term, $terms[1], 0 ) );
	vwct_check( wp_list_pluck( voiceweb_category_related_terms( $term ), 'term_id' ) === array( $terms[1] ), 'Related categories preserve order, deduplicate and exclude self.' );
	foreach ( array( 'publish', 'draft', 'protected', 'regular' ) as $type ) {
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_title' => $prefix . '_' . $type, 'post_status' => $type === 'draft' ? 'draft' : 'publish', 'post_password' => $type === 'protected' ? 'fixture-only' : '', 'meta_input' => array( '_wp_page_template' => $type === 'regular' ? 'default' : 'page-templates/gift-landing.php' ) ), true );
		if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); } $pages[] = $id;
	}
	vwct_meta( $term, 'related_gifts', $pages );
	vwct_check( wp_list_pluck( voiceweb_category_related_gifts( $term ), 'ID' ) === array( $pages[0] ), 'Related gifts include only public unprotected gift templates.' );
	vwct_meta( $term, 'guide', '<p>Useful guide</p><script>alert(1)</script><a href="javascript:alert(1)">Link</a>' );
	vwct_meta( $term, 'seo_title', 'Category <b>title</b>' ); vwct_meta( $term, 'seo_description', 'A "description" & <b>details</b>.' );
	vwct_query( $term );
	add_filter( 'voiceweb/gift/seo-plugin-active', '__return_false', 999 );
	$base = get_term_link( $term, 'product_cat' );
	vwct_check( voiceweb_category_show_guide() && voiceweb_category_canonical() === $base, 'The first unfiltered category shows its guide and is self-canonical.' );
	vwct_check( voiceweb_category_seo_title( 'Original' ) === 'Category title', 'Category SEO titles use safe editable content.' );
	ob_start(); voiceweb_category_seo_head(); $head = ob_get_clean();
	preg_match( '/<script[^>]*>(.*?)<\/script>/s', $head, $match ); $schema = json_decode( $match[1] ?? '', true );
	vwct_check( ( $schema['@type'] ?? '' ) === 'CollectionPage' && strpos( $head, 'BreadcrumbList' ) === false, 'Category adds CollectionPage only; native Woo owns breadcrumb schema.' );
	vwct_check( substr_count( $head, 'rel="canonical"' ) === 1 && substr_count( $head, 'name="description"' ) === 1 && strpos( $head, '&quot;description&quot; &amp; details.' ) !== false, 'One canonical and escaped meta description are emitted.' );
	ob_start(); get_template_part( 'template-parts/woocommerce/category-guide' ); $html = ob_get_clean();
	vwct_check( strpos( $html, 'id="category-advice"' ) !== false && strpos( $html, '<script' ) === false && strpos( $html, 'javascript:' ) === false, 'Guide is rendered with safe rich text on the primary collection.' );
	vwct_query( $term, 2 );
	vwct_check( ! voiceweb_category_show_guide() && voiceweb_category_canonical() === trailingslashit( $base ) . 'page/2/', 'Real pagination has a distinct self-canonical and omits repeated guide text.' );
	vwct_check( voiceweb_category_seo_title( 'Original' ) !== 'Category title', 'Paginated SEO titles identify the page number.' );
	vwct_query( $term, 2, array( 'orderby' => 'price' ) );
	vwct_check( ! voiceweb_category_show_guide() && voiceweb_category_canonical() === $base && ! empty( voiceweb_category_robots( array() )['noindex'] ), 'Filtered/sorted variants omit the guide, use the main category canonical and request noindex.' );
	ob_start(); get_template_part( 'template-parts/woocommerce/category-guide' ); $filtered = ob_get_clean();
	vwct_check( strpos( $filtered, 'id="category-advice"' ) === false && strpos( $filtered, 'category-related-title' ) !== false && strpos( $filtered, 'category-gifts-title' ) !== false, 'Browsing links remain available on filtered collections.' );
	add_filter( 'voiceweb/gift/seo-plugin-active', '__return_true', 1000 );
	ob_start(); voiceweb_category_seo_head(); $plugin_head = ob_get_clean();
	vwct_check( $plugin_head === '' && voiceweb_category_seo_title( 'Plugin title' ) === 'Plugin title' && voiceweb_category_robots( array() ) === array(), 'Active SEO plugins retain ownership of titles, metadata, canonical and robots.' );
} catch ( Throwable $error ) { $failure = $error; }
finally {
	$GLOBALS['wp_query'] = $old_query; $_GET = $old_get;
	foreach ( $pages as $id ) { $page = get_post( $id ); if ( $page && strpos( $page->post_title, $prefix . '_' ) === 0 ) { wp_delete_post( $id, true ); } }
	foreach ( array_reverse( $terms ) as $id ) { $term = get_term( $id, 'product_cat' ); if ( $term instanceof WP_Term && strpos( $term->name, $prefix . '_' ) === 0 ) { wp_delete_term( $id, 'product_cat' ); } }
}
foreach ( $pages as $id ) { if ( get_post( $id ) ) { fwrite( STDERR, "Fixture cleanup failed.\n" ); exit( 1 ); } }
foreach ( $terms as $id ) { if ( get_term( $id, 'product_cat' ) instanceof WP_Term ) { fwrite( STDERR, "Term cleanup failed.\n" ); exit( 1 ); } }
if ( $failure ) { fwrite( STDERR, 'FAIL after ' . $checks . ' checks: ' . $failure->getMessage() . PHP_EOL ); exit( 1 ); }
echo 'PASS: ' . $checks . ' category integration checks; own ' . count( $terms ) . ' term and ' . count( $pages ) . " page fixtures removed. No products, orders, users, or persistent options changed.\n";
