<?php
/** Replace the local starter catalog with the reviewed Avan photo pack. Dry-run by default. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/avan-catalog-import' );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
$_SERVER['REQUEST_URI'] = '/';
$GLOBALS['wp_filter']['pre_http_request'][PHP_INT_MAX][] = array( 'function' => static fn() => new WP_Error( 'offline', 'External requests disabled during import.' ), 'accepted_args' => 3 );
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array( 'function' => '__return_false', 'accepted_args' => 1 );
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function avan_catalog_find( string $type, string $key ): int {
	$ids = get_posts( array(
		'post_type' => $type, 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future', 'inherit', 'trash' ),
		'fields' => 'ids', 'numberposts' => 1, 'meta_key' => '_voiceweb_avan_catalog_key', 'meta_value' => $key,
	) );
	return (int) ( $ids[0] ?? 0 );
}

function avan_catalog_media( string $key, string $name, string $alt, string $bytes ): int {
	$id = avan_catalog_find( 'attachment', $key );
	if ( $id && is_file( get_attached_file( $id ) ) ) {
		if ( ! wp_get_attachment_metadata( $id ) ) {
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, get_attached_file( $id ) ) );
		}
		return $id;
	}
	$upload = wp_upload_bits( $name, null, $bytes );
	if ( $upload['error'] ) { throw new RuntimeException( $upload['error'] ); }
	$result = wp_insert_attachment( array(
		'ID' => $id, 'post_title' => $alt, 'post_mime_type' => $upload['type'], 'post_status' => 'inherit',
		'meta_input' => array( '_voiceweb_avan_catalog_key' => $key, '_wp_attachment_image_alt' => $alt ),
	), $upload['file'], 0, true );
	if ( is_wp_error( $result ) ) { wp_delete_file( $upload['file'] ); throw new RuntimeException( $result->get_error_message() ); }
	$metadata = wp_generate_attachment_metadata( $result, $upload['file'] );
	if ( empty( $metadata['width'] ) ) { throw new RuntimeException( 'Could not process image ' . $key ); }
	wp_update_attachment_metadata( $result, $metadata );
	return (int) $result;
}

function avan_catalog_fields( string $prefix, $object, array $values ): void {
	foreach ( $values as $key => $value ) { update_field( 'field_vw_' . $prefix . '_' . $key, $value, $object ); }
}

function avan_catalog_gift_contact_copy( int $id, array $replacements ): void {
	$content = get_post_field( 'post_content', $id, 'raw' );
	$updated = strtr( $content, $replacements );
	if ( $updated !== $content ) {
		$result = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $updated ) ), true );
		if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	}
	foreach ( array( 'tips' => 'text', 'faq' => 'answer' ) as $field => $part ) {
		$rows = voiceweb_gift_value( $field, array(), $id );
		$updated_rows = $rows;
		foreach ( $updated_rows as &$row ) { $row[ $part ] = strtr( $row[ $part ] ?? '', $replacements ); }
		unset( $row );
		if ( $updated_rows !== $rows ) { avan_catalog_fields( 'gift', $id, array( $field => $updated_rows ) ); }
	}
}

/** Reuse the uploaded catalog photos for the manually identified cardholder models. */
function avan_catalog_update_cardholders( array $spec ): array {
	$slug = 'leather-cardholders';
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	$sets = get_term_by( 'slug', 'leather-gift-sets', 'product_cat' );
	if ( ! $term || ! $sets ) { throw new RuntimeException( 'Cardholder and gift-set categories are required.' ); }
	$photos = array();
	foreach ( $spec['cardholders'] as [ $title, $views ] ) {
		foreach ( $views as $source ) {
			if ( isset( $photos[ $source ] ) ) { continue; }
			$photo = avan_catalog_find( 'attachment', 'photo:' . $source );
			if ( ! $photo || ! wp_attachment_is_image( $photo ) || ! is_file( get_attached_file( $photo ) ) ) {
				throw new RuntimeException( 'Missing cardholder photo ' . $source );
			}
			$photos[ $source ] = $photo;
		}
	}

	$ids = array();
	foreach ( $spec['cardholders'] as $key => [ $title, $views, $description ] ) {
		$id = avan_catalog_find( 'product', $key );
		if ( ! $id ) {
			$product = new WC_Product_Simple();
			$product->set_name( $title );
			$product->set_slug( 'avan-' . $key );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_sku( 'AVAN-' . strtoupper( $key ) );
			$product->set_short_description( $description );
			$product->set_description( '<p>' . esc_html( $description ) . '</p><p>برای استعلام قیمت و سفارش طرح دلخواه با آوان چرم تماس بگیرید.</p>' );
			$product->set_category_ids( array( $term->term_id ) );
			$product->set_image_id( $photos[ $views[0] ] );
			$product->set_gallery_image_ids( array_map( static fn( $source ) => $photos[ $source ], array_slice( $views, 1 ) ) );
			$product->set_reviews_allowed( false );
			$product->update_meta_data( '_voiceweb_avan_catalog_key', $key );
			$product->update_meta_data( '_voiceweb_purchase_mode', 'inquiry' );
			$id = $product->save();
			if ( ! $id ) { throw new RuntimeException( 'Could not save ' . $key ); }
		}
		$ids[ $key ] = $id;
	}

	foreach ( array( 'burgundy-wallet-cardholder', 'red-embossed-floral-set' ) as $key ) {
		$product = wc_get_product( avan_catalog_find( 'product', $key ) );
		if ( ! $product ) { continue; }
		$categories = array_map( 'intval', $product->get_category_ids() );
		if ( ! in_array( (int) $term->term_id, $categories, true ) ) { continue; }
		$product->set_category_ids( array_unique( array_merge( array( (int) $sets->term_id ), array_diff( $categories, array( $term->term_id ) ) ) ) );
		$product->save();
	}

	list( $title, $photo, $range, $guide ) = $spec['categories'][ $slug ];
	$intro = $range . ' را در کاتالوگ آوان چرم ببینید. برای قیمت و سفارش مدل دلخواه تماس بگیرید.';
	$result = wp_update_term( $term->term_id, 'product_cat', array( 'description' => $intro ) );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	update_term_meta( $term->term_id, 'thumbnail_id', $photos[ $photo ] );
	avan_catalog_fields( 'cat', 'product_cat_' . $term->term_id, array(
		'intro' => $intro, 'seo_title' => $title . ' | مدل‌های تاشو و طرح‌دار | آوان چرم', 'seo_description' => $intro,
		'guide' => '<h3>راهنمای انتخاب جاکارتی چرم</h3><p>' . esc_html( $guide ) . '</p>',
		'tips' => array(
			array( 'title' => 'به نمای باز نگاه کنید', 'text' => 'در عکس مدل‌های تاشو، محل و شکل شیارهای کارت مشخص است.' ),
			array( 'title' => 'طرح دلخواه را مشخص کنید', 'text' => 'برای هماهنگی رنگ، نقش، بند مچی یا آینهٔ داخلی با آوان چرم تماس بگیرید.' ),
		),
	) );
	wc_delete_product_transients();
	return $ids;
}

