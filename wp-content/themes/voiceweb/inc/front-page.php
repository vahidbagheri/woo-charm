<?php
/** Readable storefront helpers; data belongs to the front-page ACF form and native Woo. */
defined( 'ABSPATH' ) || exit;

function voiceweb_front_value( string $name, $fallback = '' ) {
	$id = absint( get_option( 'page_on_front' ) );
	$value = $id && function_exists( 'get_field' ) ? get_field( 'vw_front_' . $name, $id ) : null;
	return $value === null || $value === false || $value === '' ? $fallback : $value;
}
function voiceweb_front_enabled( string $name ): bool {
	$id = absint( get_option( 'page_on_front' ) );
	return ! metadata_exists( 'post', $id, 'vw_front_' . $name . '_enabled' ) || (bool) voiceweb_front_value( $name . '_enabled', false );
}
function voiceweb_front_products( string $section ): array {
	if ( ! function_exists( 'wc_get_products' ) ) { return array(); }
	$ids = array_slice( array_filter( array_map( 'absint', (array) voiceweb_front_value( $section . '_products', array() ) ) ), 0, 12 );
	$args = array( 'status' => 'publish', 'visibility' => 'visible', 'limit' => 6, 'orderby' => 'date', 'order' => 'DESC' );
	if ( $ids ) { $args['include'] = $ids; $args['limit'] = count( $ids ); }
	elseif ( $section === 'offers' ) {
		$on_sale = array_filter( wc_get_product_ids_on_sale(), static fn( $id ) => get_post_type( $id ) === 'product' );
		if ( $on_sale ) { $args['include'] = array_values( $on_sale ); }
	}
	$hide_out = get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes';
	$products = array_values( array_filter( wc_get_products( $args ), static fn( $p ) => $p->is_visible() && ( ! $hide_out || $p->is_in_stock() ) ) );
	if ( $ids ) { $order = array_flip( $ids ); usort( $products, static fn( $a, $b ) => $order[ $a->get_id() ] <=> $order[ $b->get_id() ] ); }
	return $products;
}
function voiceweb_front_categories(): array {
	if ( ! taxonomy_exists( 'product_cat' ) ) { return array(); }
	$rows = (array) voiceweb_front_value( 'categories', array() );
	if ( ! $rows ) {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 6, 'exclude' => array( absint( get_option( 'default_product_cat' ) ) ) ) );
		if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $term ) { $rows[] = array( 'term' => $term->term_id ); } }
	}
	$result = array();
	foreach ( array_slice( $rows, 0, 12 ) as $row ) {
		$term = get_term( absint( $row['term'] ?? 0 ), 'product_cat' );
		if ( ! $term instanceof WP_Term ) { continue; }
		$url = get_term_link( $term );
		if ( is_wp_error( $url ) ) { continue; }
		$result[] = array_merge( $row, array( 'label' => ( $row['label'] ?? '' ) ?: $term->name, 'url' => $url, 'image' => ( $row['image'] ?? 0 ) ?: get_term_meta( $term->term_id, 'thumbnail_id', true ) ) );
	}
	return $result;
}
function voiceweb_front_posts(): array {
	$ids = array_slice( array_filter( array_map( 'absint', (array) voiceweb_front_value( 'blog_posts', array() ) ) ), 0, 3 );
	$args = array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true, 'suppress_filters' => false );
	if ( $ids ) { $args['post__in'] = $ids; $args['orderby'] = 'post__in'; }
	return ( new WP_Query( $args ) )->posts;
}
function voiceweb_front_icon( string $name ): string {
	$paths = array( 'arrow' => '<path d="M18 12H6m5-5-5 5 5 5"/>', 'left' => '<path d="m15 6-6 6 6 6"/>', 'right' => '<path d="m9 6 6 6-6 6"/>', 'plus' => '<path d="M12 5v14M5 12h14"/>', 'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>', 'shield' => '<path d="m12 3 8 3v5c0 5-8 10-8 10S4 16 4 11V6z"/><path d="m8 11 3 3 5-5"/>', 'truck' => '<path d="M3 6h11v11H3zm11 5h4l3 4v2h-7"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>', 'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>' );
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? $paths['check'] ) . '</svg>';
}
function voiceweb_front_link( $link, string $class = 'vw-store-link' ): void {
	if ( ! is_array( $link ) || empty( $link['url'] ) || empty( $link['title'] ) ) { return; }
	$target = ( $link['target'] ?? '' ) === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';
	echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $link['url'] ) . '"' . $target . '>' . esc_html( $link['title'] ) . voiceweb_front_icon( 'arrow' ) . '</a>';
}

