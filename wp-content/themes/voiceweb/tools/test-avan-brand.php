<?php
/** Read-only local WordPress and HTTP verification of the configured Avan Charm identity. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
$avan_host = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.(?:local|test))$/i', $avan_host ) ) { fwrite( STDERR, "Only local development hosts are supported.\n" ); exit( 1 ); }
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/avan-brand-readonly-tests' );
$_SERVER['HTTP_HOST'] = $avan_host;
$_SERVER['REQUEST_URI'] = '/';
$GLOBALS['wp_filter']['pre_http_request'][PHP_INT_MAX][] = array( 'function' => static fn() => new WP_Error( 'test_offline', 'External requests disabled.' ), 'accepted_args' => 3 );
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array( 'function' => static fn() => false, 'accepted_args' => 2 );
require dirname( __DIR__, 4 ) . '/wp-load.php';

$avan_checks = 0;
$avan_pages = 0;
$avan_brand = 'آوان چرم';
$avan_phone = '09101719687';
$avan_base = home_url( '/' );
$avan_spec = require dirname( __DIR__ ) . '/config/avan-brand-content.php';
$avan_visibility_before = array( get_option( 'blog_public' ), get_option( 'woocommerce_coming_soon' ), get_option( 'woocommerce_store_pages_only' ) );

function avan_test_check( bool $condition, string $message ): void {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	$GLOBALS['avan_checks']++;
}

/** Direct cURL is confined to this local host; WordPress outbound/mail calls remain blocked. */
function avan_test_fetch( string $url ): string {
	avan_test_check( wp_parse_url( $url, PHP_URL_HOST ) === $GLOBALS['avan_host'] && in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ), 'All requests must stay on the configured local site.' );
	$curl = curl_init( $url );
	curl_setopt_array( $curl, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_NOPROXY => '*', CURLOPT_USERAGENT => 'Avan-Local-ReadOnly-Brand-Test' ) );
	$html = curl_exec( $curl ); $status = curl_getinfo( $curl, CURLINFO_HTTP_CODE ); $error = curl_error( $curl );
	curl_close( $curl );
	avan_test_check( is_string( $html ) && $status === 200, $url . ' must return HTTP 200: ' . $status . ' ' . $error );
	return $html;
}

