<?php
/** Editorial landing content belongs to native WooCommerce category archives. */
defined( 'ABSPATH' ) || exit;

function voiceweb_category_value( string $name, $fallback = '', int $term_id = 0 ) {
	$term_id = $term_id ?: ( is_tax( 'product_cat' ) ? (int) get_queried_object_id() : 0 );
	if ( ! $term_id ) { return $fallback; }
	$key = 'vw_cat_' . sanitize_key( $name );
	$value = function_exists( 'get_field' ) ? get_field( $key, 'product_cat_' . $term_id ) : null;
	if ( $value === null || $value === false || $value === '' ) {
		$value = metadata_exists( 'term', $term_id, $key ) ? get_term_meta( $term_id, $key, true ) : null;
	}
	$repeaters = array( 'tips' => array( 'title', 'text' ), 'faq' => array( 'question', 'answer' ) );
	if ( isset( $repeaters[ $name ] ) && ! is_array( $value ) ) {
		$rows = array(); $count = is_numeric( $value ) ? max( 0, min( 8, (int) $value ) ) : 0;
		for ( $i = 0; $i < $count; $i++ ) {
			$row = array();
			foreach ( $repeaters[ $name ] as $field ) { $raw = get_term_meta( $term_id, $key . '_' . $i . '_' . $field, true ); $row[ $field ] = is_scalar( $raw ) ? (string) $raw : ''; }
			$rows[] = $row;
		}
		return $rows ?: $fallback;
	}
	return $value === null || $value === '' ? $fallback : $value;
}

function voiceweb_category_ids( $values ): array {
	$ids = array();
	foreach ( is_array( $values ) ? $values : array( $values ) as $value ) {
		if ( $value instanceof WP_Term ) { $value = $value->term_id; }
		elseif ( $value instanceof WP_Post ) { $value = $value->ID; }
		if ( is_numeric( $value ) && (int) $value > 0 ) { $ids[] = (int) $value; }
	}
	return array_slice( array_values( array_unique( $ids ) ), 0, 8 );
}

/** Keep native Woo description formatting while allowing term-field editorial changes. */
function voiceweb_category_archive_intro( string $description, $term ): string {
	if ( ! $term instanceof WP_Term || $term->taxonomy !== 'product_cat' ) { return $description; }
	$intro = voiceweb_category_value( 'intro', $description, $term->term_id );
	return is_string( $intro ) ? $intro : $description;
}
add_filter( 'woocommerce_taxonomy_archive_description_raw', 'voiceweb_category_archive_intro', 20, 2 );

function voiceweb_category_has_filters(): bool {
	$params = function_exists( 'voiceweb_wc_catalog_params' ) ? voiceweb_wc_catalog_params() : array();
	unset( $params['lang'] );
	return (bool) $params;
}

/** Only the first, unfiltered collection shows the long editorial article. */
function voiceweb_category_show_guide(): bool {
	if ( ! is_tax( 'product_cat' ) || is_paged() || (int) get_query_var( 'paged' ) > 1 ) { return false; }
	return ! voiceweb_category_has_filters();
}

function voiceweb_category_related_terms( int $term_id = 0 ): array {
	$term_id = $term_id ?: (int) get_queried_object_id(); $terms = array();
	foreach ( voiceweb_category_ids( voiceweb_category_value( 'related_categories', array(), $term_id ) ) as $id ) {
		if ( $id === $term_id ) { continue; }
		$term = get_term( $id, 'product_cat' );
		if ( $term instanceof WP_Term && ! is_wp_error( get_term_link( $term ) ) ) { $terms[] = $term; }
	}
	return $terms;
}

function voiceweb_category_related_gifts( int $term_id = 0 ): array {
	$ids = voiceweb_category_ids( voiceweb_category_value( 'related_gifts', array(), $term_id ) );
	if ( ! $ids ) { return array(); }
	$query = new WP_Query( array( 'post_type' => 'page', 'post_status' => 'publish', 'has_password' => false, 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => 8, 'no_found_rows' => true, 'suppress_filters' => false, 'meta_key' => '_wp_page_template', 'meta_value' => 'page-templates/gift-landing.php' ) );
	return array_values( array_filter( $query->posts, static fn( $post ) => $post instanceof WP_Post && $post->post_status === 'publish' && $post->post_password === '' ) );
}

function voiceweb_category_assets( array $keys ): array {
	if ( is_tax( 'product_cat' ) ) { $keys[] = 'category-guide'; }
	return array_values( array_unique( $keys ) );
}
add_filter( 'cn/page-keys', 'voiceweb_category_assets', 26 );

