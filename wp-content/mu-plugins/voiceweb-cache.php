<?php
/**
 * Plugin Name: VoiceWeb Store Cache
 * Description: Reversible guest-page cache. Rename this file to voiceweb-cache.php.disabled to turn it off.
 * No advanced-cache.php, WP_CACHE flag, scheduled jobs or external service is installed.
 */
if ( ! defined( 'ABSPATH' ) ) { http_response_code( 404 ); exit; }
if ( defined( 'VOICEWEB_CACHE_DISABLED' ) && VOICEWEB_CACHE_DISABLED ) { return; }

function voiceweb_cache_directory(): string { return WP_CONTENT_DIR . '/cache/voiceweb-pages'; }
function voiceweb_cache_revision(): string {
	static $revision;
	if ( $revision !== null ) { return $revision; }
	$parts = array( (string) get_option( 'voiceweb_cache_generation', '1' ), (string) get_option( 'home' ), (string) filemtime( __FILE__ ), (string) filemtime( __DIR__ ) );
	foreach ( array_unique( array( get_option( 'template' ), get_option( 'stylesheet' ) ) ) as $theme ) {
		$root = WP_CONTENT_DIR . '/themes/' . basename( (string) $theme );
		if ( ! is_dir( $root ) ) { continue; }
		$directory = new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS );
		$filtered = new RecursiveCallbackFilterIterator( $directory, static function ( $file ) {
			return ! $file->isDir() || ! in_array( $file->getFilename(), array( '.git', 'node_modules', 'vendor', 'Doc', 'tools', 'languages' ), true );
		} );
		foreach ( new RecursiveIteratorIterator( $filtered ) as $file ) {
			if ( $file->isFile() && preg_match( '/\.(php|css|m?js|json|svg|avif|webp|png|jpe?g|gif|ico|woff2?|ttf|otf|eot)$/i', $file->getFilename() ) ) { $parts[] = $file->getPathname() . ':' . $file->getMTime() . ':' . $file->getSize(); }
		}
	}
	return $revision = hash( 'sha256', implode( '|', $parts ) );
}

/** Fail closed for sessions, action/query URLs and every non-public request. */
function voiceweb_cache_request(): ?array {
	if ( PHP_SAPI === 'cli' || ! in_array( $_SERVER['REQUEST_METHOD'] ?? '', array( 'GET', 'HEAD' ), true ) || defined( 'DOING_CRON' ) || defined( 'DOING_AJAX' ) || defined( 'REST_REQUEST' ) || ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) { return null; }
	$url = wp_parse_url( (string) get_option( 'home' ) );
	$host = strtolower( (string) ( $_SERVER['HTTP_HOST'] ?? '' ) );
	$expected = strtolower( ( $url['host'] ?? '' ) . ( isset( $url['port'] ) ? ':' . $url['port'] : '' ) );
	if ( ! $expected || $host !== $expected ) { return null; }
	$uri = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	if ( ! $path || preg_match( '~(?:wp-admin|wp-login|wp-json|wp-cron|xmlrpc|cart|checkout|my-account|wc-api|feed|sitemap|robots\.txt|\.php|\.\.)~i', rawurldecode( $path ) ) ) { return null; }
	foreach ( array_keys( $_GET ) as $key ) { if ( $key !== 'lang' ) { return null; } }
	$lang = $_GET['lang'] ?? '';
	if ( ! is_string( $lang ) || ( $lang !== '' && ! preg_match( '/^[a-zA-Z-]{2,12}$/D', $lang ) ) ) { return null; }
	foreach ( $_COOKIE as $name => $value ) {
		if ( ! in_array( $name, array( 'wp-wpml_current_language', 'wpml_browser_redirect_test' ), true ) ) { return null; }
	}
	$cookie_language = $_COOKIE['wp-wpml_current_language'] ?? '';
	if ( ! is_string( $cookie_language ) || strlen( $cookie_language ) > 12 ) { return null; }
	$scheme = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) || ( $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '' ) === 'https' ? 'https' : 'http';
	return array( 'url' => $scheme . '://' . $host . $path . ( $lang ? '?lang=' . $lang : '' ), 'language' => $cookie_language );
}

function voiceweb_cache_invalidate(): void { $GLOBALS['voiceweb_cache_dirty'] = true; }
function voiceweb_cache_purge(): void {
	update_option( 'voiceweb_cache_generation', sprintf( '%.6f', microtime( true ) ), false );
	// Old generations are never served; remove only this module's generated files.
	foreach ( glob( voiceweb_cache_directory() . '/[a-f0-9]*.php' ) ?: array() as $file ) { @unlink( $file ); }
	$GLOBALS['voiceweb_cache_dirty'] = true;
}
function voiceweb_cache_shutdown(): void { if ( ! empty( $GLOBALS['voiceweb_cache_dirty'] ) ) { voiceweb_cache_purge(); } }

