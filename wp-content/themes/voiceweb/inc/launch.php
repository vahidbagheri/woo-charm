<?php
/** Launch preparation for avancharm.ir; no environment spoofing or audit-specific behavior. */
defined( 'ABSPATH' ) || exit;

add_action( 'send_headers', static function () {
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
} );
add_filter( 'wp_sitemaps_posts_query_args', static function ( array $args, string $type ): array {
	if ( $type === 'page' && function_exists( 'wc_get_page_id' ) ) {
		$private = array_filter( array_map( 'wc_get_page_id', array( 'cart', 'checkout', 'myaccount' ) ), static fn( $id ) => $id > 0 );
		$args['post__not_in'] = array_values( array_unique( array_merge( $args['post__not_in'] ?? array(), $private ) ) );
	}
	return $args;
}, 30, 2 );

// The storefront uses its own menus/modals, so Bootstrap components are not needed on this page.
add_filter( 'cn/font-config', static function ( array $config ): array {
	if ( is_front_page() && isset( $config['fa']['font_faces'] ) ) {
		$file = get_template_directory() . '/config/launch-display-fonts.json';
		$optimized = is_file( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
		foreach ( $config['fa']['font_faces'] as &$face ) {
			$face['display'] = 'optional';
			if ( ! preg_match( '/DanaFaNum-(Regular|Medium|Bold)\.woff2$/', $face['path'] ?? '', $weight ) ) { continue; }
			$record = $optimized[ $weight[1] ] ?? null;
			$original = get_template_directory() . '/' . $face['path'];
			if ( $record && is_file( $original ) && is_file( get_template_directory() . '/' . $record['path'] ) && hash_equals( $record['source_sha256'], hash_file( 'sha256', $original ) ) ) { $face['path'] = $record['path']; }
			$face['preload'] = $weight[1] === 'Bold';
		}
	}
	return $config;
} );
foreach ( array( 'cn/default-resources/css', 'cn/default-resources/js' ) as $filter ) {
	add_filter( $filter, static fn( $resources ) => is_front_page() ? array_diff( $resources, array( 'bootstrap' ) ) : $resources );
}
add_action( 'wp', static function () {
	if ( ! is_front_page() ) { return; }
	// The custom front template does not render Gutenberg content. Prevent late excerpt
	// processing from bringing its unused block styles back into the document.
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
} );
add_action( 'wp_enqueue_scripts', static function () {
	if ( ! is_front_page() ) { return; }
	wp_enqueue_style( 'voiceweb-storefront-base', get_template_directory_uri() . '/assets/css/pages/storefront-base.css', array(), filemtime( get_template_directory() . '/assets/css/pages/storefront-base.css' ) );
}, 5 );

function voiceweb_launch_front_assets(): void {
	if ( ! is_front_page() ) { return; }
	foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles', 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'woo-variation-swatches', 'woo-variation-swatches-theme-override' ) as $handle ) { wp_dequeue_style( $handle ); }
	foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles' ) as $handle ) { wp_deregister_style( $handle ); }
	foreach ( array( 'wc-add-to-cart-variation', 'woo-variation-swatches' ) as $handle ) { wp_dequeue_script( $handle ); }
	// These scripts use DOM-ready/deferred handlers. Localized configuration remains before its owner.
	$scripts = wp_scripts();
	$visit = static function ( $handle ) use ( &$visit, $scripts ): void {
		if ( empty( $scripts->registered[ $handle ] ) ) { return; }
		wp_script_add_data( $handle, 'strategy', 'defer' );
		foreach ( $scripts->registered[ $handle ]->deps as $dependency ) { $visit( $dependency ); }
	};
	foreach ( $scripts->queue as $handle ) { $visit( $handle ); }
}
add_action( 'wp_enqueue_scripts', 'voiceweb_launch_front_assets', 999 );
add_action( 'wp_enqueue_scripts', static function () {
	if ( is_front_page() || ! wp_style_is( 'iconly-font', 'enqueued' ) ) { return; }
	$file = WP_PLUGIN_DIR . '/acf-iconly-picker/assets/fonts/iconly.woff2';
	if ( ! is_file( $file ) ) { return; }
	$url = add_query_arg( 'ver', (string) filemtime( $file ), plugins_url( 'acf-iconly-picker/assets/fonts/iconly.woff2' ) );
	wp_add_inline_style( 'iconly-font', '@font-face{font-family:iconly;src:url("' . esc_url_raw( $url ) . '") format("woff2");font-weight:normal;font-style:normal;font-display:swap}' );
}, 1000 );

add_filter( 'cn/page-keys', static function ( array $keys ): array {
	if ( is_front_page() ) { $keys = array_diff( $keys, array( 'woocommerce' ) ); $keys[] = 'woocommerce-cards'; }
	return array_values( array_unique( $keys ) );
}, 100 );
add_action( 'wp_enqueue_scripts', static function () {
	if ( is_front_page() ) { wp_enqueue_script( 'cn-page-woocommerce', get_template_directory_uri() . '/assets/js/pages/woocommerce.js', array( 'jquery' ), filemtime( get_template_directory() . '/assets/js/pages/woocommerce.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) ); }
}, 50 );

/** Inline the page's small stylesheets in dependency order instead of blocking on many requests. */
function voiceweb_launch_deferred_styles(): array {
	return array( 'cn-footer', 'cn-page-woocommerce-wishlist', 'cn-page-woocommerce-inquiry', 'cn-page-gift-landing', 'cn-page-woocommerce-cards', 'voiceweb-auth' );
}
add_filter( 'style_loader_tag', static function ( string $tag, string $handle ): string {
	if ( ! is_front_page() || ! in_array( $handle, voiceweb_launch_deferred_styles(), true ) ) { return $tag; }
	$deferred = preg_replace( '/media=(["\x27])[^"\x27]*\1/', "media=\"print\" onload=\"this.media='all'\"", $tag );
	return $deferred . '<noscript>' . $tag . '</noscript>';
}, 20, 2 );
function voiceweb_launch_inline_styles(): void {
	if ( ! is_front_page() || is_admin() ) { return; }
	$styles = wp_styles();
	foreach ( $styles->registered as $handle => $style ) {
		if ( ! $style->src || ! wp_style_is( $handle, 'enqueued' ) || in_array( $handle, voiceweb_launch_deferred_styles(), true ) ) { continue; }
		$src = $style->src;
		$parts = wp_parse_url( $src );
		if ( ! empty( $parts['host'] ) && $parts['host'] !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { continue; }
		$path = rawurldecode( $parts['path'] ?? '' );
		$home_path = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		$file = ABSPATH . ltrim( substr( $path, strlen( $home_path ) ), '/' );
		if ( ! is_file( $file ) || ! str_ends_with( $file, '.css' ) || filesize( $file ) > 100000 ) { continue; }
		$css = file_get_contents( $file );
		$base = preg_replace( '~[^/]+$~', '', preg_replace( '/[?#].*$/', '', $src ) );
		$css = preg_replace_callback( '~url\(\s*(["\x27]?)([^)"\x27]+)\1\s*\)~i', static function ( $match ) use ( $base, $file ) {
			$url = trim( $match[2] );
			if ( preg_match( '~^(?:data:|\#)~i', $url ) ) { return $match[0]; }
			if ( ! preg_match( '~^(?:[a-z]+:|/)~i', $url ) ) {
				$resolved = realpath( dirname( $file ) . '/' . ( wp_parse_url( $url, PHP_URL_PATH ) ?: '' ) );
				$root = wp_normalize_path( ABSPATH );
				$url = $resolved && str_starts_with( wp_normalize_path( $resolved ), $root ) ? home_url( '/' . substr( wp_normalize_path( $resolved ), strlen( $root ) ) ) : $base . $url;
			}
			return 'url("' . ( function_exists( 'voiceweb_cache_asset_url' ) ? voiceweb_cache_asset_url( $url ) : $url ) . '")';
		}, $css );
		$css = preg_replace( '~/\*[^*]*\*+(?:[^/*][^*]*\*+)*/~', '', $css );
		if ( strpos( $src, 'iconly-font.css' ) !== false ) { $css = preg_replace( '/font-display\s*:\s*block/i', 'font-display:swap', $css ); }
		if ( $handle === 'iconly-font' ) {
			$metadata_file = get_template_directory() . '/assets/fonts/iconly/storefront.json';
			$original = WP_PLUGIN_DIR . '/acf-iconly-picker/assets/fonts/iconly.woff2';
			$metadata = is_file( $metadata_file ) ? json_decode( file_get_contents( $metadata_file ), true ) : null;
			$subset = cn_asset( 'assets/fonts/iconly/storefront.woff2' );
			if ( $subset && is_array( $metadata ) && is_file( $original ) && hash_equals( $metadata['source_sha256'] ?? '', hash_file( 'sha256', $original ) ) && preg_match( '/^U\+[A-F0-9]+(?:,U\+[A-F0-9]+)*$/D', $metadata['unicode_range'] ?? '' ) ) {
				$css .= '@font-face{font-family:iconly;src:url("' . esc_url_raw( $subset['url'] ) . '") format("woff2");font-weight:normal;font-style:normal;font-display:swap;unicode-range:' . $metadata['unicode_range'] . '}';
			}
		}
		$style->src = false;
		// Preserve any later inline overrides after the stylesheet they belong to.
		$after = $style->extra['after'] ?? array();
		$style->extra['after'] = array_merge( array( str_ireplace( '</style', '<\/style', $css ) ), $after );
	}
}
add_action( 'wp_print_styles', 'voiceweb_launch_inline_styles', 1 );

add_filter( 'wp_get_attachment_image_attributes', static function ( array $attr, $attachment, $size ): array {
	if ( $size === 'woocommerce_thumbnail' || strpos( $attr['class'] ?? '', 'attachment-woocommerce_thumbnail' ) !== false ) {
		$attr['sizes'] = is_front_page() ? '(max-width:599px) calc((100vw - 46px)/2), (max-width:991px) 44vw, 30vw' : '(max-width:599px) calc((100vw - 46px)/2), (max-width:991px) 43vw, 27vw';
		$attr['loading'] = 'lazy';
		$attr['fetchpriority'] = 'low';
	}
	return $attr;
}, 30, 3 );

/** Use optimized derivatives only while ACF still selects the exact original banner. */
function voiceweb_launch_hero_avif( int $attachment_id ): array {
	static $resolved = array();
	if ( isset( $resolved[ $attachment_id ] ) ) { return $resolved[ $attachment_id ]; }
	$resolved[ $attachment_id ] = array();
	$file = get_template_directory() . '/config/launch-hero-media.json';
	$metadata = is_file( $file ) ? json_decode( file_get_contents( $file ), true ) : null;
	$original = get_attached_file( $attachment_id );
	if ( ! is_array( $metadata ) || ! $original || ! is_file( $original ) || basename( $original ) !== ( $metadata['source_name'] ?? '' ) || ! hash_equals( $metadata['source_sha256'] ?? '', hash_file( 'sha256', $original ) ) ) { return array(); }
	$set = array(); $src = '';
	foreach ( $metadata['images'] ?? array() as $image ) {
		$asset = cn_asset( (string) $image['path'] );
		if ( ! $asset ) { return array(); }
		$src = $asset['url']; $set[] = $src . ' ' . absint( $image['width'] ) . 'w';
	}
	return $resolved[ $attachment_id ] = $src ? array( 'src' => $src, 'srcset' => implode( ', ', $set ) ) : array();
}
