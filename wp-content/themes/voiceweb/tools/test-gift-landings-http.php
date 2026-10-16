<?php
/** Read-only source checks for seeded local gift pages. No browser/layout assertions. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
$host = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.local)$/i', $host ) ) {
	fwrite( STDERR, "Only local HTTP hosts are supported.\n" ); exit( 1 );
}
define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );
$specs = require dirname( __DIR__ ) . '/config/gift-occasions.php';
$base = 'http://' . $host;
$checks = 0; $page_count = 0;

function vwgh_check( bool $condition, string $message ): void {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	$GLOBALS['checks']++;
}
function vwgh_fetch( string $url ): string {
	vwgh_check( parse_url( $url, PHP_URL_HOST ) === $GLOBALS['host'], 'Every requested asset must remain on the local site.' );
	$curl = curl_init( $url );
	curl_setopt_array( $curl, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_NOPROXY => '*', CURLOPT_USERAGENT => 'VoiceWeb-Local-ReadOnly-Source-Test' ) );
	$source = curl_exec( $curl ); $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE ); $error = curl_error( $curl );
	curl_close( $curl );
	vwgh_check( is_string( $source ) && $code === 200, $url . ' must return HTTP 200: ' . $code . ' ' . $error );
	return $source;
}
function vwgh_load( string $path ): DOMXPath {
	static $stylesheets = array();
	$url = $GLOBALS['base'] . $path;
	$source = vwgh_fetch( $url );
	vwgh_check( ! preg_match( '/(?:Fatal error|Parse error|Warning):/i', $source ), $url . ' exposes a PHP diagnostic.' );
	$document = new DOMDocument();
	libxml_use_internal_errors( true ); $document->loadHTML( $source ); libxml_clear_errors();
	$xpath = new DOMXPath( $document );
	vwgh_check( $xpath->query( '//h1' )->length === 1, $url . ' must have exactly one H1.' );
	vwgh_check( $xpath->query( '//head/title' )->length === 1 && trim( $xpath->evaluate( 'string(//head/title)' ) ) !== '', $url . ' must have a nonempty document title.' );
	vwgh_check( $xpath->query( '//link[@rel="canonical"]' )->length === 1, $url . ' must have one canonical link.' );
	vwgh_check( $xpath->evaluate( 'string(//link[@rel="canonical"]/@href)' ) === $url, $url . ' must be self-canonical.' );
	vwgh_check( $xpath->query( '//link[@rel="stylesheet" and contains(@href,"gift-landing")]' )->length === 1, $url . ' must load the shared gift stylesheet once.' );
	$css_url = $xpath->evaluate( 'string(//link[@rel="stylesheet" and contains(@href,"gift-landing")]/@href)' );
	if ( ! isset( $stylesheets[ $css_url ] ) ) {
		$stylesheets[ $css_url ] = vwgh_fetch( $css_url );
		vwgh_check( strpos( $stylesheets[ $css_url ], '.vw-gift' ) !== false, 'Gift stylesheet response must contain the page styles.' );
	}
	vwgh_check( $xpath->evaluate( 'string(/html/@dir)' ) === 'rtl', $url . ' must retain RTL document direction.' );
	$GLOBALS['page_count']++;
	return $xpath;
}
function vwgh_metadata( DOMXPath $xpath, string $label ): void {
	vwgh_check( $xpath->query( '//meta[@name="description"]' )->length === 1 && trim( $xpath->evaluate( 'string(//meta[@name="description"]/@content)' ) ) !== '', $label . ' must have one nonempty description.' );
	$nodes = $xpath->query( '//script[@type="application/ld+json" and contains(@class,"vw-gift-schema")]' );
	vwgh_check( $nodes->length === 1, $label . ' must have one theme collection schema graph.' );
	$schema = json_decode( $nodes->item( 0 )->textContent, true, 512, JSON_THROW_ON_ERROR );
	vwgh_check( ( $schema['@graph'][0]['@type'] ?? '' ) === 'CollectionPage' && ( $schema['@graph'][1]['@type'] ?? '' ) === 'BreadcrumbList', $label . ' must contain valid collection and breadcrumb schema.' );
}

try {
	$home = vwgh_load( '/' );
	vwgh_check( $home->query( '//meta[@name="description"]' )->length === 1 && trim( $home->evaluate( 'string(//meta[@name="description"]/@content)' ) ) !== '', 'Homepage must have one nonempty SEO description.' );
	vwgh_check( $home->query( '//a[contains(@href,"/gifts/")]' )->length >= 2, 'Homepage must expose crawlable links to the gift hub and guides.' );
	echo "PASS source: /\n";
	$hub = vwgh_load( '/gifts/' ); vwgh_metadata( $hub, 'Gift hub' );
	$hub_links = array();
	foreach ( $hub->query( '//a[contains(concat(" ",normalize-space(@class)," ")," vw-gift-card ")]/@href' ) as $link ) { $hub_links[] = $link->nodeValue; }
	foreach ( array_keys( $specs ) as $slug ) { vwgh_check( in_array( $base . '/gifts/' . $slug . '/', $hub_links, true ), 'Gift hub must link to ' . $slug ); }
	echo 'PASS source: /gifts/ (' . count( $hub_links ) . " guide links)\n";
	foreach ( $specs as $slug => $spec ) {
		$path = '/gifts/' . $slug . '/'; $xpath = vwgh_load( $path ); vwgh_metadata( $xpath, $slug );
		vwgh_check( trim( $xpath->evaluate( 'string(//h1)' ) ) === $spec['title'], $slug . ' H1 must match seeded content.' );
		vwgh_check( trim( $xpath->evaluate( 'string(//head/title)' ) ) === $spec['seo_title'], $slug . ' document title must match editorial SEO title.' );
		vwgh_check( $xpath->evaluate( 'string(//meta[@name="description"]/@content)' ) === $spec['seo_description'], $slug . ' description must match editorial content.' );
		$products = $xpath->query( '//section[@id="gift-products"]//li[contains(concat(" ",normalize-space(@class)," ")," vw-product-card ")]' )->length;
		vwgh_check( $products >= 1 && $products <= 24, $slug . ' must render a bounded collection of existing WooCommerce product cards.' );
		vwgh_check( $xpath->query( '//section[@id="gift-advice"]' )->length === 1, $slug . ' must render its editable buying guide.' );
		vwgh_check( $xpath->query( '//section[@id="gift-faq"]//details' )->length === count( $spec['faq'] ), $slug . ' must render every authored FAQ.' );
		vwgh_check( $xpath->query( '//section[@id="gift-related"]//a[contains(concat(" ",normalize-space(@class)," ")," vw-gift-card ")]' )->length >= 1, $slug . ' must link to related occasions.' );
		vwgh_check( $xpath->query( '//header[contains(@class,"vw-gift-hero")]//img[@fetchpriority="high" and @loading="eager"]' )->length === 1, $slug . ' must prioritise its hero image.' );
		echo 'PASS source: ' . $path . ' (' . $products . " products)\n";
	}
	echo 'PASS: ' . $checks . ' HTTP/source checks across ' . $page_count . " local pages. This verifies HTML and metadata, not visual rendering.\n";
} catch ( Throwable $error ) { fwrite( STDERR, 'FAIL after ' . $checks . ' HTTP/source checks: ' . $error->getMessage() . PHP_EOL ); exit( 1 ); }