/** Responsive image with real dimensions. Inactive hero slides defer every network URL. */
function voiceweb_front_image( int $id, string $class = '', bool $eager = false, bool $deferred = false, string $sizes = '(max-width: 767px) 100vw, 50vw' ): void {
	if ( ! $id ) { return; }
	$html = wp_get_attachment_image( $id, 'large', false, array( 'class' => $class, 'alt' => '', 'loading' => $eager ? 'eager' : 'lazy', 'fetchpriority' => $eager ? 'high' : 'low', 'decoding' => 'async', 'sizes' => $sizes ) );
	if ( $deferred ) {
		$html = preg_replace( '/\s(src|srcset)=/', ' data-vw-$1=', $html );
		$html = str_replace( '<img ', '<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" ', $html );
	}
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/** The advertised countdown is backed by existing, actually discounted Woo prices. */
function voiceweb_front_offer_deadline( array $products ): int {
	$ends = array();
	foreach ( $products as $product ) {
		$items = $product->is_type( 'variable' ) ? array_filter( array_map( 'wc_get_product', $product->get_visible_children() ) ) : array( $product );
		foreach ( $items as $item ) {
			if ( $item->get_status() !== 'publish' || ! $item->is_in_stock() || ( $item->is_type( 'variation' ) && ! $item->variation_is_visible() ) ) { continue; }
			$date = $item->get_date_on_sale_to();
			if ( $item->is_on_sale() && $date && $date->getTimestamp() > time() ) { $ends[] = $date->getTimestamp(); }
		}
	}
	return $ends ? min( $ends ) : 0;
}
function voiceweb_front_save_offer_deadline( $post_id ): void {
	if ( ! is_numeric( $post_id ) || (int) $post_id !== (int) get_option( 'page_on_front' ) || ! current_user_can( 'edit_post', (int) $post_id ) || ! function_exists( 'wc_get_product' ) ) { return; }
	$value = (string) voiceweb_front_value( 'offers_deadline' );
	if ( ! $value ) { return; }
	try { $date = new DateTimeImmutable( $value, wp_timezone() ); } catch ( Exception $error ) { return; }
	$ends = $date->getTimestamp();
	if ( $ends <= time() ) { return; }
	$ids = array_slice( array_unique( array_filter( array_map( 'absint', (array) voiceweb_front_value( 'offers_products', array() ) ) ) ), 0, 12 );
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id ); if ( ! $product || $product->get_status() !== 'publish' || ! current_user_can( 'edit_post', $id ) ) { continue; }
		$items = $product->is_type( 'variable' ) ? array_filter( array_map( 'wc_get_product', $product->get_children() ) ) : array( $product );
		foreach ( $items as $item ) { if ( $item->get_sale_price() !== '' ) { $item->set_date_on_sale_to( $ends ); $item->save(); } }
		if ( $product->is_type( 'variable' ) ) { WC_Product_Variable::sync( $id ); }
	}
}
add_action( 'acf/save_post', 'voiceweb_front_save_offer_deadline', 30 );

function voiceweb_front_asset_keys( array $keys ): array {
	if ( is_front_page() && function_exists( 'WC' ) ) { $keys[] = 'woocommerce'; $keys[] = 'woocommerce-wishlist'; }
	if ( is_page_template( 'page-templates/about.php' ) ) { $keys[] = 'front'; }
	return array_values( array_unique( $keys ) );
}
add_filter( 'cn/page-keys', 'voiceweb_front_asset_keys', 20 );
function voiceweb_front_script_deps( array $deps, string $handle, string $key ): array {
	if ( $key === 'front' && wp_script_is( 'wc-add-to-cart', 'registered' ) ) { $deps[] = 'wc-add-to-cart'; }
	return array_values( array_unique( $deps ) );
}
add_filter( 'cn/asset/script-deps', 'voiceweb_front_script_deps', 10, 3 );
function voiceweb_front_preload_image(): void {
	if ( ! is_front_page() ) { return; }
	$slides = (array) voiceweb_front_value( 'slides', array() ); $id = absint( $slides[0]['image'] ?? 0 );
	$mobile = absint( $slides[0]['mobile_image'] ?? 0 );
	foreach ( array( array( $id, $mobile ? '(min-width: 768px)' : '' ), array( $mobile, '(max-width: 767px)' ) ) as list( $image, $media ) ) {
		$src = wp_get_attachment_image_url( $image, 'large' ); if ( ! $src ) { continue; }
		$srcset = wp_get_attachment_image_srcset( $image, 'large' );
		echo '<link rel="preload" as="image" href="' . esc_url( $src ) . '" imagesrcset="' . esc_attr( $srcset ?: '' ) . '" imagesizes="(max-width: 767px) 100vw, 58vw"' . ( $media ? ' media="' . esc_attr( $media ) . '"' : '' ) . ' fetchpriority="high">';
	}
}
add_action( 'wp_head', 'voiceweb_front_preload_image', 2 );
