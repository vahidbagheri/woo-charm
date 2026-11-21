<?php
/** Local HTTPS / production-domain preparation with backups and native serialized-data handling. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local'; $_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
$old = untrailingslashit( (string) get_option( 'home' ) );
$local = in_array( '--https-local', $argv, true );
$production = in_array( '--production', $argv, true );
$apply = in_array( '--apply', $argv, true );
if ( ! $local && ! $production ) { fwrite( STDERR, "Choose --https-local or --production; add --apply to write.\n" ); exit( 1 ); }
if ( $production && wp_get_environment_type() !== 'production' ) { fwrite( STDERR, "Production migration is allowed only on a production installation.\n" ); exit( 1 ); }
if ( $local && ! str_ends_with( (string) wp_parse_url( $old, PHP_URL_HOST ), '.local' ) ) { fwrite( STDERR, "Local HTTPS mode requires the existing .local site.\n" ); exit( 1 ); }
$new = $production ? 'https://avancharm.ir' : preg_replace( '~^http://~', 'https://', $old );
$result = array( 'from' => $old, 'to' => $new, 'indexable' => true, 'mode' => $apply ? 'apply' : 'preview', 'posts' => 0, 'meta' => 0, 'options' => 0 );
function vw_launch_replace( $value, string $old, string $new ) {
	if ( is_string( $value ) ) { return str_replace( $old, $new, $value ); }
	if ( is_array( $value ) ) { foreach ( $value as &$part ) { $part = vw_launch_replace( $part, $old, $new ); } }
	return $value;
}
if ( $apply ) {
	if ( ! get_option( 'voiceweb_launch_settings_backup' ) ) { update_option( 'voiceweb_launch_settings_backup', array( 'home' => get_option( 'home' ), 'siteurl' => get_option( 'siteurl' ), 'blog_public' => get_option( 'blog_public' ) ), false ); }
	global $wpdb;
	if ( $old !== $new ) {
		$posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID,post_content,post_excerpt FROM {$wpdb->posts} WHERE post_type IN ('page','post','product','attachment','nav_menu_item') AND (post_content LIKE %s OR post_excerpt LIKE %s)", '%' . $wpdb->esc_like( $old ) . '%', '%' . $wpdb->esc_like( $old ) . '%' ) );
		foreach ( $posts as $post ) { wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( str_replace( $old, $new, $post->post_content ) ), 'post_excerpt' => wp_slash( str_replace( $old, $new, $post->post_excerpt ) ) ) ); $result['posts']++; }
		$meta = $wpdb->get_results( $wpdb->prepare( "SELECT m.meta_id,m.post_id,m.meta_key,m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE p.post_type IN ('page','post','product','attachment','nav_menu_item') AND m.meta_value LIKE %s AND m.meta_key NOT LIKE '_voiceweb_%%backup%%'", '%' . $wpdb->esc_like( $old ) . '%' ) );
		foreach ( $meta as $row ) { update_metadata_by_mid( 'post', $row->meta_id, vw_launch_replace( maybe_unserialize( $row->meta_value ), $old, $new ) ); $result['meta']++; }
		$options = $wpdb->get_results( $wpdb->prepare( "SELECT option_name,option_value FROM {$wpdb->options} WHERE (option_name LIKE 'options_%%' OR option_name LIKE 'theme_mods_%%') AND option_value LIKE %s", '%' . $wpdb->esc_like( $old ) . '%' ) );
		foreach ( $options as $option ) { update_option( $option->option_name, vw_launch_replace( maybe_unserialize( $option->option_value ), $old, $new ) ); $result['options']++; }
	}
	update_option( 'home', $new ); update_option( 'siteurl', $new ); update_option( 'blog_public', '1' );
	if ( function_exists( 'voiceweb_cache_purge' ) ) { voiceweb_cache_purge(); }
}
echo wp_json_encode( $result, JSON_UNESCAPED_SLASHES ) . PHP_EOL;