function avan_test_normalize_digits( string $value ): string {
	return strtr( $value, array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9' ) );
}

function avan_test_page( string $url ): DOMXPath {
	$html = avan_test_fetch( $url );
	avan_test_check( ! preg_match( '/(?:Fatal error|Parse error|Warning):/i', $html ), $url . ' must not expose PHP diagnostics.' );
	$document = new DOMDocument();
	libxml_use_internal_errors( true ); $document->loadHTML( '<?xml encoding="UTF-8">' . $html ); libxml_clear_errors();
	$xpath = new DOMXPath( $document );
	avan_test_check( $xpath->query( '//h1' )->length === 1, $url . ' must have exactly one H1.' );
	avan_test_check( $xpath->query( '//head/title' )->length === 1 && strpos( $xpath->evaluate( 'string(//head/title)' ), $GLOBALS['avan_brand'] ) !== false, $url . ' must have one title with the confirmed brand.' );
	avan_test_check( $xpath->query( '//meta[@name="description"]' )->length === 1 && trim( $xpath->evaluate( 'string(//meta[@name="description"]/@content)' ) ) !== '', $url . ' must have one nonempty description.' );
	avan_test_check( $xpath->query( '//link[@rel="canonical"]' )->length === 1 && $xpath->evaluate( 'string(//link[@rel="canonical"]/@href)' ) === $url, $url . ' must have one self-canonical URL.' );
	avan_test_check( $xpath->evaluate( 'string(/html/@dir)' ) === 'rtl', $url . ' must preserve RTL direction.' );
	$logos = $xpath->query( '//a[contains(concat(" ",normalize-space(@class)," ")," avan-brand ")]' );
	avan_test_check( $logos->length === 3, $url . ' must render shared desktop, mobile and footer logos.' );
	foreach ( $logos as $logo ) {
		avan_test_check( $logo->getAttribute( 'href' ) === $GLOBALS['avan_base'] && strpos( $logo->getAttribute( 'aria-label' ), $GLOBALS['avan_brand'] ) !== false, $url . ' logo must link home with the confirmed accessible brand name.' );
	}
	avan_test_check( $xpath->query( '//img[contains(@src,"avan-charm-mark.svg")]' )->length === 3, $url . ' must use the new default vector mark in every logo.' );
	avan_test_check( $xpath->query( '//link[@rel="stylesheet" and contains(@href,"/avan-brand.css")]' )->length === 1, $url . ' must load logo styling exactly once.' );
	$phones = $xpath->query( '//a[starts-with(@href,"tel:")]' );
	avan_test_check( $phones->length >= 2, $url . ' must expose the confirmed telephone in header and footer.' );
	foreach ( $phones as $phone ) { avan_test_check( $phone->getAttribute( 'href' ) === 'tel:' . $GLOBALS['avan_phone'], $url . ' contains an incorrect telephone link.' ); }
	$visible_text = '';
	foreach ( $xpath->query( '//body//text()[not(ancestor::script) and not(ancestor::style)] | //body//@alt | //body//@aria-label | //body//@title' ) as $node ) { $visible_text .= ' ' . $node->nodeValue; }
	avan_test_check( ! preg_match( '/VoiceWeb|ویس\s*وب/iu', $visible_text ), $url . ' retains the old public branding.' );
	$digits = preg_replace( '/[\s\-()]+/u', '', avan_test_normalize_digits( $visible_text ) );
	avan_test_check( strpos( $digits, '02143997' ) === false, $url . ' retains the old phone number.' );
	foreach ( $xpath->query( '//script[@type="application/ld+json"]' ) as $schema ) { json_decode( $schema->textContent, true, 512, JSON_THROW_ON_ERROR ); }
	$GLOBALS['avan_pages']++;
	return $xpath;
}

function avan_test_schema_type( DOMXPath $xpath, string $type ): int {
	$count = 0;
	foreach ( $xpath->query( '//script[@type="application/ld+json"]' ) as $script ) {
		$json = json_decode( $script->textContent, true, 512, JSON_THROW_ON_ERROR );
		$nodes = isset( $json['@graph'] ) ? $json['@graph'] : array( $json );
		foreach ( $nodes as $node ) { if ( in_array( $type, (array) ( $node['@type'] ?? array() ), true ) ) { $count++; } }
	}
	return $count;
}

try {
	avan_test_check( function_exists( 'voiceweb_brand_page_url' ) && function_exists( 'voiceweb_gift_pages' ), 'Brand and gift modules must be loaded.' );
	avan_test_check( get_option( 'voiceweb_avan_brand_version' ) && get_option( 'blogname' ) === $avan_brand, 'The explicit Avan Charm setup must have been applied.' );
	$backup = get_option( 'voiceweb_avan_brand_backup_v1' );
	avan_test_check( is_array( $backup ) && ! empty( $backup['posts'] ) && ! empty( $backup['options'] ) && ! empty( $backup['menus'] ), 'The previous public identity and content must have a private backup.' );
	foreach ( array( 'cn_header_phone', 'cn_header_phone_link', 'cn_footer_phone', 'cn_footer_phone_link' ) as $setting ) { avan_test_check( cn_get_site_setting( $setting, '' ) === $avan_phone, $setting . ' must use the confirmed phone.' ); }
	$front_id = (int) get_option( 'page_on_front' );
	avan_test_check( strpos( (string) voiceweb_front_value( 'seo_title' ), $avan_brand ) !== false && strpos( (string) voiceweb_front_value( 'about_description' ), $avan_brand ) !== false, 'Homepage SEO and editable brand story must use Avan Charm.' );
	$urls = array( 'home' => $avan_base );
	$page_ids = array();
	foreach ( $avan_spec as $kind => $spec ) {
		$url = voiceweb_brand_page_url( $kind ); $id = url_to_postid( $url ); $page = $id ? get_post( $id ) : null;
		avan_test_check( $page instanceof WP_Post && $page->post_status === 'publish' && $page->post_password === '', $kind . ' discovery must resolve a public unprotected page.' );
		avan_test_check( get_page_template_slug( $id ) === 'page-templates/' . $kind . '.php', $kind . ' must use its dedicated template.' );
		avan_test_check( $page->post_title === $spec['title'] && $page->post_content === $spec['content'], $kind . ' must store the new editable page title and body.' );
		foreach ( array( 'intro', 'seo_title', 'seo_description', 'faq' ) as $field ) { avan_test_check( voiceweb_brand_value( $field, '', $id ) === $spec[ $field ], $kind . ' editable ' . $field . ' must match the authored content.' ); }
		if ( $kind === 'about' ) {
			foreach ( array( 'process', 'leather' ) as $field ) { avan_test_check( voiceweb_brand_value( $field, array(), $id ) === $spec[ $field ], 'About editable ' . $field . ' must match the authored sections.' ); }
		} else { avan_test_check( voiceweb_brand_value( 'phone', '', $id ) === $avan_phone, 'Contact-page phone must match the confirmed number.' ); }
		$urls[ $kind ] = $url; $page_ids[ $kind ] = $id;
	}
	// Exercise metadata privacy in memory only; no page, account or setting is changed.
	$previous_query = $GLOBALS['wp_query'];
	$previous_post = $GLOBALS['post'] ?? null;
	try {
		foreach ( array( 'private', 'password' ) as $protection ) {
			$page = clone get_post( $page_ids['about'] );
			$page->post_status = $protection === 'private' ? 'private' : 'publish';
			$page->post_password = $protection === 'password' ? 'private-test-copy' : '';
			$query = new WP_Query(); $query->is_page = true; $query->is_singular = true;
			$query->queried_object = $page; $query->queried_object_id = $page->ID;
			$GLOBALS['wp_query'] = $query; $GLOBALS['post'] = $page;
			ob_start(); voiceweb_brand_seo_head(); $private_meta = ob_get_clean();
			avan_test_check( ! voiceweb_brand_seo_context() && $private_meta === '', $protection . ' brand-page copy must not appear in fallback metadata.' );
		}
		$page = clone get_post( $page_ids['about'] );
		$query->queried_object = $page; $GLOBALS['post'] = $page;
		add_filter( 'voiceweb/gift/seo-plugin-active', '__return_true' );
		ob_start(); voiceweb_brand_seo_head(); $plugin_meta = ob_get_clean();
		avan_test_check( $plugin_meta === '' && voiceweb_brand_seo_title( 'Plugin-owned title' ) === 'Plugin-owned title', 'An active SEO plugin must retain sole ownership of brand metadata.' );
	} finally {
		remove_filter( 'voiceweb/gift/seo-plugin-active', '__return_true' );
		$GLOBALS['wp_query'] = $previous_query; $GLOBALS['post'] = $previous_post;
	}
	$gift_pages = voiceweb_gift_pages( 1 );
	avan_test_check( ! empty( $gift_pages ), 'A published gift guide must remain available.' );
	$urls['gift'] = get_permalink( $gift_pages[0] );
	$products = wc_get_products( array( 'status' => 'publish', 'visibility' => 'catalog', 'limit' => 1 ) );
	avan_test_check( ! empty( $products ), 'At least one published catalogue product must remain available.' );
	$urls['product'] = $products[0]->get_permalink();
	$main_menu = cn_get_site_menu_id( 'cn_header_menu' );
	$items = wp_get_nav_menu_items( $main_menu ) ?: array();
	$required_menu = array( 'about' => array( 'page', $page_ids['about'] ), 'contact' => array( 'page', $page_ids['contact'] ), 'gifts' => array( 'page', voiceweb_gift_hub_id() ) );
	foreach ( array( 'leather-men-bags', 'leather-women-bags' ) as $slug ) { $term = get_term_by( 'slug', $slug, 'product_cat' ); avan_test_check( $term instanceof WP_Term, $slug . ' category must exist.' ); $required_menu[ $slug ] = array( 'product_cat', $term->term_id ); }
	foreach ( $required_menu as $label => $target ) {
		$matches = array_filter( $items, static fn( $item ) => $item->object === $target[0] && (int) $item->object_id === (int) $target[1] );
		avan_test_check( count( $matches ) === 1, $label . ' must have exactly one native menu item.' );
		$item = reset( $matches );
		avan_test_check( $item->type === ( $target[0] === 'product_cat' ? 'taxonomy' : 'post_type' ) && $item->post_status === 'publish', $label . ' navigation must bind a published native object.' );
	}
	$loaded = array();
	foreach ( $urls as $label => $url ) {
		$xpath = avan_test_page( $url ); $loaded[ $label ] = $xpath;
		if ( isset( $avan_spec[ $label ] ) ) {
			$spec = $avan_spec[ $label ];
			avan_test_check( trim( $xpath->evaluate( 'string(//h1)' ) ) === $spec['title'], $label . ' H1 must match the edited title.' );
			avan_test_check( trim( $xpath->evaluate( 'string(//head/title)' ) ) === $spec['seo_title'], $label . ' SEO title must be rendered exactly once.' );
			avan_test_check( $xpath->evaluate( 'string(//meta[@name="description"]/@content)' ) === wp_html_excerpt( $spec['seo_description'], 180, '…' ), $label . ' meta description must match the edited description.' );
			avan_test_check( avan_test_schema_type( $xpath, $label === 'about' ? 'AboutPage' : 'ContactPage' ) === 1 && avan_test_schema_type( $xpath, 'Organization' ) === 1, $label . ' must have one matching page schema and one organization.' );
			avan_test_check( $xpath->query( '//link[@rel="stylesheet" and contains(@href,"brand-pages.css")]' )->length === 1, $label . ' must load its page styles once.' );
			avan_test_check( $xpath->query( '//main[contains(@id,"brand-")]//details' )->length === count( $spec['faq'] ), $label . ' must render its editable FAQ rows.' );
		} elseif ( $label === 'home' ) { avan_test_check( avan_test_schema_type( $xpath, 'Organization' ) === 1, 'Homepage must expose one brand organization graph.' ); }
		elseif ( $label === 'gift' ) { avan_test_check( avan_test_schema_type( $xpath, 'CollectionPage' ) === 1 && avan_test_schema_type( $xpath, 'BreadcrumbList' ) === 1, 'Gift collection schema must remain intact after rebranding.' ); }
		echo 'PASS source: ' . $label . ' ' . $url . "\n";
	}
	$contact = $loaded['contact'];
	avan_test_check( $contact->query( '//main[@id="brand-contact"]//a[@href="tel:09101719687"]' )->length === 1, 'Contact page must offer the exact confirmed telephone link.' );
	foreach ( array( 'email' => 'starts-with(@href,"mailto:")', 'whatsapp' => 'contains(@href,"wa.me/")', 'instagram_url' => 'contains(@href,"instagram.com")' ) as $field => $predicate ) {
		if ( voiceweb_brand_value( $field, '', $page_ids['contact'] ) === '' ) { avan_test_check( $contact->query( '//main[@id="brand-contact"]//a[' . $predicate . ']' )->length === 0, 'Unconfigured ' . $field . ' must not be invented.' ); }
	}
	if ( voiceweb_brand_value( 'address', '', $page_ids['contact'] ) === '' && voiceweb_brand_value( 'hours', '', $page_ids['contact'] ) === '' ) { avan_test_check( $contact->query( '//section[contains(@class,"vw-brand-contact-details")]' )->length === 0, 'Unconfigured address/hours must not be invented.' ); }
	foreach ( $required_menu as $label => $target ) {
		$url = $target[0] === 'product_cat' ? get_term_link( $target[1], 'product_cat' ) : get_permalink( $target[1] );
		$header_links = $loaded['home']->query( '//nav[contains(@class,"cn-header-nav")]//a/@href' ); $found = false;
		foreach ( $header_links as $href ) { if ( $href->nodeValue === $url ) { $found = true; break; } }
		avan_test_check( $found, $label . ' must be reachable through the rendered desktop menu.' );
		if ( ! in_array( $url, $urls, true ) ) { avan_test_fetch( $url ); }
	}
	$css_url = $loaded['home']->evaluate( 'string(//link[@rel="stylesheet" and contains(@href,"/avan-brand.css")]/@href)' );
	$css = avan_test_fetch( $css_url );
	avan_test_check( strpos( $css, 'prefers-reduced-motion' ) !== false && strpos( $css, 'animation: none !important' ) !== false && strpos( $css, 'pointer-events: none' ) !== false, 'Logo shine must include reduced-motion support and must not intercept clicks.' );
	if ( ! get_option( 'site_icon' ) ) {
		$favicon = $loaded['home']->query( '//link[@rel="icon" and @type="image/svg+xml" and contains(@href,"avan-charm-mark.svg")]' );
		avan_test_check( $favicon->length === 1, 'The default browser icon must use the Avan Charm vector mark once.' );
		$mark = avan_test_fetch( $favicon->item( 0 )->getAttribute( 'href' ) );
		avan_test_check( (bool) simplexml_load_string( $mark ) && ! preg_match( '/<(?:script|foreignObject|image|use|animate)\b|(?:href|onload|onclick)\s*=/i', $mark ), 'The public brand mark must be a valid, self-contained SVG without scripts or external references.' );
	}
	avan_test_check( array( get_option( 'blog_public' ), get_option( 'woocommerce_coming_soon' ), get_option( 'woocommerce_store_pages_only' ) ) === $avan_visibility_before, 'Read-only verification must preserve indexing and Coming Soon options.' );
	$gate = $loaded['product']->query( '//body[contains(@class,"woocommerce")]//*[contains(@class,"wp-block-woocommerce-coming-soon")]' )->length > 0 || strpos( $loaded['product']->evaluate( 'string(//h1)' ), 'اتفاقات بزرگی' ) !== false;
	echo 'PASS: ' . $avan_checks . ' read-only checks across ' . $avan_pages . " local pages. HTML/metadata verified; visual rendering was not tested.\n";
	if ( $gate ) { echo "Product content remains behind the existing Coming Soon screen; its shared branding and metadata were checked without changing visibility.\n"; }
} catch ( Throwable $error ) { fwrite( STDERR, 'FAIL after ' . $avan_checks . ' checks: ' . $error->getMessage() . PHP_EOL ); exit( 1 ); }
