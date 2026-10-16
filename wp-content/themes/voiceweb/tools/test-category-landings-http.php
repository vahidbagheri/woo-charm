<?php
/** Read-only local category source checks. This does not assess visual rendering. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
$host = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.local)$/i', $host ) ) { fwrite( STDERR, "Local hosts only.\n" ); exit( 1 ); }
define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );
$config = require dirname( __DIR__ ) . '/config/category-landings.php';
$cli_templates = in_array( '--cli-templates', $argv, true );
$checks = 0;
function vwch_check( bool $condition, string $message ): void { if ( ! $condition ) { throw new RuntimeException( $message ); } $GLOBALS['checks']++; }
function vwch_fetch( string $url ): string {
	vwch_check( parse_url( $url, PHP_URL_HOST ) === $GLOBALS['host'], 'Requests must remain on the local site.' );
	if ( $GLOBALS['cli_templates'] && preg_match( '~^/product-category/(leather-[a-z-]+)/$~', (string) parse_url( $url, PHP_URL_PATH ), $match ) ) {
		parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $params );
		$process = proc_open( array( PHP_BINARY, '-d', 'mysqli.default_port=10113', __DIR__ . '/render-category-template.php', $match[1], $params['orderby'] ?? '' ), array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes, null, null, array( 'bypass_shell' => true ) );
		vwch_check( is_resource( $process ), 'CLI template process must start.' );
		fclose( $pipes[0] ); $body = stream_get_contents( $pipes[1] ); fclose( $pipes[1] ); $error = stream_get_contents( $pipes[2] ); fclose( $pipes[2] ); $status = proc_close( $process );
		vwch_check( $status === 0 && $body !== '' && ! preg_match( '/(?:Fatal error|Parse error|Warning):/i', $body . $error ), 'CLI template render must complete cleanly: ' . $error );
		return $body;
	}
	$curl = curl_init( $url ); curl_setopt_array( $curl, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_NOPROXY => '*', CURLOPT_USERAGENT => 'VoiceWeb-Local-Category-Source-Test' ) );
	$body = curl_exec( $curl ); $status = curl_getinfo( $curl, CURLINFO_HTTP_CODE ); $error = curl_error( $curl ); curl_close( $curl );
	vwch_check( is_string( $body ) && $status === 200, $url . ' must return 200; got ' . $status . ' ' . $error );
	vwch_check( ! preg_match( '/(?:Fatal error|Parse error|Warning):/i', $body ), $url . ' must not expose PHP diagnostics.' );
	if ( strpos( (string) parse_url( $url, PHP_URL_PATH ), '/product-category/' ) === 0 && strpos( $body, 'data-vw-catalog' ) === false && preg_match( '/coming[\s_-]?soon/i', $body ) ) {
		throw new RuntimeException( 'Public category is behind the WooCommerce coming-soon screen: ' . $url . '. Visibility was not changed. --cli-templates checks the actual theme template separately.' );
	}
	return $body;
}
function vwch_dom( string $source ): DOMXPath { $document = new DOMDocument(); libxml_use_internal_errors( true ); $document->loadHTML( $source ); libxml_clear_errors(); return new DOMXPath( $document ); }
function vwch_types( array $value ): array { $types = array(); foreach ( $value as $key => $item ) { if ( $key === '@type' ) { $types = array_merge( $types, (array) $item ); } elseif ( is_array( $item ) ) { $types = array_merge( $types, vwch_types( $item ) ); } } return $types; }
try {
	$home = vwch_dom( vwch_fetch( 'http://' . $host . '/' ) ); $css_checked = false;
	foreach ( $config as $slug => $spec ) {
		$links = $home->query( '//a[contains(@href,"/' . $slug . '/")]/@href' );
		vwch_check( $links->length > 0, 'Homepage must link directly to native category ' . $slug );
		$url = $links->item( 0 )->nodeValue; $xpath = vwch_dom( vwch_fetch( $url ) );
		vwch_check( $xpath->query( '//h1' )->length === 1, $slug . ' must have one H1.' );
		vwch_check( $xpath->query( '//head/title' )->length === 1 && trim( $xpath->evaluate( 'string(//head/title)' ) ) === $spec['seo_title'], $slug . ' must use its authored SEO title.' );
		vwch_check( $xpath->query( '//meta[@name="description"]' )->length === 1 && $xpath->evaluate( 'string(//meta[@name="description"]/@content)' ) === $spec['seo_description'], $slug . ' must use one authored description.' );
		vwch_check( $xpath->query( '//link[@rel="canonical"]' )->length === 1 && $xpath->evaluate( 'string(//link[@rel="canonical"]/@href)' ) === $url, $slug . ' must have one self-canonical.' );
		vwch_check( trim( $xpath->evaluate( 'string(//div[@class="term-description"])' ) ) === $spec['intro'], $slug . ' must show its editable intro.' );
		vwch_check( $xpath->query( '//section[@data-vw-catalog]//section[@id="category-advice"]' )->length === 1, $slug . ' buying guide must live inside the replaceable catalog root.' );
		vwch_check( $xpath->query( '//div[@class="vw-category-tips"]/article' )->length === 3 && $xpath->query( '//section[@class="vw-category-faq"]//details' )->length === 3, $slug . ' must render three tips and three FAQs.' );
		vwch_check( $xpath->query( '//form[@data-vw-filter-form]' )->length === 1 && $xpath->query( '//a[@data-vw-catalog-link]' )->length > 5, $slug . ' must retain native filter form and sortable links.' );
		$product_count = $xpath->query( '//li[contains(concat(" ",normalize-space(@class)," ")," vw-product-card ")]' )->length;
		vwch_check( $product_count > 0, $slug . ' must retain WooCommerce product cards.' );
		vwch_check( $xpath->query( '//nav[@class="vw-category-discovery"]//a' )->length === 6, $slug . ' must render three complementary categories and three gift guides.' );
		$types = array(); foreach ( $xpath->query( '//script[@type="application/ld+json"]' ) as $node ) { $data = json_decode( $node->textContent, true, 512, JSON_THROW_ON_ERROR ); if ( is_array( $data ) ) { $types = array_merge( $types, vwch_types( $data ) ); } }
		$counts = array_count_values( $types );
		vwch_check( ( $counts['CollectionPage'] ?? 0 ) === 1 && ( $counts['BreadcrumbList'] ?? 0 ) === 1 && empty( $counts['Product'] ) && empty( $counts['FAQPage'] ), $slug . ' must have one collection and native breadcrumb schema, without synthetic Product or FAQPage markup.' );
		$css = $xpath->query( '//link[@rel="stylesheet" and contains(@href,"category-guide")]' );
		vwch_check( $css->length === 1, $slug . ' must load category styles once.' );
		if ( ! $css_checked ) { vwch_check( strpos( vwch_fetch( $css->item( 0 )->getAttribute( 'href' ) ), '.vw-category-guide' ) !== false, 'Category stylesheet must return its styles.' ); $css_checked = true; }
		$filtered = vwch_dom( vwch_fetch( $url . '?orderby=price' ) );
		vwch_check( $filtered->query( '//section[@id="category-advice"]' )->length === 0 && $filtered->query( '//nav[@class="vw-category-discovery"]//a' )->length === 6, $slug . ' sorted view must suppress long copy while retaining browse links.' );
		vwch_check( $filtered->query( '//link[@rel="canonical"]' )->length === 1 && $filtered->evaluate( 'string(//link[@rel="canonical"]/@href)' ) === $url && strpos( $filtered->evaluate( 'string(//meta[@name="robots"]/@content)' ), 'noindex' ) !== false, $slug . ' sorted view must canonicalise to base and request noindex.' );
		vwch_check( $filtered->query( '//li[contains(concat(" ",normalize-space(@class)," ")," vw-product-card ")]' )->length === $product_count, $slug . ' sorting must retain purchasable collection contents.' );
		echo 'PASS source: ' . $slug . ' (' . $product_count . " products, clean + sorted views)\n";
	}
	echo 'PASS: ' . $checks . ' category ' . ( $cli_templates ? 'CLI template/source' : 'HTTP/source' ) . " checks across six native category pages and their sorted variants. No visual rendering claim.\n";
} catch ( Throwable $error ) { fwrite( STDERR, 'FAIL after ' . $checks . ' checks: ' . $error->getMessage() . PHP_EOL ); exit( 1 ); }
