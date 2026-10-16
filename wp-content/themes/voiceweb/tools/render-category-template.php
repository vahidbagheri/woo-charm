<?php
/** Render one native category template in CLI, independently of public coming-soon routing. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
$host = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
$slug = $argv[1] ?? ''; $sort = $argv[2] ?? '';
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.local)$/i', $host ) || ! in_array( $slug, array( 'leather-men-bags', 'leather-women-bags', 'leather-men-wallets', 'leather-women-wallets', 'leather-cardholders', 'leather-accessories' ), true ) || ! in_array( $sort, array( '', 'price' ), true ) ) { fwrite( STDERR, "Only known local category render fixtures are supported.\n" ); exit( 1 ); }
define( 'DISABLE_WP_CRON', true ); define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-category-render' );
$_SERVER['HTTP_HOST'] = $host; $_SERVER['SERVER_NAME'] = $host; $_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_METHOD'] = 'GET'; $_SERVER['PHP_SELF'] = '/index.php'; $_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/product-category/' . $slug . '/' . ( $sort ? '?orderby=' . $sort : '' );
$_SERVER['QUERY_STRING'] = $sort ? 'orderby=' . $sort : '';
$_GET = $sort ? array( 'orderby' => $sort ) : array();
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array( 'function' => static fn() => false, 'accepted_args' => 2 );
$GLOBALS['wp_filter']['pre_http_request'][PHP_INT_MAX][] = array( 'function' => static fn() => new WP_Error( 'test_no_network', 'Network blocked.' ), 'accepted_args' => 3 );
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! preg_match( '/^(?:localhost|127\.0\.0\.1|[a-z0-9.-]+\.local)$/i', (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) { fwrite( STDERR, "Configured site is not local.\n" ); exit( 1 ); }
$visibility = array( get_option( 'woocommerce_coming_soon' ), get_option( 'woocommerce_store_pages_only' ), get_option( 'blog_public' ) );
wp( array( 'product_cat' => $slug ) );
if ( ! is_tax( 'product_cat', $slug ) || is_404() ) { fwrite( STDERR, "Native category query did not resolve.\n" ); exit( 1 ); }
// Deliberately render this theme's file directly, not the public template router.
// No visibility filters, account changes or persistent option writes are used.
include get_template_directory() . '/woocommerce.php';
if ( $visibility !== array( get_option( 'woocommerce_coming_soon' ), get_option( 'woocommerce_store_pages_only' ), get_option( 'blog_public' ) ) ) { fwrite( STDERR, "Visibility settings changed unexpectedly.\n" ); exit( 1 ); }
