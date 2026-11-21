<?php
/** Backed-up editorial exclusions for the existing travel archive; no content is deleted. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local'; $_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
$journal = get_term_by( 'slug', 'leather-journal', 'category' );
if ( ! $journal ) { exit( "Leather journal must exist before excluding the old archive.\n" ); }
$apply = in_array( '--apply', $argv, true ); $restore = in_array( '--restore', $argv, true );
$excluded = array(); $excluded_terms = array();
$manifest = get_option( 'voiceweb_launch_index_manifest', array() );
$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => true ) );
if ( ! $manifest ) {
	$manifest = array( 'posts' => array(), 'terms' => array() );
	foreach ( $posts as $post ) { if ( ! has_category( $journal->term_id, $post ) ) { $manifest['posts'][] = $post->ID; } }
}
if ( empty( $manifest['all_languages'] ) && $manifest['posts'] ) {
	global $wpdb;
	$ids = implode( ',', array_map( 'absint', $manifest['posts'] ) );
	$protected = implode( ',', array_map( 'absint', array_merge( array( $journal->term_id ), get_ancestors( $journal->term_id, 'category' ) ) ) );
	// Query the exact manifest in every WPML language, without changing the site's language.
	$manifest['terms'] = array_map( 'intval', $wpdb->get_col( "SELECT DISTINCT t.term_id FROM {$wpdb->term_taxonomy} t INNER JOIN {$wpdb->term_relationships} r ON r.term_taxonomy_id=t.term_taxonomy_id WHERE t.taxonomy IN ('category','post_tag') AND t.term_id NOT IN ({$protected}) AND r.object_id IN ({$ids}) AND NOT EXISTS (SELECT 1 FROM {$wpdb->term_relationships} other INNER JOIN {$wpdb->posts} p ON p.ID=other.object_id WHERE other.term_taxonomy_id=t.term_taxonomy_id AND p.post_type='post' AND p.post_status='publish' AND p.ID NOT IN ({$ids})) ORDER BY t.term_id" ) );
	$manifest['all_languages'] = true;
}
if ( $apply ) { update_option( 'voiceweb_launch_index_manifest', $manifest, false ); }
foreach ( $posts as $post ) {
	if ( ! in_array( $post->ID, $manifest['posts'], true ) ) { continue; }
	$backup = get_post_meta( $post->ID, '_voiceweb_launch_index_backup', true );
	if ( $restore ) {
		if ( is_array( $backup ) ) {
			if ( $backup['exists'] ) { update_post_meta( $post->ID, 'vw_brand_noindex', $backup['value'] ); } else { delete_post_meta( $post->ID, 'vw_brand_noindex' ); }
			delete_post_meta( $post->ID, '_voiceweb_launch_index_backup' );
		}
	} elseif ( $apply ) {
		if ( ! is_array( $backup ) ) { update_post_meta( $post->ID, '_voiceweb_launch_index_backup', array( 'exists' => metadata_exists( 'post', $post->ID, 'vw_brand_noindex' ), 'value' => get_post_meta( $post->ID, 'vw_brand_noindex', true ) ) ); }
		update_post_meta( $post->ID, 'vw_brand_noindex', '1' );
	}
	$excluded[] = $post->ID;
}
foreach ( $manifest['terms'] as $id ) {
	$backup = get_term_meta( $id, '_voiceweb_launch_index_backup', true );
	if ( $restore && is_array( $backup ) ) {
		if ( $backup['exists'] ) { update_term_meta( $id, 'vw_brand_noindex', $backup['value'] ); } else { delete_term_meta( $id, 'vw_brand_noindex' ); }
		delete_term_meta( $id, '_voiceweb_launch_index_backup' );
	} elseif ( $apply ) {
		if ( ! is_array( $backup ) ) { update_term_meta( $id, '_voiceweb_launch_index_backup', array( 'exists' => metadata_exists( 'term', $id, 'vw_brand_noindex' ), 'value' => get_term_meta( $id, 'vw_brand_noindex', true ) ) ); }
		update_term_meta( $id, 'vw_brand_noindex', '1' );
	}
	$excluded_terms[] = $id;
}
echo wp_json_encode( array( 'mode' => $restore ? 'restore' : ( $apply ? 'apply' : 'preview' ), 'legacy_posts' => count( $excluded ), 'legacy_terms' => count( $excluded_terms ), 'ids' => $excluded ) ) . PHP_EOL;
