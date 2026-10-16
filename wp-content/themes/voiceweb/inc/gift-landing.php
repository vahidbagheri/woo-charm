<?php
/** Editable occasion collections; products and inventory remain owned by WooCommerce. */
defined( 'ABSPATH' ) || exit;

/** Read this page's fields, including saved ACF data when ACF is unavailable. */
function voiceweb_gift_value( string $name, $fallback = '', int $post_id = 0 ) {
	$post_id = $post_id ?: (int) get_queried_object_id();
	if ( ! $post_id ) { return $fallback; }
	$key = 'vw_gift_' . sanitize_key( $name );
	$value = function_exists( 'get_field' ) ? get_field( $key, $post_id ) : null;
	if ( $value === null || $value === false || $value === '' ) {
		$value = metadata_exists( 'post', $post_id, $key ) ? get_post_meta( $post_id, $key, true ) : null;
	}
	$repeaters = array( 'tips' => array( 'title', 'text' ), 'faq' => array( 'question', 'answer' ) );
	if ( isset( $repeaters[ $name ] ) && ! is_array( $value ) ) {
		$rows = array();
		$count = is_numeric( $value ) ? min( $name === 'tips' ? 6 : 8, max( 0, (int) $value ) ) : 0;
		for ( $index = 0; $index < $count; $index++ ) {
			$row = array();
			foreach ( $repeaters[ $name ] as $subfield ) {
				$raw = get_post_meta( $post_id, $key . '_' . $index . '_' . $subfield, true );
				$row[ $subfield ] = is_scalar( $raw ) ? (string) $raw : '';
			}
			$rows[] = $row;
		}
		return $rows ?: $fallback;
	}
	return $value === null || $value === '' ? $fallback : $value;
}

/** Normalize relationship/taxonomy IDs without coercing objects or malformed data. */
function voiceweb_gift_ids( $values, int $limit = 24 ): array {
	$ids = array();
	foreach ( is_array( $values ) ? $values : array( $values ) as $value ) {
		if ( $value instanceof WP_Post ) { $value = $value->ID; }
		elseif ( $value instanceof WP_Term ) { $value = $value->term_id; }
		if ( ! is_numeric( $value ) || (int) $value <= 0 ) { continue; }
		$ids[] = (int) $value;
	}
	return array_slice( array_values( array_unique( $ids ) ), 0, $limit );
}

/** Selected, existing product categories in editor order. */
function voiceweb_gift_categories( int $post_id = 0 ): array {
	if ( ! taxonomy_exists( 'product_cat' ) ) { return array(); }
	$categories = array();
	foreach ( voiceweb_gift_ids( voiceweb_gift_value( 'categories', array(), $post_id ) ) as $id ) {
		$term = get_term( $id, 'product_cat' );
		if ( $term instanceof WP_Term && ! is_wp_error( get_term_link( $term ) ) ) { $categories[] = $term; }
	}
	return $categories;
}

