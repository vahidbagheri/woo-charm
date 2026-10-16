<?php
/** Leather magazine: relevant articles, native pagination and fallback search metadata. */
defined( 'ABSPATH' ) || exit;

function voiceweb_leather_magazine_term(): int {
	$term = get_term_by( 'slug', 'leather-journal', 'category' );
	return $term instanceof WP_Term ? cn_term_id( $term->term_id, 'category' ) : 0;
}

function voiceweb_leather_article( int $post_id = 0 ): bool {
	$term_id = voiceweb_leather_magazine_term();
	if ( ! $term_id ) { return false; }
	$children = get_term_children( $term_id, 'category' );
	$terms = array_merge( array( $term_id ), is_wp_error( $children ) ? array() : $children );
	return has_category( $terms, $post_id ?: (int) get_queried_object_id() );
}

/** The main posts query owns totals and pagination; do not alter other archives or legacy posts. */
function voiceweb_leather_magazine_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() || $query->is_feed() ) { return; }
	$term_id = voiceweb_leather_magazine_term();
	if ( ! $term_id ) { return; }
	$query->set( 'cat', $term_id ); // WordPress includes descendant categories.
	$query->set( 'post_type', 'post' );
	$query->set( 'post_status', 'publish' );
	$query->set( 'has_password', false );
	$query->set( 'ignore_sticky_posts', true );
	$query->set( 'orderby', array( 'date' => 'DESC', 'ID' => 'DESC' ) );
}
add_action( 'pre_get_posts', 'voiceweb_leather_magazine_query', 25 );

function voiceweb_leather_magazine_url( int $page = 1 ): string {
	$blog_id = cn_term_id( (int) get_option( 'page_for_posts' ), 'page' );
	$url = $blog_id ? get_permalink( $blog_id ) : home_url( '/' );
	return voiceweb_leather_magazine_paginated_url( $url, $page );
}

/** Preserve language parameters supplied by a multilingual permalink filter. */
function voiceweb_leather_magazine_paginated_url( string $url, int $page ): string {
	if ( $page < 2 ) { return $url; }
	global $wp_rewrite;
	if ( ! $wp_rewrite->using_permalinks() ) { return add_query_arg( 'paged', $page, $url ); }
	$parts = explode( '?', $url, 2 );
	return trailingslashit( $parts[0] ) . user_trailingslashit( $wp_rewrite->pagination_base . '/' . $page, 'paged' ) . ( isset( $parts[1] ) ? '?' . $parts[1] : '' );
}

/** The journal category is an alias of the magazine; unrelated/child categories retain their URLs. */
function voiceweb_leather_magazine_category_destination( int $term_id, int $page = 1 ): string {
	$journal_id = voiceweb_leather_magazine_term();
	return $journal_id && $term_id === $journal_id ? voiceweb_leather_magazine_url( max( 1, $page ) ) : '';
}

function voiceweb_leather_magazine_category_link( string $url, int $term_id ): string {
	return voiceweb_leather_magazine_category_destination( $term_id ) ?: $url;
}
add_filter( 'category_link', 'voiceweb_leather_magazine_category_link', 25, 2 );

/** Capture the exact archive before WordPress clears taxonomy flags for an empty paginated URL. */
function voiceweb_leather_magazine_capture_category( $preempt, WP_Query $query ) {
	$journal_id = voiceweb_leather_magazine_term();
	if ( ! is_admin() && ! $query->is_feed() && ! $query->is_preview() && $query->is_main_query() && $journal_id && $query->is_category( $journal_id ) ) {
		$query->set( '_voiceweb_journal_alias', $journal_id );
	}
	return $preempt;
}
add_filter( 'pre_handle_404', 'voiceweb_leather_magazine_capture_category', 5, 2 );

function voiceweb_leather_magazine_redirect_category(): void {
	if ( is_admin() || is_feed() || is_preview() ) { return; }
	$term_id = is_category() ? (int) get_queried_object_id() : ( is_404() ? (int) get_query_var( '_voiceweb_journal_alias' ) : 0 );
	$destination = voiceweb_leather_magazine_category_destination( $term_id, (int) get_query_var( 'paged' ) );
	if ( $destination ) { wp_safe_redirect( $destination, 301, 'Avan Charm magazine' ); exit; }
}
add_action( 'template_redirect', 'voiceweb_leather_magazine_redirect_category', 5 );

