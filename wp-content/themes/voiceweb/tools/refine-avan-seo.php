<?php
/** Local-only, reversible cleanup of the identified starter product and magazine label. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'DISABLE_WP_CRON', true );
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/avan-seo-cleanup' );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
$_SERVER['REQUEST_URI'] = '/';
$GLOBALS['wp_filter']['pre_http_request'][PHP_INT_MAX][] = array( 'function' => static fn() => new WP_Error( 'offline', 'External requests disabled.' ), 'accepted_args' => 3 );
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array( 'function' => static fn() => false, 'accepted_args' => 2 );
require dirname( __DIR__, 4 ) . '/wp-load.php';
$apply = in_array( '--apply', $argv, true );
$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
if ( ! preg_match( '/(?:\.local|\.test)$|^localhost$|^127\.0\.0\.1$/i', $host ) || ! get_option( 'voiceweb_avan_brand_version' ) ) { fwrite( STDERR, "An initialized local Avan Charm site is required.\n" ); exit( 1 ); }
$demos = array();
foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'title' => 'تست', 'numberposts' => 20, 'suppress_filters' => true ) ) as $post ) {
	if ( trim( $post->post_title ) !== 'تست' || trim( wp_strip_all_tags( $post->post_content ) ) !== 'تستی' ) { continue; }
	$product = wc_get_product( $post->ID );
	if ( $product ) { $demos[ $post->ID ] = $product; }
}
$blog_id = (int) get_option( 'page_for_posts' );
$menu_updates = array();
if ( $blog_id ) {
	foreach ( wp_get_nav_menus() as $menu ) {
		foreach ( wp_get_nav_menu_items( $menu->term_id ) ?: array() as $item ) {
			if ( strpos( (string) wp_parse_url( $item->url, PHP_URL_PATH ), '/category/leather-journal/' ) !== false ) { $menu_updates[ $item->ID ] = array( 'menu' => $menu->term_id, 'item' => $item ); }
		}
	}
}
$front_id = (int) get_option( 'page_on_front' );
$front_blog_link = $front_id ? voiceweb_front_value( 'blog_link', array() ) : array();
$update_front_blog = $blog_id && is_array( $front_blog_link ) && strpos( (string) wp_parse_url( $front_blog_link['url'] ?? '', PHP_URL_PATH ), '/category/leather-journal/' ) !== false;
$summary = array( 'mode' => $apply ? 'applied' : 'dry-run', 'sample_products' => array_keys( $demos ), 'magazine' => $blog_id ? get_permalink( $blog_id ) : '', 'magazine_title' => $blog_id ? get_the_title( $blog_id ) : '', 'magazine_menu_links' => count( $menu_updates ), 'homepage_magazine_link' => $update_front_blog );
if ( ! $apply ) { echo wp_json_encode( $summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n"; exit; }
$backup = get_option( 'voiceweb_avan_seo_cleanup_backup_v1', array( 'products' => array(), 'magazine' => array() ) );
foreach ( $demos as $id => $product ) {
	if ( ! isset( $backup['products'][ $id ] ) ) { $backup['products'][ $id ] = array( 'visibility' => $product->get_catalog_visibility(), 'noindex_exists' => metadata_exists( 'post', $id, 'vw_brand_noindex' ), 'noindex' => get_post_meta( $id, 'vw_brand_noindex', true ) ); }
}
if ( $blog_id && empty( $backup['magazine'] ) ) { $backup['magazine'] = array( 'id' => $blog_id, 'title' => get_the_title( $blog_id ) ); }
foreach ( $menu_updates as $id => $entry ) { if ( ! isset( $backup['menus'][ $id ] ) ) { $backup['menus'][ $id ] = array( 'menu' => $entry['menu'], 'post' => get_post( $id, ARRAY_A ), 'meta' => get_post_meta( $id ) ); } }
if ( $update_front_blog && ! isset( $backup['front_blog_link'] ) ) { $backup['front_blog_link'] = array( 'id' => $front_id, 'value' => $front_blog_link ); }
update_option( 'voiceweb_avan_seo_cleanup_backup_v1', $backup, false );
foreach ( $demos as $id => $product ) { $product->set_catalog_visibility( 'hidden' ); $product->save(); update_field( 'field_vw_brand_noindex', 1, $id ); }
if ( $blog_id ) { wp_update_post( wp_slash( array( 'ID' => $blog_id, 'post_title' => 'مجلهٔ آوان چرم' ) ) ); }
foreach ( $menu_updates as $id => $entry ) {
	$item = $entry['item'];
	$result = wp_update_nav_menu_item( $entry['menu'], $id, array( 'menu-item-title' => 'مجلهٔ آوان چرم', 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $blog_id, 'menu-item-status' => 'publish', 'menu-item-position' => $item->menu_order, 'menu-item-parent-id' => $item->menu_item_parent ) );
	if ( is_wp_error( $result ) ) { fwrite( STDERR, $result->get_error_message() . "\n" ); exit( 1 ); }
}
if ( $update_front_blog ) { $front_blog_link['url'] = get_permalink( $blog_id ); update_field( 'field_vw_front_blog_link', $front_blog_link, $front_id ); }
$summary['magazine_title'] = $blog_id ? get_the_title( $blog_id ) : '';
$summary['demo_visibility'] = 'hidden; data retained';
echo wp_json_encode( $summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
