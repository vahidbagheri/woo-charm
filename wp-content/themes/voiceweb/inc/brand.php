<?php
/** Editable brand pages and their public identity. */
defined( 'ABSPATH' ) || exit;

function voiceweb_brand_value( string $name, $fallback = '', int $post_id = 0 ) {
	$post_id = $post_id ?: (int) get_queried_object_id();
	if ( ! $post_id ) { return $fallback; }
	$key = 'vw_brand_' . sanitize_key( $name );
	$value = function_exists( 'get_field' ) ? get_field( $key, $post_id ) : null;
	if ( $value === null || $value === false || $value === '' ) { $value = get_post_meta( $post_id, $key, true ); }
	$repeaters = array( 'process' => array( 'title', 'text' ), 'leather' => array( 'title', 'text' ), 'faq' => array( 'question', 'answer' ) );
	if ( isset( $repeaters[ $name ] ) && ! is_array( $value ) ) {
		$rows = array();
		for ( $i = 0; $i < min( 8, max( 0, (int) $value ) ); $i++ ) {
			$row = array();
			foreach ( $repeaters[ $name ] as $field ) { $row[ $field ] = (string) get_post_meta( $post_id, $key . '_' . $i . '_' . $field, true ); }
			$rows[] = $row;
		}
		return $rows ?: $fallback;
	}
	return $value === null || $value === '' || $value === false ? $fallback : $value;
}

function voiceweb_brand_page_url( string $kind ): string {
	if ( ! in_array( $kind, array( 'about', 'contact' ), true ) ) { return ''; }
	$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'has_password' => false, 'numberposts' => 1, 'meta_key' => '_wp_page_template', 'meta_value' => 'page-templates/' . $kind . '.php', 'suppress_filters' => false, 'orderby' => 'ID', 'order' => 'ASC' ) );
	return $pages ? (string) get_permalink( $pages[0] ) : '';
}

function voiceweb_brand_category_url( string $slug ): string {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) { return ''; }
	$url = get_term_link( $term );
	return is_wp_error( $url ) ? '' : $url;
}

function voiceweb_brand_asset_keys( array $keys ): array {
	if ( is_page_template( array( 'page-templates/about.php', 'page-templates/contact.php' ) ) ) { $keys[] = 'front'; $keys[] = 'brand-pages'; }
	return array_values( array_unique( $keys ) );
}
add_filter( 'cn/page-keys', 'voiceweb_brand_asset_keys', 30 );

function voiceweb_brand_seo_context(): bool {
	if ( is_admin() || is_front_page() || voiceweb_gift_has_seo_plugin() || ! is_page_template( array( 'page-templates/about.php', 'page-templates/contact.php' ) ) ) { return false; }
	$page = get_queried_object();
	return $page instanceof WP_Post && $page->post_status === 'publish' && $page->post_password === '';
}

function voiceweb_brand_seo_title( string $title ): string {
	if ( ! voiceweb_brand_seo_context() ) { return $title; }
	$custom = voiceweb_brand_value( 'seo_title' );
	return is_string( $custom ) && trim( $custom ) !== '' ? trim( wp_strip_all_tags( $custom ) ) : $title;
}
add_filter( 'pre_get_document_title', 'voiceweb_brand_seo_title', 25 );

/** Metadata for brand pages and products; existing collection/SEO-plugin ownership is retained. */
function voiceweb_brand_seo_head(): void {
	if ( is_admin() || voiceweb_gift_has_seo_plugin() ) { return; }
	$description = ''; $graph = array();
	if ( voiceweb_brand_seo_context() ) {
		$id = (int) get_queried_object_id(); $url = get_permalink( $id );
		$description = voiceweb_brand_value( 'seo_description', voiceweb_brand_value( 'intro' ) );
		$graph[] = array( '@type' => is_page_template( 'page-templates/contact.php' ) ? 'ContactPage' : 'AboutPage', '@id' => $url . '#webpage', 'url' => $url, 'name' => get_the_title( $id ), 'inLanguage' => get_bloginfo( 'language' ), 'about' => array( '@id' => home_url( '/#organization' ) ) );
	} elseif ( ! is_front_page() && function_exists( 'is_product' ) && is_product() && get_post_field( 'post_password', get_queried_object_id() ) === '' && get_post_status() === 'publish' ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) { $description = $product->get_short_description() ?: $product->get_description(); }
	} elseif ( ! is_front_page() && function_exists( 'is_shop' ) && is_shop() ) {
		$description = 'خرید کیف و کیف پول چرم طبیعی از آوان چرم؛ مدل‌های مردانه، زنانه و جاکارتی را از نظر ابعاد، رنگ، کاربرد و قیمت مقایسه کنید.';
	} elseif ( ! is_front_page() && is_singular( 'post' ) && get_post_field( 'post_password', get_queried_object_id() ) === '' && get_post_status() === 'publish' ) {
		$description = get_the_excerpt();
	}
	if ( is_string( $description ) && $description !== '' ) {
		$description = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $description ) ) ) );
		$description = wp_html_excerpt( $description, 180, '…' );
		if ( $description !== '' ) { echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n"; }
	}
	if ( is_front_page() || voiceweb_brand_seo_context() ) {
		$graph[] = array( '@type' => 'Organization', '@id' => home_url( '/#organization' ), 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ), 'logo' => get_theme_file_uri( 'assets/images/avan-charm-mark.svg' ) );
	}
	if ( $graph ) {
		echo '<script type="application/ld+json" class="vw-brand-schema">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'voiceweb_brand_seo_head', 25 );

function voiceweb_brand_favicon(): void {
	if ( get_option( 'voiceweb_avan_brand_version' ) && ! get_option( 'site_icon' ) ) { echo '<link rel="icon" type="image/svg+xml" href="' . esc_url( get_theme_file_uri( 'assets/images/avan-charm-mark.svg' ) ) . '">' . "\n"; }
}
add_action( 'wp_head', 'voiceweb_brand_favicon', 99 );
add_action( 'login_head', 'voiceweb_brand_favicon', 99 );
add_action( 'admin_head', 'voiceweb_brand_favicon', 99 );

/** Explicit editorial exclusions, also respected by the native WordPress sitemap. */
function voiceweb_brand_noindex( array $robots ): array {
	if ( is_singular() && get_post_meta( get_queried_object_id(), 'vw_brand_noindex', true ) && ! voiceweb_gift_has_seo_plugin() ) {
		unset( $robots['index'] ); $robots['noindex'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'voiceweb_brand_noindex', 25 );

function voiceweb_brand_sitemap_query( array $args, string $post_type ): array {
	$exclusion = array( 'relation' => 'OR', array( 'key' => 'vw_brand_noindex', 'compare' => 'NOT EXISTS' ), array( 'key' => 'vw_brand_noindex', 'value' => '1', 'compare' => '!=' ) );
	$args['meta_query'] = empty( $args['meta_query'] ) ? $exclusion : array( 'relation' => 'AND', $args['meta_query'], $exclusion );
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'voiceweb_brand_sitemap_query', 20, 2 );