function voiceweb_leather_magazine_seo_context(): bool {
	return ! is_admin() && is_home() && voiceweb_leather_magazine_term() > 0 && ! voiceweb_gift_has_seo_plugin();
}

function voiceweb_leather_magazine_title( string $title ): string {
	if ( ! voiceweb_leather_magazine_seo_context() ) { return $title; }
	$title = 'مجلهٔ آوان چرم | راهنمای خرید و نگهداری چرم طبیعی';
	$page = max( 1, (int) get_query_var( 'paged' ) );
	return $page > 1 ? $title . ' | صفحهٔ ' . cn_localize_digits( $page ) : $title;
}
add_filter( 'pre_get_document_title', 'voiceweb_leather_magazine_title', 26 );

function voiceweb_leather_magazine_seo_head(): void {
	if ( ! voiceweb_leather_magazine_seo_context() ) { return; }
	$page = max( 1, (int) get_query_var( 'paged' ) );
	$url = voiceweb_leather_magazine_url( $page );
	$description = 'راهنمای انتخاب کیف و کیف پول چرم طبیعی، نگهداری از چرم و ایده‌های هدیه در مجلهٔ آوان چرم؛ نکته‌های کاربردی برای انتخاب بر اساس نیاز و استفادهٔ روزانه.';
	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	// Core emits a canonical only for singular pages; this is the native posts index.
	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	$graph = array(
		array( '@type' => 'Blog', '@id' => $url . '#magazine', 'url' => $url, 'name' => 'مجلهٔ آوان چرم', 'description' => $description, 'inLanguage' => get_bloginfo( 'language' ) ),
		array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => array(
			array( '@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => home_url( '/' ) ),
			array( '@type' => 'ListItem', 'position' => 2, 'name' => 'مجلهٔ آوان چرم', 'item' => $url ),
		) ),
	);
	echo '<script type="application/ld+json" class="vw-magazine-schema">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'voiceweb_leather_magazine_seo_head', 26 );

function voiceweb_leather_magazine_assets( array $keys ): array {
	if ( ( is_home() && voiceweb_leather_magazine_term() ) || ( is_singular( 'post' ) && voiceweb_leather_article() ) ) { $keys[] = 'leather-magazine'; }
	return array_values( array_unique( $keys ) );
}
add_filter( 'cn/page-keys', 'voiceweb_leather_magazine_assets', 31 );

/** Useful routes from an article to existing collections and occasion guides. */
function voiceweb_leather_discovery_links(): array {
	$links = array();
	foreach ( array( 'leather-men-bags', 'leather-women-bags', 'leather-men-wallets', 'leather-cardholders' ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term instanceof WP_Term ) { continue; }
		$url = get_term_link( $term );
		if ( ! is_wp_error( $url ) ) { $links[] = array( 'url' => $url, 'label' => $term->name ); }
	}
	if ( function_exists( 'wc_get_products' ) ) {
		$products = wc_get_products( array( 'status' => 'publish', 'visibility' => 'visible', 'has_password' => false, 'limit' => 1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
		foreach ( $products as $product ) {
			if ( $product instanceof WC_Product && $product->is_visible() && get_post_field( 'post_password', $product->get_id() ) === '' ) { $links[] = array( 'url' => $product->get_permalink(), 'label' => 'مشاهدهٔ ' . $product->get_name() ); }
		}
	}
	$hub_id = voiceweb_gift_hub_id();
	if ( $hub_id ) { $links[] = array( 'url' => get_permalink( $hub_id ), 'label' => 'هدیه برای مناسبت‌ها' ); }
	return $links;
}

function voiceweb_leather_discovery(): void {
	$links = voiceweb_leather_discovery_links();
	if ( ! $links ) { return; }
	echo '<nav class="vw-magazine-discovery" aria-label="انتخاب محصول و هدیه"><h2>از راهنما تا انتخاب شما</h2><p>مدل‌ها و مشخصات هر محصول را در مجموعهٔ مرتبط ببینید یا برای مناسبت پیش رو ایده بگیرید.</p><div class="vw-magazine-discovery__links">';
	foreach ( $links as $link ) { echo '<a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a>'; }
	echo '</div></nav>';
}