function avan_catalog_product_terms( array $group, array $spec ): array {
	$key = $group['group_id'];
	return match ( $group['category'] ) {
		'bags'        => array( in_array( $key, $spec['bag_classic'], true ) ? 'leather-men-bags' : 'leather-women-bags' ),
		'wallets'     => array( in_array( $key, $spec['wallet_classic'], true ) ? 'leather-men-wallets' : 'leather-women-wallets' ),
		'accessories' => array( 'leather-accessories' ),
		'sets'        => array( 'leather-gift-sets' ),
	};
}

try {
	$apply = in_array( '--apply', $argv, true );
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! preg_match( '/(?:\.local|\.test)$|^localhost$|^127\.0\.0\.1$/i', $host ) || ! get_option( 'voiceweb_avan_brand_version' ) ||
		! function_exists( 'voiceweb_front_categories' ) || ! function_exists( 'wc_get_products' ) || ! function_exists( 'update_field' ) ) {
		throw new RuntimeException( 'Run on the local Avan WordPress installation with the VoiceWeb theme, ACF and WooCommerce active.' );
	}
	$spec = require dirname( __DIR__ ) . '/config/avan-catalog.php';
	if ( $done = get_option( 'voiceweb_avan_catalog_import_v1' ) ) {
		if ( empty( $done['cardholders'] ) ) {
			if ( $apply ) {
				$done['cardholders'] = avan_catalog_update_cardholders( $spec );
				$done['products'] += $done['cardholders'];
				update_option( 'voiceweb_avan_catalog_import_v1', $done, false );
			} else {
				$done['pending_cardholders'] = array_keys( $spec['cardholders'] );
			}
		}
		echo wp_json_encode( $done, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n";
		exit;
	}
	$zip_path = $argv[1] ?? '';
	$zip = new ZipArchive();
	if ( ! is_file( $zip_path ) || true !== $zip->open( $zip_path, ZipArchive::RDONLY ) ) {
		throw new RuntimeException( 'Pass the path to Leather-Shop-Upload.zip as the first argument.' );
	}
	$root = 'Leather-Shop-Upload/';
	$catalog = json_decode( $zip->getFromName( $root . 'catalog.json' ) ?: '', true, 512, JSON_THROW_ON_ERROR );
	$groups = $files = $products = $remaining = array();
	foreach ( $catalog['groups'] as $group ) {
		$key = $group['group_id'];
		if ( ! preg_match( '/^[a-z0-9-]+$/D', $key ) || isset( $groups[ $key ] ) ) { throw new RuntimeException( 'Invalid or duplicate group key.' ); }
		$groups[ $key ] = $group;
		$group_files = array();
		foreach ( $group['images'] as $item ) {
			$id = (int) $item['source_id'];
			$name = $item['file'];
			if ( isset( $files[ $id ] ) || ! preg_match( '#^images/[a-z0-9-]+/' . preg_quote( $key, '#' ) . '/photo-' . $id . '-studio\.jpg$#D', $name ) ) {
				throw new RuntimeException( 'Invalid or duplicate image: ' . $name );
			}
			$bytes = $zip->getFromName( $root . $name, 0, ZipArchive::FL_UNCHANGED );
			$size = $bytes ? getimagesizefromstring( $bytes ) : false;
			if ( ! $size || $size[2] !== IMAGETYPE_JPEG || $size[0] !== 1254 || $size[1] !== 1254 || hash( 'sha256', $bytes ) !== $item['sha256'] ) {
				throw new RuntimeException( 'Image validation failed: ' . $name );
			}
			$group_files[] = $name;
			$files[ $id ] = $item;
		}
		if ( ! in_array( $group['cover_image'], $group_files, true ) ) { throw new RuntimeException( 'Missing group cover: ' . $key ); }
		if ( in_array( $group['catalog_role'], array( 'product_views', 'set_views' ), true ) ) { $products[ $key ] = $group; }
		else { $remaining[ $group['catalog_role'] ][] = $key; }
	}
	if ( count( $groups ) !== 71 || count( $files ) !== 166 || count( $products ) !== 55 ) {
		throw new RuntimeException( 'The photo pack differs from the reviewed catalog.' );
	}

	$demo_ids = $gift_ids = $term_ids = array();
	foreach ( wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'draft', 'pending', 'private', 'future' ) ) ) as $product ) {
		if ( $product->get_meta( '_voiceweb_avan_catalog_key' ) ) { continue; }
		$id = $product->get_id();
		if ( str_starts_with( (string) $product->get_meta( '_voiceweb_leather_seed' ), 'product:' ) ||
			( $product->get_name() === 'تست' && trim( wp_strip_all_tags( $product->get_description() ) ) === 'تستی' ) ) {
			$demo_ids[] = $id;
		}
	}
	foreach ( $spec['categories'] as $slug => $category ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term && $slug !== 'leather-gift-sets' ) { throw new RuntimeException( 'Missing existing category: ' . $slug ); }
		$term_ids[ $slug ] = $term ? (int) $term->term_id : 0;
	}
	foreach ( $spec['gifts'] as $slug => $selection ) {
		$keys = array_slice( $selection, 1 );
		$page = get_page_by_path( 'gifts/' . $slug );
		if ( ! $page || $page->post_status !== 'publish' || array_diff( $keys, array_keys( $products ) ) ) {
			throw new RuntimeException( 'Missing gift page or mapped product: ' . $slug );
		}
		$gift_ids[ $slug ] = $page->ID;
	}
	$front = (int) get_option( 'page_on_front' );
	$hub = voiceweb_gift_hub_id();
	$shop_id = wc_get_page_id( 'shop' );
	$shop = wc_get_page_permalink( 'shop' );
	$contact = get_page_by_path( 'contact' );
	$banners = array(
		'hero' => 'کیف دوشی عسلی، کیف مشکی زنجیردار و کیف پول آبی گل‌دار',
		'wallets' => 'سه کیف پول چرمی با رنگ‌ها و نقش‌های گل‌دار',
		'sets' => 'ست چرمی شیری گل‌دار و ست زرشکی کیف پول و جاکارتی',
	);
	foreach ( $banners as $key => $alt ) {
		if ( ! is_file( dirname( __DIR__ ) . '/assets/images/avan-catalog-' . $key . '.webp' ) ) { throw new RuntimeException( 'Missing banner: ' . $key ); }
	}
	if ( ! $front || ! $hub || $shop_id <= 0 || ! $contact || ! acf_get_field( 'field_vw_front_slides' ) || ! acf_get_field( 'field_vw_gift_products' ) ) {
		throw new RuntimeException( 'The storefront ACF fields and destination pages must exist.' );
	}
	if ( $demo_ids && ! EMPTY_TRASH_DAYS ) { throw new RuntimeException( 'Enable the WordPress recycle bin before removing demo products.' ); }
	$summary = array(
		'images' => count( $files ), 'product_groups' => count( $products ), 'demo_ids' => $demo_ids,
		'gift_pages' => count( $gift_ids ), 'category_pages' => count( $term_ids ), 'other_image_groups' => $remaining,
		'additional_cardholders' => count( $spec['cardholders'] ),
	);
	if ( ! $apply ) {
		echo wp_json_encode( array( 'mode' => 'dry-run' ) + $summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n";
		exit;
	}

	$media = $image_ids = $product_ids = array();
	$alt_overrides = array( 315 => 'نمای داخلی کیف پول زرشکی', 316 => 'نمای داخلی کیف پول قرمز با بخش کارت' );
	foreach ( $files as $id => $item ) {
		$media[ $id ] = avan_catalog_media( 'photo:' . $id, 'avan-' . basename( $item['file'] ), $alt_overrides[ $id ] ?? $item['alt_fa'], $zip->getFromName( $root . $item['file'] ) );
		$image_ids[ $item['file'] ] = $media[ $id ];
	}
	$zip->close();
	$banner_ids = array();
	foreach ( $banners as $key => $alt ) {
		$name = 'avan-catalog-' . $key . '.webp';
		$banner_ids[ $key ] = avan_catalog_media( 'banner:' . $key, $name, $alt, file_get_contents( dirname( __DIR__ ) . '/assets/images/' . $name ) );
	}

	foreach ( $spec['categories'] as $slug => [ $title, $photo, $range, $guide ] ) {
		if ( ! $term_ids[ $slug ] ) {
			$term = wp_insert_term( $title, 'product_cat', array( 'slug' => $slug ) );
			if ( is_wp_error( $term ) ) { throw new RuntimeException( $term->get_error_message() ); }
			$term_ids[ $slug ] = (int) $term['term_id'];
		}
		$term_id = $term_ids[ $slug ];
		$intro = $range . ' را در کاتالوگ آوان چرم ببینید. برای قیمت و سفارش مدل دلخواه تماس بگیرید.';
		$result = wp_update_term( $term_id, 'product_cat', array( 'description' => $intro ) );
		if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
		update_term_meta( $term_id, 'thumbnail_id', $media[ $photo ] );
		avan_catalog_fields( 'cat', 'product_cat_' . $term_id, array(
			'intro' => $intro, 'seo_title' => $title . ' | مدل‌ها و سفارش تلفنی | آوان چرم', 'seo_description' => $intro,
			'guide' => '<h3>راهنمای انتخاب ' . esc_html( $title ) . '</h3><p>' . esc_html( $guide ) . '</p>',
			'faq' => array( array( 'question' => 'برای قیمت و سفارش مدل دلخواه چطور تماس بگیرم؟', 'answer' => 'از کارت یا صفحهٔ محصول روی تماس برای سفارش مدل دلخواه بزنید. برای پیگیری فروشگاه هم می‌توانید شماره‌تان را در فرم درخواست تماس ثبت کنید.' ) ),
		) );
	}

	foreach ( $products as $key => $group ) {
		$id = avan_catalog_find( 'product', $key );
		if ( $id ) { $product_ids[ $key ] = $id; continue; }
		$product = new WC_Product_Simple();
		$title = $spec['titles'][ $key ] ?? str_replace( array( 'کیف پول ', 'کیف دوشی ', 'کیف دستی ', 'کیف مچی ' ), array( 'کیف پول چرمی ', 'کیف دوشی چرمی ', 'کیف دستی چرمی ', 'کیف مچی چرمی ' ), $group['label_fa'] );
		$label = $group['label_fa'];
		$cover = $group['cover_image'];
		$views = $group['images'];
		usort( $views, static fn( $a, $b ) => $a['gallery_order'] <=> $b['gallery_order'] );
		$gallery = array();
		foreach ( $views as $view ) { if ( $view['file'] !== $cover ) { $gallery[] = $image_ids[ $view['file'] ]; } }
		$notes = $group['category'] === 'sets' ? 'برای هماهنگی اقلام همراه، رنگ و زمان آماده‌سازی تماس بگیرید.' : 'برای هماهنگی قیمت، ابعاد، جنس دقیق چرم و رنگ موردنظرتان تماس بگیرید.';
		$product->set_name( $title );
		$product->set_slug( 'avan-' . $key );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_sku( 'AVAN-' . strtoupper( $key ) );
		$product->set_short_description( $label . ' از کاتالوگ آوان چرم. ' . $notes );
		$product->set_description( '<p>' . esc_html( $label ) . ' را در عکس‌های کاتالوگ آوان چرم ببینید. ' . esc_html( $notes ) . '</p><p>اگر طرح دیگری در نظر دارید، هنگام تماس جزئیات مدل دلخواهتان را بگویید تا امکان ساخت بررسی شود.</p>' );
		$product->set_category_ids( array_map( static fn( $slug ) => $term_ids[ $slug ], avan_catalog_product_terms( $group, $spec ) ) );
		$product->set_image_id( $image_ids[ $cover ] );
		$product->set_gallery_image_ids( $gallery );
		$product->set_reviews_allowed( false );
		$product->update_meta_data( '_voiceweb_avan_catalog_key', $key );
		$product->update_meta_data( '_voiceweb_purchase_mode', 'inquiry' );
		$product_ids[ $key ] = $product->save();
		if ( ! $product_ids[ $key ] ) { throw new RuntimeException( 'Could not save product ' . $key ); }
	}

	foreach ( $spec['gifts'] as $slug => $selection ) {
		$description = array_shift( $selection );
		$keys = $selection;
		$id = $gift_ids[ $slug ];
		$ids = array_map( static fn( $key ) => $product_ids[ $key ], $keys );
		$categories = array( $term_ids['leather-gift-sets'] );
		foreach ( $keys as $key ) {
			foreach ( avan_catalog_product_terms( $products[ $key ], $spec ) as $term_slug ) { $categories[] = $term_ids[ $term_slug ]; }
		}
		$image = (int) get_post_thumbnail_id( $ids[0] );
		$title = get_the_title( $id );
		$intro = $description . ' را برای این مناسبت ببینید. برای هماهنگی قیمت و سفارش مدل دلخواه با آوان چرم تماس بگیرید.';
		avan_catalog_fields( 'gift', $id, array(
			'intro' => $intro, 'seo_title' => $title . ' | آوان چرم', 'seo_description' => $intro,
			'hero_image' => $image, 'product_source' => 'manual', 'products' => $ids, 'product_limit' => count( $ids ), 'categories' => array_values( array_unique( $categories ) ),
		) );
		set_post_thumbnail( $id, $image );
		avan_catalog_gift_contact_copy( $id, $spec['gift_contact_copy'] );
	}
	avan_catalog_fields( 'gift', $hub, array(
		'seo_description' => 'کیف، کیف پول و ست چرمی برای روز مادر، روز پدر، تولد و مناسبت‌های دیگر. مدل‌های پیشنهادی آوان چرم را ببینید و برای سفارش تماس بگیرید.',
	) );
	set_post_thumbnail( $hub, $banner_ids['sets'] );
	avan_catalog_gift_contact_copy( $hub, $spec['gift_contact_copy'] );
	$shop_update = wp_update_post( wp_slash( array(
		'ID' => $shop_id, 'post_title' => 'کاتالوگ محصولات چرمی',
		'post_excerpt' => 'کیف دوشی، کیف پول، جاکارتی و ست هدیهٔ آوان چرم را در کاتالوگ ببینید و برای قیمت و سفارش مدل دلخواه تماس بگیرید.',
	) ), true );
	if ( is_wp_error( $shop_update ) ) { throw new RuntimeException( $shop_update->get_error_message() ); }

	$link = static fn( string $url, string $title ) => array( 'url' => $url, 'title' => $title, 'target' => '' );
	$term_url = static fn( string $slug ) => get_term_link( $term_ids[ $slug ], 'product_cat' );
	$featured = array( 'brown-buckle-shoulder', 'black-gold-chain', 'blue-coral-flower-wallet', 'ivory-floral-set', 'tan-compact-fold', 'teal-floral-cosmetic' );
	avan_catalog_fields( 'front', $front, array(
		'header_note' => 'برای سفارش مدل دلخواه با آوان چرم تماس بگیرید',
		'slides' => array( array(
			'eyebrow' => 'آوان چرم', 'title' => "کیف و کیف پول چرمی\nبه انتخاب شما",
			'description' => 'مدل‌های دوشی، کیف پول‌های گل‌دار و ست‌های هدیه را ببینید. برای انتخاب یا سفارش طرح دلخواهتان با ما تماس بگیرید.',
			'image' => $banner_ids['hero'], 'mobile_image' => 0, 'floating_images' => array(), 'tone' => 'ink',
			'primary' => $link( $shop, 'دیدن کاتالوگ' ), 'secondary' => $link( get_permalink( $contact ), 'سفارش مدل دلخواه' ),
		) ),
		'autoplay' => 0, 'offers_enabled' => 0, 'offers_products' => array(),
		'promos' => array(
			array( 'title' => 'کیف پول‌های گل‌دار', 'description' => 'مدل‌های قفل‌دار و گلدوزی‌شده را از نزدیک ببینید.', 'image' => $banner_ids['wallets'], 'tone' => 'ice', 'link' => $link( $term_url( 'leather-women-wallets' ), 'دیدن کیف پول‌ها' ) ),
			array( 'title' => 'ست‌های هدیه چرمی', 'description' => 'برای هدیه، ترکیب‌های هماهنگ کیف و جاکارتی را ببینید.', 'image' => $banner_ids['sets'], 'tone' => 'rose', 'link' => $link( $term_url( 'leather-gift-sets' ), 'دیدن ست‌ها' ) ),
		),
		'categories_title' => 'مدل‌ها را بر اساس دسته ببینید',
		'categories' => array_values( array_map( static fn( $term, $category ) => array(
			'term' => $term, 'image' => $media[ $category[1] ], 'label' => $category[0], 'description' => '',
		), $term_ids, $spec['categories'] ) ),
		'arrivals_enabled' => 1, 'arrivals_title' => 'منتخب کاتالوگ',
		'arrivals_products' => array_map( static fn( $key ) => $product_ids[ $key ], $featured ),
		'collection_title' => 'جاکارتی‌ها و نقش‌های رنگی',
		'collection_description' => 'برای سفارش جاکارتی یا هماهنگی رنگ و ترکیب دلخواهتان تماس بگیرید.',
		'collection_image' => $media[309], 'collection_link' => $link( $term_url( 'leather-cardholders' ), 'دیدن مدل‌ها' ),
		'story_image' => $media[310], 'about_image' => $media[17],
		'seo_title' => 'کیف، کیف پول و ست چرمی | سفارش مدل دلخواه | آوان چرم',
		'seo_description' => 'کاتالوگ آوان چرم شامل کیف دوشی و دستی، کیف پول‌های گل‌دار و تاشو و ست‌های هدیه است. برای قیمت و سفارش طرح دلخواه با آوان چرم تماس بگیرید.',
	) );
	update_option( 'voiceweb_wc_purchase_mode', 'inquiry' );
	foreach ( $demo_ids as $id ) {
		if ( ! wp_trash_post( $id ) ) { throw new RuntimeException( 'Could not trash demo product ' . $id ); }
	}
	wc_delete_product_transients();
	$cardholders = avan_catalog_update_cardholders( $spec );
	$summary = array( 'mode' => 'applied' ) + $summary + array( 'products' => $product_ids + $cardholders, 'banners' => $banner_ids, 'cardholders' => $cardholders );
	update_option( 'voiceweb_avan_catalog_import_v1', $summary, false );
	echo wp_json_encode( $summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n";
} catch ( Throwable $error ) {
	fwrite( STDERR, $error->getMessage() . "\n" );
	exit( 1 );
}