function voiceweb_category_seo_context(): bool {
	return ! is_admin() && is_tax( 'product_cat' ) && get_queried_object() instanceof WP_Term
		&& ! ( function_exists( 'voiceweb_gift_has_seo_plugin' ) && voiceweb_gift_has_seo_plugin() );
}

/** Share archive pagination rules without carrying facet parameters into canonicals. */
function voiceweb_catalog_page_canonical( string $url ): string {
	$page = voiceweb_category_has_filters() ? 1 : max( 1, (int) get_query_var( 'paged' ) );
	if ( $page > 1 ) {
		global $wp_rewrite;
		if ( $wp_rewrite->using_permalinks() ) {
			// Language plugins may put a query string on the base archive URL.
			$parts = explode( '?', $url, 2 );
			$url = trailingslashit( $parts[0] ) . user_trailingslashit( $wp_rewrite->pagination_base . '/' . $page, 'paged' );
			if ( isset( $parts[1] ) ) { $url .= '?' . $parts[1]; }
		} else { $url = add_query_arg( 'paged', $page, $url ); }
	}
	return $url;
}

/** Canonicalise filter/sort variants to the collection; real pagination keeps its own URL. */
function voiceweb_category_canonical(): string {
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || $term->taxonomy !== 'product_cat' ) { return ''; }
	$url = get_term_link( $term );
	return is_wp_error( $url ) ? '' : voiceweb_catalog_page_canonical( $url );
}

/** The native shop is a product archive, so WordPress does not normally emit a canonical. */
function voiceweb_shop_seo_context(): bool {
	return ! is_admin() && function_exists( 'is_shop' ) && is_shop()
		&& ! ( function_exists( 'voiceweb_gift_has_seo_plugin' ) && voiceweb_gift_has_seo_plugin() );
}

function voiceweb_shop_seo_head(): void {
	if ( ! voiceweb_shop_seo_context() ) { return; }
	$url = voiceweb_catalog_page_canonical( wc_get_page_permalink( 'shop' ) );
	if ( $url === '' ) { return; }
	// Also avoid two canonicals if another integration represents the shop as a singular page.
	remove_action( 'wp_head', 'rel_canonical' );
	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
}
add_action( 'wp_head', 'voiceweb_shop_seo_head', 9 );

function voiceweb_shop_robots( array $robots ): array {
	if ( voiceweb_shop_seo_context() && voiceweb_category_has_filters() ) { unset( $robots['index'] ); $robots['noindex'] = true; }
	return $robots;
}
add_filter( 'wp_robots', 'voiceweb_shop_robots', 20 );

function voiceweb_category_seo_title( string $title ): string {
	if ( ! voiceweb_category_seo_context() ) { return $title; }
	$custom = voiceweb_category_value( 'seo_title' );
	if ( ! is_string( $custom ) || trim( $custom ) === '' ) { return $title; }
	$title = trim( wp_strip_all_tags( $custom ) );
	$page = max( 1, (int) get_query_var( 'paged' ) );
	return $page > 1 ? $title . ' | صفحهٔ ' . number_format_i18n( $page ) : $title;
}
add_filter( 'pre_get_document_title', 'voiceweb_category_seo_title', 21 );

/** Facets remain fully usable while the primary collection owns the search landing. */
function voiceweb_category_robots( array $robots ): array {
	if ( voiceweb_category_seo_context() && voiceweb_category_has_filters() ) { unset( $robots['index'] ); $robots['noindex'] = true; }
	return $robots;
}
add_filter( 'wp_robots', 'voiceweb_category_robots', 20 );

/** Core canonical is singular-only; WooCommerce already owns breadcrumb structured data. */
function voiceweb_category_seo_head(): void {
	if ( ! voiceweb_category_seo_context() ) { return; }
	$url = voiceweb_category_canonical();
	if ( $url === '' ) { return; }
	$term = get_queried_object();
	$description = voiceweb_category_value( 'seo_description', voiceweb_category_value( 'intro', $term->description ) );
	$description = is_string( $description ) ? trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $description ) ) ) ) : '';
	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	if ( $description !== '' ) { echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n"; }
	$schema = array( '@context' => 'https://schema.org', '@type' => 'CollectionPage', '@id' => $url . '#collection', 'url' => $url, 'name' => wp_strip_all_tags( $term->name ), 'inLanguage' => get_bloginfo( 'language' ) );
	if ( $description !== '' ) { $schema['description'] = $description; }
	$json = wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( $json ) { echo '<script type="application/ld+json" class="vw-category-schema">' . $json . '</script>' . "\n"; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON hex encoding protects script context.
}
add_action( 'wp_head', 'voiceweb_category_seo_head', 21 );