function voiceweb_cache_option_changed( string $name ): void {
	if ( str_starts_with( $name, 'voiceweb_cache_' ) || str_starts_with( $name, '_transient_' ) || str_starts_with( $name, '_site_transient_' ) || in_array( $name, array( 'cron', 'voiceweb_sale_expiry_lease' ), true ) ) { return; }
	if ( is_admin() || PHP_SAPI === 'cli' || preg_match( '/^(blogname|blogdescription|page_on_front|show_on_front|home|siteurl|blog_public|theme_mods_|options_|voiceweb_usd_)/', $name ) ) { voiceweb_cache_invalidate(); }
}
foreach ( array( 'save_post', 'deleted_post', 'added_post_meta', 'updated_post_meta', 'deleted_post_meta', 'edited_term', 'created_term', 'delete_term', 'set_object_terms', 'wp_update_nav_menu', 'customize_save_after', 'woocommerce_product_set_stock', 'woocommerce_variation_set_stock', 'woocommerce_update_product', 'woocommerce_update_product_variation' ) as $hook ) { add_action( $hook, 'voiceweb_cache_invalidate', PHP_INT_MAX, 0 ); }
foreach ( array( 'updated_option', 'added_option', 'deleted_option' ) as $hook ) { add_action( $hook, 'voiceweb_cache_option_changed', PHP_INT_MAX, 1 ); }
add_action( 'shutdown', 'voiceweb_cache_shutdown', PHP_INT_MAX );
add_action( 'voiceweb_catalog_pricing_committed', 'voiceweb_cache_purge' );
foreach ( array( 'added_term_meta', 'updated_term_meta', 'deleted_term_meta' ) as $hook ) { add_action( $hook, 'voiceweb_cache_invalidate', PHP_INT_MAX, 0 ); }
foreach ( array( 'upgrader_process_complete', 'switch_theme', 'activated_plugin', 'deactivated_plugin' ) as $hook ) { add_action( $hook, 'voiceweb_cache_invalidate', PHP_INT_MAX, 0 ); }

function voiceweb_cache_expiry(): int {
	global $wpdb;
	$now = time(); $expiry = $now + 300;
	// Include future products as well as visible offers, so a new sale also invalidates cached lists.
	$boundary = $wpdb->get_var( $wpdb->prepare(
		"SELECT MIN(CAST(m.meta_value AS UNSIGNED) + IF(m.meta_key='_sale_price_dates_to',1,0))
		 FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id
		 WHERE p.post_type IN ('product','product_variation') AND p.post_status='publish'
		 AND m.meta_key IN ('_sale_price_dates_from','_sale_price_dates_to') AND CAST(m.meta_value AS UNSIGNED)>=%d", $now
	) );
	return $boundary ? min( $expiry, (int) $boundary ) : $expiry;
}

function voiceweb_cache_capture(): void {
	$request = $GLOBALS['voiceweb_cache_request'] ?? null;
	if ( ! $request || $_SERVER['REQUEST_METHOD'] !== 'GET' || is_user_logged_in() || is_preview() || is_404() || is_search() || is_feed() || post_password_required() || ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) ) { return; }
	if ( ! ( is_front_page() || is_home() || is_page() || is_singular( array( 'product', 'post' ) ) || is_post_type_archive( 'product' ) || is_tax() || is_category() ) ) { return; }
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url() || ( WC()->session && WC()->session->has_session() ) ) ) { return; }
	$revision = voiceweb_cache_revision(); $expires = voiceweb_cache_expiry();
	if ( $expires <= time() ) { return; }
	header( 'X-VoiceWeb-Cache: MISS' );
	header( 'Cache-Control: no-cache, max-age=0, must-revalidate' );
	ob_start( static function ( string $html ) use ( $request, $revision, $expires ): string {
		if ( http_response_code() !== 200 || ! empty( $GLOBALS['voiceweb_cache_dirty'] ) || time() >= $expires || strpos( $html, '</html>' ) === false || strlen( $html ) > 2 * 1024 * 1024 ) { return $html; }
		$headers = array();
		foreach ( headers_list() as $header ) {
			if ( stripos( $header, 'Set-Cookie:' ) === 0 || preg_match( '/^Cache-Control:.*(?:no-store|private)/i', $header ) ) { return $html; }
			if ( preg_match( '/^(Content-Type|Content-Language|X-Robots-Tag|Link|X-Content-Type-Options|Referrer-Policy|Permissions-Policy|Strict-Transport-Security):/i', $header ) ) { $headers[] = $header; }
		}
		$directory = voiceweb_cache_directory();
		if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) { return $html; }
		$file = $directory . '/' . hash( 'sha256', $revision . '|' . $request['url'] . '|' . $request['language'] ) . '.php';
		$payload = '<?php http_response_code(404); exit; ?>' . "\n" . wp_json_encode( array( 'expires' => $expires, 'created' => time(), 'headers' => $headers, 'html' => $html ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		$temp = tempnam( $directory, 'build-' );
		if ( $temp && file_put_contents( $temp, $payload, LOCK_EX ) !== false ) { @rename( $temp, $file ); }
		if ( $temp && is_file( $temp ) ) { @unlink( $temp ); }
		return $html;
	} );
}
add_action( 'template_redirect', 'voiceweb_cache_capture', 50 );

