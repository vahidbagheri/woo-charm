<?php
/** Read-only local magazine query and actual HTTP/source checks. No visual assertions. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
$host = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.local)$/i', $host ) ) { exit( "Local hosts only.\n" ); }
define( 'DISABLE_WP_CRON', true ); define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-magazine-test' );
$_SERVER['HTTP_HOST'] = $host; $_SERVER['REQUEST_URI'] = '/blog/';
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array( 'function' => static fn() => false, 'accepted_args' => 2 );
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( (string) wp_parse_url( home_url(), PHP_URL_HOST ) !== $host ) { exit( "Configured site must match the local test host.\n" ); }
$checks = 0; $old_query = $GLOBALS['wp_query']; $old_main = $GLOBALS['wp_the_query'];
function vwlm_check( bool $condition, string $message ): void { if ( ! $condition ) { throw new RuntimeException( $message ); } $GLOBALS['checks']++; }
function vwlm_dom( string $url ): DOMXPath {
	vwlm_check( wp_parse_url( $url, PHP_URL_HOST ) === $GLOBALS['host'], 'HTTP requests stay local.' );
	$curl = curl_init( $url ); curl_setopt_array( $curl, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_NOPROXY => '*' ) );
	$body = curl_exec( $curl ); $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE ); $error = curl_error( $curl ); curl_close( $curl );
	vwlm_check( is_string( $body ) && $code === 200, $url . ' must return HTTP 200; got ' . $code . ' ' . $error );
	vwlm_check( ! preg_match( '/(?:Fatal error|Parse error|Warning):/i', $body ), 'Public responses do not expose PHP diagnostics.' );
	$document = new DOMDocument(); libxml_use_internal_errors( true ); $document->loadHTML( $body ); libxml_clear_errors();
	return new DOMXPath( $document );
}
function vwlm_metadata( DOMXPath $xpath, string $url ): void {
	vwlm_check( $xpath->query( '//h1' )->length === 1, $url . ' has exactly one H1.' );
	vwlm_check( $xpath->query( '//head/title' )->length === 1 && mb_strpos( $xpath->evaluate( 'string(//head/title)' ), 'آوان چرم' ) !== false, $url . ' has one branded document title.' );
	vwlm_check( $xpath->query( '//meta[@name="description"]' )->length === 1 && trim( $xpath->evaluate( 'string(//meta[@name="description"]/@content)' ) ) !== '', $url . ' has one nonempty description.' );
	vwlm_check( $xpath->query( '//link[@rel="canonical"]' )->length === 1 && $xpath->evaluate( 'string(//link[@rel="canonical"]/@href)' ) === $url, $url . ' has one self-canonical.' );
}
try {
	vwlm_check( function_exists( 'voiceweb_leather_magazine_query' ), 'Magazine module is loaded.' );
	$term = voiceweb_leather_magazine_term(); vwlm_check( $term > 0, 'Existing leather-journal category is available.' );
	vwlm_check( voiceweb_leather_magazine_category_destination( $term ) === voiceweb_leather_magazine_url() && voiceweb_leather_magazine_category_destination( $term, 2 ) === voiceweb_leather_magazine_url( 2 ), 'The exact journal category alias maps the first and paginated magazine URL.' );
	vwlm_check( voiceweb_leather_magazine_category_destination( 0 ) === '' && voiceweb_leather_magazine_category_link( 'untouched-category', 0 ) === 'untouched-category', 'Other categories do not inherit the journal alias.' );
	vwlm_check( get_category_link( $term ) === voiceweb_leather_magazine_url(), 'Journal category links point directly to the canonical magazine.' );
	$expected = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false, 'cat' => $term, 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => false ) );
	vwlm_check( count( $expected ) >= 3, 'The existing leather articles remain published.' );
	$legacy_ids = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'category__not_in' => array( $term ), 'numberposts' => -1, 'fields' => 'ids' ) );
	$probe = new WP_Query(); $GLOBALS['wp_the_query'] = $probe; $GLOBALS['wp_query'] = $probe;
	$probe->query( array( 'post_type' => 'post', 'posts_per_page' => 1, 'paged' => 1 ) );
	vwlm_check( count( $probe->posts ) === 1 && in_array( $probe->posts[0]->ID, $expected, true ) && (int) $probe->found_posts === count( $expected ), 'The home main query returns only leather posts with native totals.' );
	$first = $probe->posts[0]->ID;
	$probe = new WP_Query(); $GLOBALS['wp_the_query'] = $probe; $GLOBALS['wp_query'] = $probe;
	$probe->query( array( 'post_type' => 'post', 'posts_per_page' => 1, 'paged' => 2 ) );
	vwlm_check( count( $probe->posts ) === 1 && $probe->posts[0]->ID !== $first && in_array( $probe->posts[0]->ID, $expected, true ) && (int) $probe->max_num_pages === count( $expected ), 'Native page two advances the filtered query without changing totals.' );
	vwlm_check( voiceweb_leather_magazine_url( 2 ) === trailingslashit( voiceweb_leather_magazine_url() ) . 'page/2/' && mb_strpos( voiceweb_leather_magazine_title( 'Original' ), 'صفحهٔ ۲' ) !== false, 'Pagination gets a distinct canonical and page-specific title.' );
	vwlm_check( voiceweb_leather_magazine_paginated_url( home_url( '/blog/?lang=en' ), 2 ) === home_url( '/blog/page/2/?lang=en' ), 'Paginated multilingual URLs retain their language query parameter after the path.' );
	add_filter( 'voiceweb/gift/seo-plugin-active', '__return_true', 999 );
	ob_start(); voiceweb_leather_magazine_seo_head(); $plugin_head = ob_get_clean();
	vwlm_check( $plugin_head === '' && voiceweb_leather_magazine_title( 'Plugin title' ) === 'Plugin title', 'An active SEO plugin keeps ownership of metadata and title.' );
	remove_filter( 'voiceweb/gift/seo-plugin-active', '__return_true', 999 );
	$GLOBALS['wp_query'] = $old_query; $GLOBALS['wp_the_query'] = $old_main;
	$blog_url = voiceweb_leather_magazine_url(); $blog = vwlm_dom( $blog_url ); vwlm_metadata( $blog, $blog_url );
	foreach ( array( 1, 2 ) as $alias_page ) {
		$alias_url = home_url( '/category/leather-journal/' . ( $alias_page > 1 ? 'page/' . $alias_page . '/' : '' ) );
		$curl = curl_init( $alias_url ); curl_setopt_array( $curl, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_NOPROXY => '*' ) );
		$alias_response = curl_exec( $curl ); $alias_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE ); curl_close( $curl );
		vwlm_check( $alias_code === 301 && preg_match( '/^Location:\s*' . preg_quote( voiceweb_leather_magazine_url( $alias_page ), '/' ) . '\s*$/mi', $alias_response ) === 1, 'The legacy journal category returns HTTP 301 to its matching magazine page.' );
	}
	$tracking = vwlm_dom( add_query_arg( 'utm_source', 'local-magazine-test', $blog_url ) );
	vwlm_check( $tracking->query( '//link[@rel="canonical"]' )->length === 1 && $tracking->evaluate( 'string(//link[@rel="canonical"]/@href)' ) === $blog_url, 'Tracking parameters canonicalise to the clean magazine URL.' );
	$invalid_url = voiceweb_leather_magazine_url( (int) ceil( count( $expected ) / max( 1, (int) get_option( 'posts_per_page' ) ) ) + 1 );
	$curl = curl_init( $invalid_url ); curl_setopt_array( $curl, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_NOPROXY => '*' ) );
	curl_exec( $curl ); $invalid_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE ); curl_close( $curl );
	vwlm_check( $invalid_code === 404, 'An out-of-range magazine page returns a real HTTP 404.' );
	vwlm_check( $blog->query( '//div[contains(concat(" ",normalize-space(@class)," ")," vw-magazine-grid ")]/div[contains(concat(" ",normalize-space(@class)," ")," cn-card--grid ")]' )->length === min( count( $expected ), (int) get_option( 'posts_per_page' ) ), 'HTTP magazine renders the native first-page article count.' );
	vwlm_check( $blog->query( '//link[@rel="stylesheet" and contains(@href,"leather-magazine.css")]' )->length === 1, 'Magazine styles load once.' );
	$schema_nodes = $blog->query( '//script[@type="application/ld+json" and contains(@class,"vw-magazine-schema")]' );
	vwlm_check( $schema_nodes->length === 1 && ( json_decode( $schema_nodes->item( 0 )->textContent, true, 512, JSON_THROW_ON_ERROR )['@graph'][0]['@type'] ?? '' ) === 'Blog', 'Magazine exposes one valid Blog schema graph.' );
	foreach ( $blog->query( '//div[contains(concat(" ",normalize-space(@class)," ")," vw-magazine-grid ")]//a/@href' ) as $link ) {
		vwlm_check( in_array( url_to_postid( $link->nodeValue ), $expected, true ), 'Every magazine card links to a leather article.' );
	}
	vwlm_check( $blog->query( '//nav[@class="vw-magazine-discovery"]//a[contains(@href,"/product-category/")]' )->length >= 4 && $blog->query( '//nav[@class="vw-magazine-discovery"]//a[contains(@href,"/product/")]' )->length >= 1 && $blog->query( '//nav[@class="vw-magazine-discovery"]//a[contains(@href,"/gifts/")]' )->length === 1, 'Crawlable collection, existing product and occasion links are present.' );
	foreach ( $expected as $id ) {
		$url = get_permalink( $id ); $article = vwlm_dom( $url ); vwlm_metadata( $article, $url );
		$latest = $article->query( '//aside[contains(concat(" ",normalize-space(@class)," ")," article-side ")]//a[contains(concat(" ",normalize-space(@class)," ")," cn-card--latest ")]/@href' );
		vwlm_check( $latest->length === min( 6, count( $expected ) - 1 ), 'Leather article sidebar shows only other published leather guides.' );
		foreach ( $latest as $link ) { $related_id = url_to_postid( $link->nodeValue ); vwlm_check( $related_id !== $id && in_array( $related_id, $expected, true ), 'Sidebar does not link to legacy tourism content.' ); }
		vwlm_check( $article->query( '//article//nav[@class="vw-magazine-discovery"]//a' )->length >= 6, 'Each leather article links to collections, a product and gift discovery.' );
	}
	if ( $legacy_ids ) {
		$legacy = vwlm_dom( get_permalink( $legacy_ids[0] ) );
		vwlm_check( $legacy->query( '//article//nav[@class="vw-magazine-discovery"]' )->length === 0, 'Legacy article URLs remain public and their editorial layout is unchanged.' );
	}
	$remaining = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'category__not_in' => array( $term ), 'numberposts' => -1, 'fields' => 'ids' ) );
	vwlm_check( $remaining === $legacy_ids, 'Legacy published article records are preserved.' );
	echo 'PASS: ' . $checks . " leather magazine query + actual HTTP/source checks. No content records, options, or files changed by this test. No visual rendering claim.\n";
} catch ( Throwable $error ) { fwrite( STDERR, 'FAIL after ' . $checks . ' checks: ' . $error->getMessage() . PHP_EOL ); exit( 1 ); }
finally { $GLOBALS['wp_query'] = $old_query; $GLOBALS['wp_the_query'] = $old_main; }