/** Query only explicitly selected categories or products, never the whole catalogue. */
function voiceweb_gift_products( int $post_id = 0 ): array {
	if ( ! function_exists( 'wc_get_products' ) || ! class_exists( 'WC_Product' ) ) { return array(); }
	$source = voiceweb_gift_value( 'product_source', 'categories', $post_id );
	$limit = max( 1, min( 24, (int) voiceweb_gift_value( 'product_limit', 12, $post_id ) ) );
	$stock_value = voiceweb_gift_value( 'in_stock', true, $post_id );
	$stock_only = in_array( $stock_value, array( true, 1, '1', 'yes' ), true );
	$hide_out = $stock_only || get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes';
	$args = array( 'status' => 'publish', 'visibility' => 'catalog', 'has_password' => false, 'limit' => $limit, 'return' => 'objects', 'paginate' => false, 'orderby' => 'date', 'order' => 'DESC' );
	if ( $hide_out ) { $args['stock_status'] = $stock_only ? 'instock' : array( 'instock', 'onbackorder' ); }
	$ids = array();
	if ( $source === 'manual' ) {
		$ids = voiceweb_gift_ids( voiceweb_gift_value( 'products', array(), $post_id ) );
		if ( ! $ids ) { return array(); }
		$args['include'] = $ids;
		$args['orderby'] = 'include';
	} elseif ( $source === 'categories' ) {
		$slugs = array();
		foreach ( voiceweb_gift_categories( $post_id ) as $term ) {
			$slugs[] = $term->slug;
			$children = get_term_children( $term->term_id, 'product_cat' );
			if ( is_wp_error( $children ) ) { continue; }
			foreach ( $children as $child_id ) {
				$child = get_term( $child_id, 'product_cat' );
				if ( $child instanceof WP_Term ) { $slugs[] = $child->slug; }
			}
		}
		if ( ! $slugs ) { return array(); }
		$args['category'] = array_values( array_unique( $slugs ) );
		$order = voiceweb_gift_value( 'product_order', 'date', $post_id );
		if ( $order === 'price' || $order === 'popularity' ) {
			$args['meta_key'] = $order === 'price' ? '_price' : 'total_sales';
			$args['orderby'] = array( 'meta_value_num' => $order === 'price' ? 'ASC' : 'DESC', 'ID' => 'DESC' );
		} elseif ( $order === 'menu_order' ) {
			$args['orderby'] = array( 'menu_order' => 'ASC', 'title' => 'ASC' );
		}
	} else { return array(); }
	$products = array_values( array_filter( wc_get_products( $args ), static function ( $product ) use ( $hide_out ) {
		return $product instanceof WC_Product
			&& $product->get_status() === 'publish'
			&& in_array( $product->get_catalog_visibility(), array( 'visible', 'catalog' ), true )
			&& $product->is_visible()
			&& ! get_post_field( 'post_password', $product->get_id() )
			&& ( ! $hide_out || $product->is_in_stock() );
	} ) );
	if ( $ids ) {
		$positions = array_flip( $ids );
		usort( $products, static fn( $first, $second ) => ( $positions[ $first->get_id() ] ?? PHP_INT_MAX ) <=> ( $positions[ $second->get_id() ] ?? PHP_INT_MAX ) );
	}
	return array_slice( $products, 0, $limit );
}

/** Public related pages only; keep the administrator's ordering. */
function voiceweb_gift_related( int $post_id = 0 ): array {
	$post_id = $post_id ?: (int) get_queried_object_id();
	$ids = array_values( array_diff( voiceweb_gift_ids( voiceweb_gift_value( 'related_pages', array(), $post_id ), 8 ), array( $post_id ) ) );
	if ( ! $ids ) { return array(); }
	$query = new WP_Query( array( 'post_type' => 'page', 'post_status' => 'publish', 'has_password' => false, 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => 8, 'no_found_rows' => true, 'ignore_sticky_posts' => true, 'suppress_filters' => false ) );
	return array_values( array_filter( $query->posts, static fn( $page ) => $page instanceof WP_Post && $page->post_status === 'publish' && $page->post_password === '' ) );
}

/** Public landing pages, also used for the homepage's internal discovery links. */
function voiceweb_gift_pages( int $limit = 60 ): array {
	$query = new WP_Query( array( 'post_type' => 'page', 'post_status' => 'publish', 'has_password' => false, 'meta_key' => '_wp_page_template', 'meta_value' => 'page-templates/gift-landing.php', 'posts_per_page' => max( 1, min( 100, $limit ) ), 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true, 'suppress_filters' => false ) );
	return array_values( array_filter( $query->posts, static fn( $page ) => $page instanceof WP_Post && $page->post_status === 'publish' && $page->post_password === '' ) );
}

function voiceweb_gift_hub_id(): int {
	$query = new WP_Query( array( 'post_type' => 'page', 'post_status' => 'publish', 'has_password' => false, 'meta_key' => '_wp_page_template', 'meta_value' => 'page-templates/gift-hub.php', 'posts_per_page' => 1, 'orderby' => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ), 'fields' => 'ids', 'no_found_rows' => true, 'suppress_filters' => false ) );
	return $query->posts ? (int) $query->posts[0] : 0;
}

function voiceweb_gift_asset_keys( array $keys ): array {
	if ( is_front_page() ) { $keys[] = 'gift-landing'; }
	if ( is_page_template( 'page-templates/gift-landing.php' ) ) {
		$keys[] = 'front';
		if ( function_exists( 'WC' ) ) { $keys[] = 'woocommerce'; $keys[] = 'woocommerce-wishlist'; }
		$keys[] = 'gift-landing';
	} elseif ( is_page_template( 'page-templates/gift-hub.php' ) ) {
		$keys[] = 'front'; $keys[] = 'gift-landing';
	}
	return array_values( array_unique( $keys ) );
}
add_filter( 'cn/page-keys', 'voiceweb_gift_asset_keys', 25 );

