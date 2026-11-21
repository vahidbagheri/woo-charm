<?php
/** Read-only local/production content and deployment checks. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
global $wpdb;
$ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_voiceweb_avan_catalog_key' ) );
$missing = array(); $intros = array(); $backups = 0;
foreach ( $ids as $id ) {
	$post = get_post( $id );
	if ( ! trim( $post->post_content ) || ! trim( $post->post_excerpt ) || ! get_post_meta( $id, '_voiceweb_launch_copy_version', true ) ) { $missing[] = $id; }
	$intros[] = $post->post_excerpt;
	if ( metadata_exists( 'post', $id, '_voiceweb_launch_copy_backup' ) ) { $backups++; }
}
$legacy = array();
foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1 ) ) as $post ) {
	if ( ! has_category( 'leather-journal', $post ) ) { $legacy[] = array( 'id' => $post->ID, 'title' => $post->post_title, 'noindex' => (bool) get_post_meta( $post->ID, 'vw_brand_noindex', true ) ); }
}
$manifest = get_option( 'voiceweb_launch_index_manifest', array() );
$terms = $manifest['terms'] ?? array();
if ( $terms ) { update_meta_cache( 'term', $terms ); }
$indexable_terms = array_values( array_filter( $terms, static fn( $id ) => ! get_term_meta( $id, 'vw_brand_noindex', true ) ) );
$result = array(
	'home' => get_option( 'home' ), 'siteurl' => get_option( 'siteurl' ), 'environment' => wp_get_environment_type(), 'public' => (bool) get_option( 'blog_public' ),
	'catalog_products' => count( $ids ), 'missing_copy' => $missing, 'unique_intros' => count( array_unique( $intros ) ), 'original_copy_backups' => $backups,
	'cache_module' => is_file( WP_CONTENT_DIR . '/mu-plugins/voiceweb-cache.php' ), 'sitemap' => home_url( '/wp-sitemap.xml' ),
	'legacy_posts_count' => count( $legacy ), 'legacy_indexable' => count( array_filter( $legacy, static fn( $post ) => ! $post['noindex'] ) ),
	'legacy_terms' => count( $terms ), 'legacy_indexable_terms' => $indexable_terms,
	'legacy_posts_sample' => in_array( '--details', $argv, true ) ? $legacy : array_slice( $legacy, 0, 3 ),
);
echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
exit( $missing || count( array_unique( $intros ) ) !== count( $ids ) ? 1 : 0 );