/** Content-version media as well as scripts; a replaced file gets a fresh browser-cache URL. */
function voiceweb_cache_asset_url( string $url ): string {
	$parts = wp_parse_url( $url ); $home = wp_parse_url( (string) get_option( 'home' ) );
	if ( ! empty( $parts['host'] ) && $parts['host'] !== ( $home['host'] ?? '' ) ) { return $url; }
	$path = rawurldecode( $parts['path'] ?? '' );
	if ( ! preg_match( '~/(wp-content/(?:themes|plugins|uploads)/|wp-includes/(?:js|css)/)~', $path ) || ! preg_match( '/\.(css|m?js|woff2?|ttf|otf|eot|svg|webp|png|jpe?g|avif|gif|ico)$/i', $path ) || strpos( $path, '..' ) !== false ) { return $url; }
	$base = wp_parse_url( (string) get_option( 'home' ), PHP_URL_PATH ) ?: '';
	$file = ABSPATH . ltrim( substr( $path, strlen( rtrim( $base, '/' ) ) ), '/' );
	return is_file( $file ) ? add_query_arg( 'ver', (string) filemtime( $file ), $url ) : $url;
}
add_filter( 'script_loader_src', 'voiceweb_cache_asset_url', 100 );
add_filter( 'style_loader_src', 'voiceweb_cache_asset_url', 100 );
add_filter( 'theme_file_uri', 'voiceweb_cache_asset_url', 100 );
add_filter( 'wp_get_attachment_image_src', static function ( $image ) { if ( is_array( $image ) ) { $image[0] = voiceweb_cache_asset_url( $image[0] ); } return $image; } );
add_filter( 'wp_calculate_image_srcset', static function ( $sources ) { if ( is_array( $sources ) ) { foreach ( $sources as &$source ) { $source['url'] = voiceweb_cache_asset_url( $source['url'] ); } } return $sources; } );

add_action( 'admin_bar_menu', static function ( $bar ) {
	if ( current_user_can( 'manage_options' ) ) { $bar->add_node( array( 'id' => 'voiceweb-cache-purge', 'title' => 'پاک‌سازی کش فروشگاه', 'href' => wp_nonce_url( admin_url( 'admin-post.php?action=voiceweb_cache_purge' ), 'voiceweb_cache_purge' ) ) ); }
}, 100 );
add_action( 'admin_post_voiceweb_cache_purge', static function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی مجاز نیست.' ); }
	check_admin_referer( 'voiceweb_cache_purge' ); voiceweb_cache_purge();
	wp_safe_redirect( wp_get_referer() ?: admin_url() ); exit;
} );

// Run before ordinary plugins and the theme; removing this single file restores normal WordPress delivery.
$GLOBALS['voiceweb_cache_request'] = voiceweb_cache_request();
if ( $GLOBALS['voiceweb_cache_request'] ) {
	$cache_request = $GLOBALS['voiceweb_cache_request'];
	$cache_file = voiceweb_cache_directory() . '/' . hash( 'sha256', voiceweb_cache_revision() . '|' . $cache_request['url'] . '|' . $cache_request['language'] ) . '.php';
	if ( is_file( $cache_file ) ) {
		$cache_raw = file_get_contents( $cache_file );
		$cache_data = json_decode( substr( $cache_raw, strpos( $cache_raw, "\n" ) + 1 ), true );
		if ( is_array( $cache_data ) && ( $cache_data['expires'] ?? 0 ) > time() && ! empty( $cache_data['html'] ) ) {
			foreach ( $cache_data['headers'] as $cache_header ) { header( $cache_header, false ); }
			header( 'X-VoiceWeb-Cache: HIT' ); header( 'Cache-Control: no-cache, max-age=0, must-revalidate' );
			header( 'Age: ' . max( 0, time() - $cache_data['created'] ) );
			if ( $_SERVER['REQUEST_METHOD'] !== 'HEAD' ) {
				$html = $cache_data['html'];
				// Release an image preload early, before compression buffers the rest of a long page.
				// The response bytes and markup remain exactly the same as the uncached page.
				if ( preg_match( '~<link\b[^>]*\brel=(["\x27])preload\1[^>]*\bas=(["\x27])image\2[^>]*>~i', $html, $preload, PREG_OFFSET_CAPTURE ) ) {
					$split = $preload[0][1] + strlen( $preload[0][0] );
					echo substr( $html, 0, $split );
					$buffer = ob_get_status();
					if ( $buffer && ( $buffer['flags'] & PHP_OUTPUT_HANDLER_FLUSHABLE ) ) { ob_flush(); }
					flush(); echo substr( $html, $split );
				} else { echo $html; }
			}
			exit;
		}
	}
}