/** Existing SEO plugins own titles, descriptions and structured data when active. */
function voiceweb_gift_has_seo_plugin(): bool {
	$active = defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' )
		|| defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' )
		|| defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' )
		|| defined( 'SEOPRESS_VERSION' ) || function_exists( 'seopress_init' )
		|| defined( 'THE_SEO_FRAMEWORK_VERSION' ) || function_exists( 'the_seo_framework' );
	return (bool) apply_filters( 'voiceweb/gift/seo-plugin-active', $active );
}

/** Do not expose private/password-protected page copy in fallback metadata. */
function voiceweb_gift_seo_context(): bool {
	if ( is_admin() || ! is_singular( 'page' ) || ! is_page_template( array( 'page-templates/gift-landing.php', 'page-templates/gift-hub.php' ) ) || voiceweb_gift_has_seo_plugin() ) { return false; }
	$page = get_queried_object();
	return $page instanceof WP_Post && $page->post_status === 'publish' && $page->post_password === '';
}

function voiceweb_gift_front_seo_context(): bool {
	if ( is_admin() || ! is_front_page() || voiceweb_gift_has_seo_plugin() ) { return false; }
	$page = get_queried_object();
	return $page instanceof WP_Post && $page->post_type === 'page' && $page->post_status === 'publish' && $page->post_password === '';
}

function voiceweb_gift_seo_title( string $title ): string {
	if ( voiceweb_gift_front_seo_context() ) {
		$front_title = voiceweb_front_value( 'seo_title' );
		if ( is_string( $front_title ) && trim( $front_title ) !== '' ) { return trim( wp_strip_all_tags( $front_title ) ); }
	}
	if ( ! voiceweb_gift_seo_context() ) { return $title; }
	$custom = voiceweb_gift_value( 'seo_title' );
	return is_string( $custom ) && trim( $custom ) !== '' ? trim( wp_strip_all_tags( $custom ) ) : $title;
}
add_filter( 'pre_get_document_title', 'voiceweb_gift_seo_title', 20 );

/** Keep WordPress's canonical output; supply only otherwise missing metadata. */
function voiceweb_gift_seo_head(): void {
	if ( voiceweb_gift_front_seo_context() ) {
		$front_description = voiceweb_front_value( 'seo_description' );
		if ( is_string( $front_description ) && trim( $front_description ) !== '' ) {
			echo '<meta name="description" content="' . esc_attr( trim( wp_strip_all_tags( strip_shortcodes( $front_description ) ) ) ) . '">' . "\n";
		}
		return;
	}
	if ( ! voiceweb_gift_seo_context() ) { return; }
	$post_id = (int) get_queried_object_id();
	$url = wp_get_canonical_url( $post_id );
	if ( ! $url ) { return; }
	$description = voiceweb_gift_value( 'seo_description', voiceweb_gift_value( 'intro', '' ) );
	$description = is_string( $description ) ? trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $description ) ) ) ) : '';
	if ( $description !== '' ) { echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n"; }
	$items = array( array( '@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => home_url( '/' ) ) );
	foreach ( array_reverse( get_post_ancestors( $post_id ) ) as $ancestor_id ) {
		$ancestor = get_post( $ancestor_id );
		if ( ! $ancestor instanceof WP_Post || $ancestor->post_status !== 'publish' || $ancestor->post_password !== '' ) { continue; }
		$items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'name' => wp_strip_all_tags( get_the_title( $ancestor_id ) ), 'item' => get_permalink( $ancestor_id ) );
	}
	$items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'name' => wp_strip_all_tags( get_the_title( $post_id ) ), 'item' => $url );
	$collection = array( '@type' => 'CollectionPage', '@id' => $url . '#webpage', 'url' => $url, 'name' => wp_strip_all_tags( get_the_title( $post_id ) ), 'inLanguage' => get_bloginfo( 'language' ), 'breadcrumb' => array( '@id' => $url . '#breadcrumb' ) );
	if ( $description !== '' ) { $collection['description'] = $description; }
	$graph = array( '@context' => 'https://schema.org', '@graph' => array( $collection, array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items ) ) );
	$json = wp_json_encode( $graph, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( $json ) { echo '<script type="application/ld+json" class="vw-gift-schema">' . $json . '</script>' . "\n"; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON hex flags protect the script context.
}
add_action( 'wp_head', 'voiceweb_gift_seo_head', 20 );
