<?php
/**
 * Initial leather-store content. CLI only; dry-run unless --apply is supplied.
 * Existing seeded content is always kept so later editorial changes survive.
 *
 * @package voiceweb
 */
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 403 );
	exit;
}
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-leather-seed-log' );
$_SERVER['HTTP_HOST']   = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
add_filter( 'pre_http_request', static fn() => new WP_Error( 'seed_no_network', 'External requests are disabled while seeding.' ), PHP_INT_MAX );

if ( ! function_exists( 'wc_get_products' ) || ! function_exists( 'update_field' ) ) {
	fwrite( STDERR, "WooCommerce and ACF must be active.\n" );
	exit( 1 );
}
$apply = in_array( '--apply', $argv, true );
$theme = dirname( __DIR__ );
$page  = (int) get_option( 'page_on_front' );
$asset_specs = array(
	'hero'            => array( 'کالکشن کیف و اکسسوری‌های چرمی', 'کیف‌های سبز و مشکی، کیف دوشی عسلی و اکسسوری‌های چرمی روی پایهٔ سنگی' ),
	'men-black'       => array( 'کیف اداری آریا، مشکی', 'کیف اداری چرم مشکی با دسته و بند دوشی' ),
	'men-tan'         => array( 'کیف اداری آریا، عسلی', 'کیف اداری چرم عسلی با دسته و بند دوشی' ),
	'women-cream'     => array( 'کیف زنانه رونا، کرم', 'کیف زنانه چرم کرم با دو دسته و یراق طلایی' ),
	'women-black'     => array( 'کیف زنانه رونا، مشکی', 'کیف زنانه چرم مشکی با دو دسته و یراق طلایی' ),
	'wallet-tan'      => array( 'کیف پول مردانه هیراد', 'کیف پول تاشوی مردانه به رنگ عسلی' ),
	'wallet-burgundy' => array( 'کیف پول زنانه آوا', 'کیف پول زنانه زرشکی با در پاکتی' ),
	'card-green'      => array( 'جاکارتی چرم سرو', 'جاکارتی چرمی کوچک به رنگ سبز تیره' ),
	'accessory-tan'   => array( 'کیف دوشی کوچک مهر', 'کیف دوشی کوچک عسلی با بند چرمی' ),
	'craft'           => array( 'جزئیات دوخت چرم', 'نمای نزدیک دست‌ها هنگام دوخت لبهٔ چرم روی میز کار' ),
	'collection'      => array( 'کالکشن روزمرهٔ چرم', 'کیف اداری مشکی روی سنگ روشن با پس‌زمینهٔ سبز تیره' ),
);
$current_products = array();
foreach ( wc_get_products( array( 'limit' => 40, 'status' => array( 'publish', 'draft' ) ) ) as $product ) {
	$current_products[] = array( 'id' => $product->get_id(), 'name' => $product->get_name(), 'type' => $product->get_type(), 'seed' => (string) $product->get_meta( '_voiceweb_leather_seed' ), 'prior_seed' => (string) $product->get_meta( '_voiceweb_home_seed_key' ), 'prior_demo' => (string) $product->get_meta( '_voiceweb_home_demo' ) );
}
$missing_assets = array_values( array_filter( array_keys( $asset_specs ), static fn( $key ) => ! is_file( $theme . '/assets/images/leather-' . $key . '.png' ) ) );
if ( ! $apply ) {
	$variation_data = array();
	foreach ( $current_products as $summary ) {
		if ( $summary['seed'] && $summary['type'] === 'variable' ) {
			$product = wc_get_product( $summary['id'] );
			foreach ( $product->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );
				$variation_data[] = array( 'id' => $child_id, 'parent' => $product->get_id(), 'attributes' => $variation->get_attributes(), 'purchasable' => $variation->is_purchasable(), 'stock' => $variation->get_stock_quantity(), 'image' => $variation->get_image_id(), 'sale_ends' => $variation->get_date_on_sale_to() ? $variation->get_date_on_sale_to()->getTimestamp() : null );
			}
		}
	}
	echo wp_json_encode( array( 'mode' => 'dry-run', 'front_page' => $page, 'currency' => get_woocommerce_currency(), 'missing_assets' => $missing_assets, 'existing_products' => $current_products, 'variations' => $variation_data, 'seed_state' => get_option( 'voiceweb_leather_seed_v1', array() ), 'planned' => array( 'categories' => 6, 'products' => 6, 'variable_products' => 2, 'variations' => 8, 'blog_posts' => 3, 'about_page' => 1, 'media_assets' => count( $asset_specs ), 'purchase_mode' => 'one inquiry product; global normal purchasing preserved' ) ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n";
	exit;
}
if ( ! $page || 'IRR' !== get_woocommerce_currency() || $missing_assets ) {
	fwrite( STDERR, 'A static front page, native IRR currency and all image assets are required. Missing: ' . implode( ', ', $missing_assets ) . "\n" );
	exit( 1 );
}
if ( ! acf_get_field( 'field_vw_front_slides' ) ) {
	fwrite( STDERR, "The current homepage ACF schema must be registered first (field_vw_front_slides).\n" );
	exit( 1 );
}

/** Find only objects explicitly marked as this seed's own content. */
function vwl_seed_post_id( string $key, string $type ): int {
	$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_voiceweb_leather_seed', 'meta_value' => $key, 'suppress_filters' => true ) );
	return (int) ( $ids[0] ?? 0 );
}

/** Import one original image as WebP and generate native WordPress srcsets. */
function vwl_seed_image( string $key, array $spec ): int {
	$id = vwl_seed_post_id( 'asset:' . $key, 'attachment' );
	if ( $id ) {
		return $id;
	}
	$source = $GLOBALS['theme'] . '/assets/images/leather-' . $key . '.png';
	$editor = wp_get_image_editor( $source );
	if ( is_wp_error( $editor ) || ! $editor->supports_mime_type( 'image/webp' ) ) {
		throw new RuntimeException( 'WebP-capable WordPress image editor is required for ' . $key );
	}
	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) || ! wp_mkdir_p( $uploads['path'] ) ) {
		throw new RuntimeException( 'Cannot create the native media upload directory.' );
	}
	$editor->set_quality( 82 );
	$file = $uploads['path'] . '/' . wp_unique_filename( $uploads['path'], 'voiceweb-leather-' . $key . '.webp' );
	$saved = $editor->save( $file, 'image/webp' );
	if ( is_wp_error( $saved ) ) {
		throw new RuntimeException( 'Image conversion failed: ' . $key );
	}
	$id = wp_insert_attachment( array( 'post_mime_type' => 'image/webp', 'post_title' => $spec[0], 'post_content' => '', 'post_status' => 'inherit' ), $saved['path'], 0, true );
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( 'Attachment registration failed: ' . $key );
	}
	update_post_meta( $id, '_voiceweb_leather_seed', 'asset:' . $key );
	update_post_meta( $id, '_wp_attachment_image_alt', $spec[1] );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $saved['path'] ) );
	return (int) $id;
}

/** Register a global attribute through WooCommerce and retain existing settings. */
function vwl_seed_attribute( string $slug, string $label, string $type ): int {
	$id = wc_attribute_taxonomy_id_by_name( $slug );
	if ( ! $id ) {
		$id = wc_create_attribute( array( 'name' => $label, 'slug' => $slug, 'type' => $type, 'order_by' => 'menu_order', 'has_archives' => true ) );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( 'Global attribute creation failed: ' . $slug );
		}
	}
	$taxonomy = 'pa_' . $slug;
	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy( $taxonomy, array( 'product' ), array( 'public' => true, 'hierarchical' => false, 'show_ui' => true, 'query_var' => true, 'rewrite' => false ) );
	}
	return (int) $id;
}

/** Native product category/attribute term, with first-run-only presentation data. */
function vwl_seed_term( string $name, string $slug, string $taxonomy, array $meta = array(), string $description = '' ): int {
	$found = get_term_by( 'slug', $slug, $taxonomy );
	if ( $found instanceof WP_Term ) {
		return (int) $found->term_id;
	}
	$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug, 'description' => $description ) );
	if ( is_wp_error( $created ) ) {
		throw new RuntimeException( 'Term creation failed: ' . $slug );
	}
	$id = (int) $created['term_id'];
	update_term_meta( $id, '_voiceweb_leather_seed', $slug );
	foreach ( $meta as $field => $value ) {
		update_term_meta( $id, $field, $value );
	}
	return $id;
}

/** Format catalog copy as safe, ordinary WordPress content. */
function vwl_seed_description( string $intro, array $details ): string {
	$copy = '<h2>همراه جزئیات روزمره</h2><p>' . esc_html( $intro ) . '</p><h3>طراحی و کاربرد</h3><ul>';
	foreach ( $details as $detail ) {
		$copy .= '<li>' . esc_html( $detail ) . '</li>';
	}
	return $copy . '</ul><h3>نگهداری</h3><p>برای پاک‌کردن سطح، ابتدا از یک پارچهٔ نرم و خشک استفاده کنید. محصول را دور از رطوبت طولانی، آفتاب مستقیم و منابع گرما نگه دارید. پیش از استفاده از هر محصول مراقبتی، سازگاری آن را با نوع چرم بررسی و روی بخشی دور از دید آزمایش کنید.</p><p>برای انتخاب رنگ و اندازه، گزینه‌های همین صفحه را بررسی کنید. اگر دربارهٔ کاربرد یا ابعاد سؤال دارید، از بخش درخواست راهنمایی با ما در تماس باشید.</p>';
}

/** A new real WooCommerce product with stock, shipping data and scheduled sale. */
function vwl_seed_product( string $key, array $spec, array $media, array $categories, array $attributes, array $terms, int $deadline ): int {
	$existing = vwl_seed_post_id( 'product:' . $key, 'product' );
	if ( $existing ) {
		return $existing;
	}
	$variable = ! empty( $spec['colors'] );
	// Reuse only the six explicitly owned phone/tablet starter products. The
	// user's own products and every order/stock history remain untouched.
	$prior_keys = array( 'aria' => 'phone', 'rona' => 'tablet', 'hirad' => 'audio', 'ava' => 'watch', 'sarv' => 'charge', 'mehr' => 'box' );
	$prior_ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_voiceweb_home_seed_key', 'value' => $prior_keys[ $key ] ), array( 'key' => '_voiceweb_home_demo', 'value' => '1' ) ), 'suppress_filters' => true ) );
	$prior_id = (int) ( $prior_ids[0] ?? 0 );
	$product = $variable ? new WC_Product_Variable( $prior_id ) : new WC_Product_Simple( $prior_id );
	$product->set_name( $spec['name'] );
	$product->set_slug( 'leather-' . $key );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_description( vwl_seed_description( $spec['intro'], $spec['details'] ) );
	$product->set_short_description( '<p>' . esc_html( $spec['short'] ) . '</p>' );
	$product->set_sku( 'VW-L-' . strtoupper( $key ) );
	$product->set_category_ids( array( $categories[ $spec['category'] ] ) );
	$product->set_image_id( $media[ $spec['image'] ] );
	$product->set_gallery_image_ids( array_values( array_unique( array_map( static fn( $key ) => $media[ $key ], $spec['gallery'] ?? array() ) ) ) );
	$product->set_weight( (string) wc_get_weight( $spec['weight'], get_option( 'woocommerce_weight_unit', 'kg' ), 'kg' ) );
	foreach ( array( 'length', 'width', 'height' ) as $index => $name ) {
		$product->{'set_' . $name}( (string) wc_get_dimension( $spec['dimensions'][ $index ], get_option( 'woocommerce_dimension_unit', 'cm' ), 'cm' ) );
	}
	$product->set_tax_status( 'taxable' );
	$product->set_reviews_allowed( true );
	$product->update_meta_data( '_voiceweb_leather_seed', 'product:' . $key );
	$product->delete_meta_data( '_voiceweb_home_demo' );
	$product->delete_meta_data( '_voiceweb_home_seed_key' );
	$product->update_meta_data( '_voiceweb_purchase_mode', ! empty( $spec['inquiry'] ) ? 'inquiry' : '' );
	$product->set_menu_order( $spec['order'] );
	if ( $variable ) {
		$product->set_manage_stock( false );
		$product->set_stock_quantity( null );
		$product->set_stock_status( 'instock' );
		$product->set_regular_price( '' );
		$product->set_sale_price( '' );
		$product->set_date_on_sale_from( null );
		$product->set_date_on_sale_to( null );
		$attribute_values = array();
		foreach ( array( 'color' => $spec['colors'], 'size' => array( 'standard', 'large' ) ) as $name => $options ) {
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( $attributes[ $name ] );
			$attribute->set_name( 'pa_' . $name );
			$attribute->set_options( array_map( static fn( $key ) => $terms[ $name ][ $key ], $options ) );
			$attribute->set_visible( true );
			$attribute->set_variation( true );
			$attribute_values[] = $attribute;
		}
		$product->set_attributes( $attribute_values );
		$product->set_default_attributes( array( 'pa_color' => $spec['colors'][0], 'pa_size' => 'standard' ) );
		$id = $product->save();
		foreach ( $spec['colors'] as $color ) {
			foreach ( array( 'standard', 'large' ) as $size ) {
				$variation = new WC_Product_Variation();
				$variation->set_parent_id( $id );
				$variation->set_status( 'publish' );
				$variation->set_attributes( array( 'pa_color' => $color, 'pa_size' => $size ) );
				$variation->set_sku( 'VW-L-' . strtoupper( $key . '-' . $color . '-' . $size ) );
				$extra = 'large' === $size ? 3000000 : 0;
				$variation->set_regular_price( (string) ( $spec['regular'] + $extra ) );
				if ( ! empty( $spec['sale'] ) ) {
					$variation->set_sale_price( (string) ( $spec['sale'] + $extra ) );
					$variation->set_date_on_sale_from( time() - 60 );
					$variation->set_date_on_sale_to( $deadline );
				}
				$variation->set_manage_stock( true );
				$variation->set_stock_quantity( 12 );
				$variation->set_stock_status( 'instock' );
				$variation->set_backorders( 'no' );
				$variation->set_image_id( $media[ $spec['color_images'][ $color ] ] );
				$dimensions = 'large' === $size ? ( 'aria' === $key ? array( 41, 10, 31 ) : array( 32, 13, 25 ) ) : $spec['dimensions'];
				foreach ( array( 'length', 'width', 'height' ) as $index => $name ) { $variation->{'set_' . $name}( (string) wc_get_dimension( $dimensions[ $index ], get_option( 'woocommerce_dimension_unit', 'cm' ), 'cm' ) ); }
				$variation->set_weight( (string) wc_get_weight( $spec['weight'] + ( 'large' === $size ? 0.1 : 0 ), get_option( 'woocommerce_weight_unit', 'kg' ), 'kg' ) );
				$variation->set_description( 'large' === $size ? 'اندازهٔ بزرگ؛ فضای بیشتر برای وسایل روزمره.' : 'اندازهٔ استاندارد؛ همراه جمع‌وجور روزمره.' );
				$variation->update_meta_data( '_voiceweb_leather_seed', 'variation:' . $key . ':' . $color . ':' . $size );
				$variation->save();
			}
		}
		WC_Product_Variable::sync( $id );
		wc_delete_product_transients( $id );
		return (int) $id;
	}
	$product->set_regular_price( (string) $spec['regular'] );
	if ( ! empty( $spec['sale'] ) ) {
		$product->set_sale_price( (string) $spec['sale'] );
		$product->set_date_on_sale_from( time() - 60 );
		$product->set_date_on_sale_to( $deadline );
	}
	$product->set_manage_stock( true );
	$product->set_stock_quantity( 18 );
	$product->set_stock_status( 'instock' );
	$product->set_backorders( 'no' );
	return (int) $product->save();
}

/** Add an owned menu; existing and manually edited menus are never removed. */
function vwl_seed_menu( string $key, string $label, array $items ): int {
	$found = get_terms( array( 'taxonomy' => 'nav_menu', 'hide_empty' => false, 'number' => 1, 'meta_key' => '_voiceweb_leather_seed', 'meta_value' => 'menu:' . $key ) );
	if ( ! is_wp_error( $found ) && $found ) { return (int) $found[0]->term_id; }
	$id = wp_create_nav_menu( $label );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( 'Menu creation failed: ' . $key ); }
	update_term_meta( $id, '_voiceweb_leather_seed', 'menu:' . $key );
	foreach ( $items as $item ) {
		$result = wp_update_nav_menu_item( $id, 0, array( 'menu-item-title' => $item[0], 'menu-item-type' => 'custom', 'menu-item-url' => $item[1], 'menu-item-status' => 'publish' ) );
		if ( is_wp_error( $result ) ) { throw new RuntimeException( 'Menu item creation failed: ' . $key ); }
	}
	return (int) $id;
}

/** Existing ACF options may have historical field keys, so resolve by name. */
function vwl_seed_option( string $name, $value ): void {
	$field = get_field_object( $name, 'option', false, false ) ?: acf_get_field( $name );
	if ( ! $field || empty( $field['key'] ) ) { throw new RuntimeException( 'ACF option field cannot be resolved: ' . $name ); }
	update_field( $field['key'], $value, 'option' );
	if ( (string) get_field( $name, 'option', false ) !== (string) $value ) { throw new RuntimeException( 'ACF option did not persist: ' . $name ); }
}

try {
	$state    = get_option( 'voiceweb_leather_seed_v1', array() );
	$deadline = (int) ( $state['deadline'] ?? ( time() + 7 * DAY_IN_SECONDS ) );
	if ( empty( $state['deadline'] ) ) {
		$state['deadline'] = $deadline;
		$state['started_at'] = current_time( 'mysql' );
		update_option( 'voiceweb_leather_seed_v1', $state, false );
	}
	$media = array();
	foreach ( $asset_specs as $key => $spec ) {
		$media[ $key ] = vwl_seed_image( $key, $spec );
	}
	$category_specs = array(
		'men-bags'      => array( 'کیف مردانه', 'رسمی، روزمره و همراه مسیر شما', 'men-black' ),
		'women-bags'    => array( 'کیف زنانه', 'سبک شخصی، با جزئیات فکرشده', 'women-cream' ),
		'men-wallets'   => array( 'کیف پول مردانه', 'مرتب و جمع‌وجور، برای هر روز', 'wallet-tan' ),
		'women-wallets' => array( 'کیف پول زنانه', 'جزئیات کوچک، انتخاب‌های زیبا', 'wallet-burgundy' ),
		'cardholders'   => array( 'جاکارتی', 'سبک‌تر همراهتان باشید', 'card-green' ),
		'accessories'   => array( 'اکسسوری چرمی', 'چیزهای کوچکِ دوست‌داشتنی', 'accessory-tan' ),
	);
	$categories = array();
	foreach ( $category_specs as $key => $spec ) {
		$categories[ $key ] = vwl_seed_term( $spec[0], 'leather-' . $key, 'product_cat', array( 'thumbnail_id' => $media[ $spec[2] ] ), $spec[1] );
	}
	$attributes = array( 'color' => vwl_seed_attribute( 'color', 'رنگ', 'color' ), 'size' => vwl_seed_attribute( 'size', 'اندازه', 'button' ) );
	$terms = array( 'color' => array(), 'size' => array() );
	foreach ( array( 'black' => array( 'مشکی', '#242522' ), 'tan' => array( 'عسلی', '#aa693b' ), 'cream' => array( 'کرم', '#e9decb' ), 'burgundy' => array( 'زرشکی', '#6c2935' ), 'green' => array( 'سبز تیره', '#253c33' ) ) as $slug => $spec ) {
		$terms['color'][ $slug ] = vwl_seed_term( $spec[0], $slug, 'pa_color', array( 'product_attribute_color' => $spec[1] ) );
	}
	$terms['size']['standard'] = vwl_seed_term( 'استاندارد', 'standard', 'pa_size' );
	$terms['size']['large'] = vwl_seed_term( 'بزرگ', 'large', 'pa_size' );
	$specs = array(
		'aria' => array( 'name' => 'کیف اداری چرم آریا', 'category' => 'men-bags', 'image' => 'men-black', 'gallery' => array( 'men-tan' ), 'colors' => array( 'black', 'tan' ), 'color_images' => array( 'black' => 'men-black', 'tan' => 'men-tan' ), 'regular' => 47900000, 'sale' => 39900000, 'weight' => 1.1, 'dimensions' => array( 38, 9, 29 ), 'order' => 1, 'short' => 'فرم مرتب، دستهٔ راحت و بند دوشی؛ برای رفت‌وآمدهای کاری و قرارهای روزمره.', 'intro' => 'آریا برای روزهایی طراحی شده که می‌خواهید وسایل همراهتان مرتب باشند و ظاهر شما یکدست بماند. فرم ایستاده و سادهٔ آن به‌راحتی با پوشش رسمی یا روزمره هماهنگ می‌شود.', 'details' => array( 'دو دسته برای حمل دستی و بند جداشدنی برای حمل روی شانه', 'محفظهٔ اصلی زیپی و فضای جلو برای وسایل کوچک', 'دو رنگ مشکی و عسلی و دو اندازهٔ قابل انتخاب', 'ابعاد اندازهٔ استاندارد حدود ۳۸ × ۲۹ × ۹ و اندازهٔ بزرگ حدود ۴۱ × ۳۱ × ۱۰ سانتی‌متر' ) ),
		'rona' => array( 'name' => 'کیف دستی چرم رونا', 'category' => 'women-bags', 'image' => 'women-cream', 'gallery' => array( 'women-black' ), 'colors' => array( 'cream', 'black' ), 'color_images' => array( 'cream' => 'women-cream', 'black' => 'women-black' ), 'regular' => 43800000, 'sale' => 38500000, 'weight' => 0.8, 'dimensions' => array( 28, 12, 22 ), 'order' => 2, 'short' => 'یک کیف خوش‌فرم برای برنامه‌های روزانه؛ رنگ‌های آرام، یراق ظریف و بند قابل جداشدن.', 'intro' => 'رونا با خط‌های نرم و فرم متعادل، همراهی برای روزهای شلوغ و لحظه‌های آرام است. دسته‌های منحنی و بند دوشی به شما اجازه می‌دهند شیوهٔ حمل را با برنامهٔ روزتان هماهنگ کنید.', 'details' => array( 'فرم نیمه‌ساختاریافته با دو دسته و بند جداشدنی', 'فضای اصلی برای کیف پول و وسایل ضروری روزانه', 'دو رنگ کرم و مشکی و دو اندازهٔ قابل انتخاب', 'ابعاد اندازهٔ استاندارد حدود ۲۸ × ۲۲ × ۱۲ و اندازهٔ بزرگ حدود ۳۲ × ۲۵ × ۱۳ سانتی‌متر' ) ),
		'hirad' => array( 'name' => 'کیف پول چرم هیراد', 'category' => 'men-wallets', 'image' => 'wallet-tan', 'regular' => 18800000, 'sale' => 15900000, 'weight' => 0.13, 'dimensions' => array( 11, 2, 9 ), 'order' => 3, 'short' => 'کیف پول تاشوی عسلی؛ فضای منظم کارت‌ها در ابعادی مناسب جیب.', 'intro' => 'هیراد با طراحی تاشو و فرم ساده، از آن همراه‌هایی است که هر روز به کار می‌آیند. رنگ عسلی بافت چرم و خط دوخت را نشان می‌دهد و به مرور با سبک استفادهٔ شما شخصیت می‌گیرد.', 'details' => array( 'فرم تاشوی کلاسیک با جای کارت و اسکناس', 'اندازهٔ بسته حدود ۱۱ × ۹ × ۲ سانتی‌متر', 'رنگ عسلی و دوخت هماهنگ با سطح چرم', 'مناسب حمل روزمره در جیب یا کیف' ) ),
		'ava' => array( 'name' => 'کیف پول چرم آوا', 'category' => 'women-wallets', 'image' => 'wallet-burgundy', 'regular' => 21500000, 'sale' => 18900000, 'weight' => 0.2, 'dimensions' => array( 19, 2, 10 ), 'order' => 4, 'short' => 'کیف پول پاکتی زرشکی با قفل ظریف؛ یک همراه مرتب برای کارت‌ها و پول نقد.', 'intro' => 'آوا رنگی عمیق و فرمی آرام دارد. طراحی پاکتی و قفل کوچک آن، وسایل ضروری را کنار هم نگه می‌دارد و به‌راحتی داخل کیف روزمره قرار می‌گیرد.', 'details' => array( 'در پاکتی با قفل ظریف و جای کارت', 'اندازهٔ بسته حدود ۱۹ × ۱۰ × ۲ سانتی‌متر', 'رنگ زرشکی با دوخت مرتب در امتداد لبه‌ها', 'فرم باریک برای قرارگرفتن در کیف دستی و دوشی' ) ),
		'sarv' => array( 'name' => 'جاکارتی چرم سرو', 'category' => 'cardholders', 'image' => 'card-green', 'regular' => 12800000, 'weight' => 0.06, 'dimensions' => array( 10, 0.8, 7 ), 'order' => 5, 'short' => 'جاکارتی سبک سبز تیره؛ فقط ضروری‌ها را همراه داشته باشید.', 'intro' => 'سرو برای زمانی است که به همراهی سبک‌تر از کیف پول نیاز دارید. طراحی باریک و دسترسی مستقیم به کارت‌ها، آن را به انتخابی ساده برای برنامه‌های روزمره تبدیل می‌کند.', 'details' => array( 'چند جای کارت با دسترسی مستقیم', 'ابعاد حدود ۱۰ × ۷ × ۰٫۸ سانتی‌متر', 'فرم کم‌حجم با دوخت ظریف', 'رنگ سبز تیره برای هماهنگی با کیف و اکسسوری' ) ),
		'mehr' => array( 'name' => 'کیف دوشی چرم مهر', 'category' => 'accessories', 'image' => 'accessory-tan', 'regular' => 29800000, 'inquiry' => true, 'weight' => 0.38, 'dimensions' => array( 18, 6, 15 ), 'order' => 6, 'short' => 'کیف دوشی کوچک عسلی؛ برای راهنمایی دربارهٔ این مدل، شمارهٔ خود را ثبت کنید.', 'intro' => 'مهر یک کیف کوچک برای حمل وسایل ضروری و سبک سفرکردن در شهر است. بند بلند و فرم جمع‌وجور، دست‌هایتان را برای برنامهٔ روزانه آزاد می‌گذارد.', 'details' => array( 'بند دوشی بلند و بدنهٔ کوچک با در پاکتی', 'ابعاد حدود ۱۸ × ۱۵ × ۶ سانتی‌متر', 'رنگ عسلی و یراق کوچک با ظاهر ساده', 'این مدل روی حالت درخواست تماس قرار دارد؛ ثبت فرم به معنی خرید، پرداخت یا رزرو موجودی نیست' ) ),
	);
	$products = array();
	foreach ( $specs as $key => $spec ) {
		$products[ $key ] = vwl_seed_product( $key, $spec, $media, $categories, $attributes, $terms, $deadline );
	}
	if ( empty( $state['variation_dimensions_written'] ) ) {
		// Complete an interrupted first import without changing any edited value.
		foreach ( array( 'aria', 'rona' ) as $key ) {
			$parent = wc_get_product( $products[ $key ] );
			foreach ( $parent->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( ! str_starts_with( (string) $variation->get_meta( '_voiceweb_leather_seed' ), 'variation:' . $key . ':' ) ) { continue; }
				$large = ( $variation->get_attributes()['pa_size'] ?? '' ) === 'large';
				$dimensions = $large ? ( 'aria' === $key ? array( 41, 10, 31 ) : array( 32, 13, 25 ) ) : $specs[ $key ]['dimensions'];
				foreach ( array( 'length', 'width', 'height' ) as $index => $name ) { if ( $variation->{'get_' . $name}( 'edit' ) === '' ) { $variation->{'set_' . $name}( (string) wc_get_dimension( $dimensions[ $index ], get_option( 'woocommerce_dimension_unit', 'cm' ), 'cm' ) ); } }
				if ( $variation->get_weight( 'edit' ) === '' ) { $variation->set_weight( (string) wc_get_weight( $specs[ $key ]['weight'] + ( $large ? 0.1 : 0 ), get_option( 'woocommerce_weight_unit', 'kg' ), 'kg' ) ); }
				$variation->save();
			}
		}
		$state['variation_dimensions_written'] = true;
	}
	$blog_category = vwl_seed_term( 'مجلهٔ چرم و سبک زندگی', 'leather-journal', 'category', array(), 'راهنماهای ساده برای انتخاب و همراهی بهتر با محصولات چرمی' );
	$blog_specs = array(
		'choose-bag' => array( 'چطور کیف مناسب روزمره‌مان را انتخاب کنیم؟', 'men-black', '<p>کیفی که هر روز همراهتان است بهتر است با برنامهٔ شما هماهنگ باشد. پیش از نگاه‌کردن به رنگ یا فرم، فهرستی از وسایلی بنویسید که واقعاً با خود حمل می‌کنید.</p><h2>اول اندازه، بعد ظاهر</h2><p>کیف بزرگ همیشه کاربردی‌تر نیست. اگر بیشتر زمانتان بیرون از خانه است، وزن کیف و شیوهٔ حمل را هم در نظر بگیرید. برای وسایل حساس، وجود فضای مناسب را جداگانه بررسی کنید و صرفاً به ظاهر کیف تکیه نکنید.</p><h2>دسته یا بند دوشی؟</h2><p>دسته برای مسیرهای کوتاه و قرارهای رسمی کاربردی است؛ بند دوشی می‌تواند حمل وسایل را در مسیرهای طولانی راحت‌تر کند. طول بند و دسترسی به زیپ‌ها را هنگام انتخاب مرور کنید.</p><h2>رنگی برای برنامهٔ شما</h2><p>مشکی و کرم به‌راحتی با پوشش‌های متفاوت هماهنگ می‌شوند. عسلی و سبز تیره می‌توانند جزئیات شاخص یک پوشش ساده باشند. رنگ دلخواه خود را کنار لباس‌هایی که بیشتر می‌پوشید تصور کنید.</p><h2>جزئیات را بخوانید</h2><p>ابعاد، وزن و گزینه‌های هر مدل در صفحهٔ محصول آمده‌اند. قبل از خرید آن‌ها را با نیاز خود مقایسه کنید و اگر سؤال دارید از فرم درخواست راهنمایی کمک بگیرید.</p>' ),
		'care' => array( 'چند عادت ساده برای نگهداری بهتر از چرم', 'craft', '<p>رسیدگی به محصولات چرمی از چند عادت روزمره شروع می‌شود. شرایط نگهداری و نوع پوشش چرم می‌توانند متفاوت باشند؛ دستور مراقبت محصول خود را مبنا قرار دهید.</p><h2>تمیزکردن آرام</h2><p>برای گردوغبار از پارچهٔ نرم و خشک استفاده کنید. روی لکه‌ها محکم نکشید و از شویندهٔ خانگی یا الکل بدون بررسی سازگاری استفاده نکنید. اگر لکه باقی ماند، از فروشنده یا متخصص نگهداری همان نوع چرم راهنمایی بگیرید.</p><h2>رطوبت و گرمای مستقیم</h2><p>اگر سطح خیس شد، رطوبت را آرام با پارچه بردارید و اجازه دهید در فضای دارای جریان هوا خشک شود. سشوار، بخاری یا آفتاب مستقیم می‌تواند به سطح و فرم آسیب بزند.</p><h2>نگهداری بین دو استفاده</h2><p>کیف را بیش از ظرفیت پر نکنید. هنگام نگهداری طولانی، برای حفظ فرم از پُرکنندهٔ نرم و تمیز استفاده کنید و در کاور تنفس‌پذیر، دور از رطوبت نگه دارید.</p><h2>محصول مراقبتی مناسب</h2><p>روغن یا واکس برای هر چرمی مناسب نیست. دستور سازنده را بررسی و محصول مراقبتی سازگار را ابتدا روی قسمت کوچکی دور از دید آزمایش کنید. این نوشته جایگزین دستور اختصاصی محصول یا ترمیم حرفه‌ای نیست.</p>' ),
		'light-carry' => array( 'کیف پول یا جاکارتی؟ یک انتخاب برای سبک‌تر همراه‌بودن', 'card-green', '<p>کم‌کردن وسایل همراه، گاهی برنامهٔ روزانه را ساده‌تر می‌کند. انتخاب بین کیف پول و جاکارتی به تعداد کارت‌ها، میزان پول نقد و شیوهٔ حمل شما بستگی دارد.</p><h2>چه چیزهایی ضروری‌اند؟</h2><p>کارت‌هایی را که هر روز استفاده می‌کنید جدا کنید. رسیدها و کارت‌های کم‌کاربرد ممکن است بدون نیاز فضای کیف پول را پُر کنند. پاک‌سازی دوره‌ای وسایل، انتخاب اندازه را هم راحت‌تر می‌کند.</p><h2>وقتی جاکارتی کافی است</h2><p>اگر بیشتر پرداخت‌هایتان با چند کارت انجام می‌شود، جاکارتی باریک می‌تواند گزینه‌ای سبک باشد. تعداد جای کارت و راحتی بیرون‌آوردن کارت را قبل از انتخاب بررسی کنید.</p><h2>وقتی کیف پول کاربردی‌تر است</h2><p>برای حمل اسکناس، کارت‌های بیشتر یا مدارک کوچک، کیف پول با تقسیم‌بندی مناسب انتخاب راحت‌تری است. ابعاد بسته را با جیب یا کیف خود مقایسه کنید.</p><h2>هماهنگی ساده</h2><p>لازم نیست همهٔ اکسسوری‌ها دقیقاً هم‌رنگ باشند. یک رنگ آرام در کنار کیف اصلی می‌تواند ترکیبی ساده و شخصی بسازد. مدل‌ها و اندازه‌های هر دسته را در فروشگاه ببینید و مطابق شیوهٔ استفادهٔ خود انتخاب کنید.</p>' ),
	);
	$posts = array();
	foreach ( $blog_specs as $key => $spec ) {
		$id = vwl_seed_post_id( 'post:' . $key, 'post' );
		if ( ! $id ) {
			$id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_author' => (int) get_post_field( 'post_author', $page ), 'post_title' => $spec[0], 'post_name' => 'leather-' . $key, 'post_content' => $spec[2], 'post_excerpt' => wp_trim_words( wp_strip_all_tags( $spec[2] ), 30 ), 'post_category' => array( $blog_category ), 'meta_input' => array( '_voiceweb_leather_seed' => 'post:' . $key ) ), true );
			if ( is_wp_error( $id ) ) { throw new RuntimeException( 'Blog post creation failed: ' . $key ); }
			set_post_thumbnail( $id, $media[ $spec[1] ] );
		}
		$posts[] = (int) $id;
	}
	$about = vwl_seed_post_id( 'page:about', 'page' );
	$existing_about = get_page_by_path( 'about-us' ) ?: get_page_by_path( 'about' );
	if ( ! $about && $existing_about instanceof WP_Post && 'publish' === $existing_about->post_status ) { $about = (int) $existing_about->ID; }
	if ( ! $about ) {
		$about = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'دربارهٔ ویس وب', 'post_name' => 'about-us', 'post_content' => '<h2>جزئیات، سبک زندگی شماست</h2><p>ویس وب مجموعه‌ای از کیف‌ها، کیف پول‌ها و اکسسوری‌های چرمی را با نگاهی ساده و کاربردی کنار هم می‌گذارد. برای ما، انتخاب خوب از شناخت نیاز شروع می‌شود: چیزی که راحت همراهتان باشد و با سبک شخصی‌تان هماهنگ شود.</p><h2>انتخابی برای هر روز</h2><p>از فرم یک کیف اداری تا اندازهٔ یک جاکارتی، جزئیات کوچک روی تجربهٔ روزمره اثر دارند. تلاش می‌کنیم اطلاعات محصول روشن باشد و رنگ و اندازه را پیش از خرید به‌راحتی بررسی کنید.</p><h2>اگر برای انتخاب سؤال دارید</h2><p>در صفحهٔ محصول می‌توانید راهنمایی بخواهید. شمارهٔ خود را ثبت کنید تا دربارهٔ مدل موردنظر با شما تماس بگیریم. ثبت این درخواست به معنی خرید یا پرداخت نیست.</p>', 'meta_input' => array( '_voiceweb_leather_seed' => 'page:about', '_wp_page_template' => 'page-templates/about.php' ) ), true );
		if ( is_wp_error( $about ) ) { throw new RuntimeException( 'About page creation failed.' ); }
		set_post_thumbnail( $about, $media['craft'] );
	}
	if ( empty( $state['about_template_written'] ) ) {
		update_post_meta( $about, '_wp_page_template', 'page-templates/about.php' );
		$state['about_template_written'] = true;
	}
	if ( empty( $state['about_content_written'] ) ) {
		if ( trim( (string) get_post_field( 'post_content', $about ) ) === '' ) {
			wp_update_post( array( 'ID' => $about, 'post_content' => '<h2>جزئیات، سبک زندگی شماست</h2><p>ویس وب مجموعه‌ای از کیف‌ها، کیف پول‌ها و اکسسوری‌های چرمی را با نگاهی ساده و کاربردی کنار هم می‌گذارد. برای ما، انتخاب خوب از شناخت نیاز شروع می‌شود: چیزی که راحت همراهتان باشد و با سبک شخصی‌تان هماهنگ شود.</p><h2>انتخابی برای هر روز</h2><p>از فرم یک کیف اداری تا اندازهٔ یک جاکارتی، جزئیات کوچک روی تجربهٔ روزمره اثر دارند. تلاش می‌کنیم اطلاعات محصول روشن باشد و رنگ و اندازه را پیش از خرید به‌راحتی بررسی کنید.</p><h2>جزئیاتی که می‌توانید بررسی کنید</h2><p>عکس‌های محصول، توضیح کوتاه و ابعاد به شما کمک می‌کنند انتخابی آگاهانه داشته باشید. در مدل‌های متغیر، رنگ و اندازه را انتخاب کنید تا قیمت و اطلاعات همان گزینه را ببینید. پیش از ثبت سفارش، نشانی تحویل و جزئیات سبد را مرور کنید.</p><h2>اگر برای انتخاب سؤال دارید</h2><p>در صفحهٔ محصول می‌توانید راهنمایی بخواهید. شمارهٔ خود را ثبت کنید تا دربارهٔ مدل موردنظر با شما تماس بگیریم. ثبت این درخواست به معنی خرید، پرداخت یا رزرو موجودی نیست.</p>' ) );
			update_post_meta( $about, '_voiceweb_leather_seed', 'page:about' );
			if ( ! has_post_thumbnail( $about ) ) { set_post_thumbnail( $about, $media['craft'] ); }
		}
		$state['about_content_written'] = true;
	}
	$shop = wc_get_page_permalink( 'shop' );
	$link = static fn( $url, $title ) => array( 'url' => $url, 'title' => $title, 'target' => '' );
	$term_url = static fn( $key ) => get_term_link( $categories[ $key ], 'product_cat' );
	if ( empty( $state['menus_written'] ) ) {
		$main_menu = vwl_seed_menu( 'main', 'ویس وب | فروشگاه چرم', array( array( 'خانه', home_url( '/' ) ), array( 'فروشگاه', $shop ), array( 'منتخب مردانه', $term_url( 'men-bags' ) ), array( 'منتخب زنانه', $term_url( 'women-bags' ) ), array( 'مجله', get_term_link( $blog_category, 'category' ) ), array( 'دربارهٔ ما', get_permalink( $about ) ) ) );
		$category_items = array();
		foreach ( $category_specs as $key => $spec ) { $category_items[] = array( $spec[0], $term_url( $key ) ); }
		$footer_categories = vwl_seed_menu( 'categories', 'دسته‌بندی محصولات', $category_items );
		$footer_links = vwl_seed_menu( 'links', 'همراه خرید', array( array( 'حساب کاربری', wc_get_page_permalink( 'myaccount' ) ), array( 'سبد خرید', wc_get_cart_url() ), array( 'مجلهٔ چرم', get_term_link( $blog_category, 'category' ) ), array( 'دربارهٔ ما', get_permalink( $about ) ) ) );
		foreach ( array( 'cn_header_menu' => $main_menu, 'cn_header_mobile_menu' => $main_menu, 'cn_footer_col1_menu' => $footer_categories, 'cn_footer_col2_menu' => $footer_links ) as $field_name => $menu_id ) { vwl_seed_option( $field_name, $menu_id ); }
		$state['menus'] = array( 'main' => $main_menu, 'categories' => $footer_categories, 'links' => $footer_links );
		$state['menus_written'] = true;
	}
	if ( empty( $state['header_migration_written'] ) ) {
		$menu_id = (int) ( $state['menus']['main'] ?? 0 );
		if ( ! $menu_id || get_term_meta( $menu_id, '_voiceweb_leather_seed', true ) !== 'menu:main' ) { throw new RuntimeException( 'The owned leather header menu was not found.' ); }
		vwl_seed_option( 'cn_header_menu', $menu_id );
		vwl_seed_option( 'cn_header_mobile_menu', $menu_id );
		foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
			if ( $item->title === 'مجله' ) { wp_update_post( array( 'ID' => $item->ID, 'post_title' => 'مجلهٔ چرم' ) ); }
		}
		$state['header_migration_written'] = true;
	}
	if ( empty( $state['footer_written'] ) ) {
		vwl_seed_option( 'cn_footer_description', 'کیف‌ها، کیف پول‌ها و اکسسوری‌های چرمی برای روزهایی که جزئیات اهمیت دارند. انتخابی فکرشده برای سبک شخصی شما.' );
		foreach ( array( 'categories' => 'دسته‌بندی محصولات', 'links' => 'همراه خرید' ) as $key => $label ) {
			$id = (int) ( $state['menus'][ $key ] ?? 0 );
			if ( $id && get_term_meta( $id, '_voiceweb_leather_seed', true ) === 'menu:' . $key ) { wp_update_term( $id, 'nav_menu', array( 'name' => $label ) ); }
		}
		$state['footer_written'] = true;
	}
	if ( empty( $state['homepage_written'] ) ) {
		$fields = array(
			'vw_front_slides' => array(
				array( 'eyebrow' => 'LEATHER COLLECTION', 'title' => 'جزئیات، سبک زندگی شماست', 'description' => 'کیف‌ها، کیف پول‌ها و اکسسوری‌های چرمی؛ انتخاب‌هایی برای همراهی با روزهای شما.', 'image' => $media['hero'], 'mobile_image' => $media['hero'], 'primary' => $link( $shop, 'مشاهدهٔ محصولات' ), 'secondary' => $link( get_permalink( $about ), 'داستان ما' ), 'tone' => 'ice', 'floating_images' => array( array( 'image' => $media['wallet-burgundy'] ), array( 'image' => $media['card-green'] ) ) ),
				array( 'eyebrow' => 'EVERYDAY DETAILS', 'title' => 'سبک‌تر، مرتب‌تر، همراه‌تر', 'description' => 'کیف پول و جاکارتی‌هایی که فقط ضروری‌ها را همراهتان می‌آورند.', 'image' => $media['wallet-tan'], 'mobile_image' => $media['wallet-tan'], 'primary' => $link( $term_url( 'men-wallets' ), 'انتخاب کیف پول' ), 'secondary' => $link( $term_url( 'cardholders' ), 'دیدن جاکارتی‌ها' ), 'tone' => 'ice' ),
			),
			'vw_front_autoplay' => 0,
			'vw_front_interval' => 7,
			'vw_front_header_note' => 'کیف و اکسسوری‌های چرمی؛ همراه جزئیات روزمرهٔ شما',
			'vw_front_offers_enabled' => 1,
			'vw_front_offers_title' => 'پیشنهادهای ویژه',
			'vw_front_offers_description' => 'چند انتخاب دوست‌داشتنی، با قیمت ویژه تا پایان زمان مشخص‌شده.',
			'vw_front_offers_products' => array_values( array_intersect_key( $products, array_flip( array( 'aria', 'rona', 'hirad', 'ava' ) ) ) ),
			'vw_front_offers_deadline' => wp_date( 'Y-m-d H:i:s', $deadline, wp_timezone() ),
			'vw_front_categories_enabled' => 1,
			'vw_front_categories_title' => 'دسته‌بندی محصولات',
			'vw_front_categories' => array_map( static fn( $key, $spec ) => array( 'term' => $categories[ $key ], 'label' => $spec[0], 'description' => $spec[1], 'image' => $media[ $spec[2] ] ), array_keys( $category_specs ), array_values( $category_specs ) ),
			'vw_front_blog_enabled' => 1,
			'vw_front_blog_title' => 'مجلهٔ چرم و سبک زندگی',
			'vw_front_blog_description' => 'ایده‌های ساده برای انتخاب، استفاده و نگهداری بهتر.',
			'vw_front_blog_posts' => $posts,
			'vw_front_blog_link' => $link( get_term_link( $blog_category, 'category' ), 'همهٔ نوشته‌ها' ),
			'vw_front_arrivals_enabled' => 1,
			'vw_front_arrivals_title' => 'همراه‌های تازهٔ شما',
			'vw_front_arrivals_products' => array_values( $products ),
			'vw_front_promos' => array(
				array( 'title' => 'منتخب مردانه', 'description' => 'فرم مرتب و جزئیات کاربردی برای مسیر هر روز', 'image' => $media['men-black'], 'link' => $link( $term_url( 'men-bags' ), 'مشاهدهٔ کالکشن' ), 'tone' => 'ink' ),
				array( 'title' => 'منتخب زنانه', 'description' => 'رنگ‌های آرام و فرم‌هایی برای سبک شخصی شما', 'image' => $media['women-cream'], 'link' => $link( $term_url( 'women-bags' ), 'مشاهدهٔ کالکشن' ), 'tone' => 'ice' ),
			),
			'vw_front_story_title' => 'فراتر از یک کیف',
			'vw_front_story_description' => 'یک همراه خوب از جزئیات ساخته می‌شود؛ از انتخاب اندازه تا حس چرم در دست و نظمی که به وسایل روزمره می‌دهد. محصولی را انتخاب کنید که با زندگی شما هماهنگ باشد.',
			'vw_front_story_image' => $media['wallet-burgundy'],
			'vw_front_story_link' => $link( $shop, 'همراه خود را پیدا کنید' ),
			'vw_front_about_title' => 'هنر در کنار شماست',
			'vw_front_about_description' => 'از خط یک دوخت تا فرم یک دسته، جزئیات کوچک تجربهٔ استفاده را می‌سازند. ویس وب جایی برای انتخاب‌های فکرشده و همراه‌های روزمره است.',
			'vw_front_about_image' => $media['craft'],
			'vw_front_about_link' => $link( get_permalink( $about ), 'بیشتر دربارهٔ ما' ),
			'vw_front_collection_title' => 'استایل روزمره، با امضای چرم',
			'vw_front_collection_description' => 'کیف‌ها و اکسسوری‌هایی که به‌سادگی با برنامهٔ روزانهٔ شما همراه می‌شوند.',
			'vw_front_collection_image' => $media['collection'],
			'vw_front_collection_link' => $link( $shop, 'مشاهدهٔ کالکشن جدید' ),
			'vw_front_benefits' => array(
				array( 'title' => 'انتخاب روشن', 'description' => 'رنگ، اندازه و اطلاعات محصول پیش از خرید', 'icon' => 'check' ),
				array( 'title' => 'همراه روزمره', 'description' => 'مدل‌های کاربردی برای سبک‌های متفاوت', 'icon' => 'shield' ),
				array( 'title' => 'راهنمای انتخاب', 'description' => 'درخواست تماس برای سؤال دربارهٔ محصول', 'icon' => 'user' ),
			),
			'vw_front_faq_title' => 'پیش از انتخاب، بدانید',
			'vw_front_faq' => array(
				array( 'question' => 'چطور رنگ و اندازهٔ محصول را انتخاب کنم؟', 'answer' => 'در صفحهٔ محصول‌های دارای تنوع، ابتدا رنگ و اندازه را انتخاب کنید. عکس، قیمت و موجودی همان گزینه نمایش داده می‌شود؛ سپس می‌توانید آن را به سبد اضافه کنید.' ),
				array( 'question' => 'ثبت درخواست تماس به معنی خرید است؟', 'answer' => 'خیر. درخواست تماس برای راهنمایی دربارهٔ محصول است و پرداخت یا رزرو موجودی ایجاد نمی‌کند. بعد از بررسی درخواست، دربارهٔ مدل انتخاب‌شده با شما تماس می‌گیریم.' ),
				array( 'question' => 'چطور نشانی تحویل را انتخاب کنم؟', 'answer' => 'در حساب کاربری می‌توانید چند آدرس ذخیره کنید و هنگام تسویه‌حساب یکی را برای سفارش انتخاب کنید. اطلاعات ارسال را پیش از ثبت نهایی بررسی کنید.' ),
			),
		);
		foreach ( $fields as $name => $value ) {
			$field = acf_get_field( 'field_' . $name );
			if ( ! $field ) { throw new RuntimeException( 'Missing current ACF field: ' . $name ); }
			update_field( $field['key'], $value, $page );
		}
		$state['homepage_written'] = true;
		update_post_meta( $page, '_voiceweb_leather_seed', 'homepage:v1' );
	}
	if ( empty( $state['collection_image_written'] ) ) {
		$current_image = (int) get_field( 'vw_front_collection_image', $page, false );
		if ( ! $current_image || $current_image === (int) $media['men-tan'] ) { update_field( 'field_vw_front_collection_image', $media['collection'], $page ); }
		$state['collection_image_written'] = true;
	}
	if ( empty( $state['blog_link_written'] ) ) {
		if ( ! get_field( 'vw_front_blog_link', $page, false ) ) { update_field( 'field_vw_front_blog_link', $link( get_term_link( $blog_category, 'category' ), 'همهٔ نوشته‌ها' ), $page ); }
		$state['blog_link_written'] = true;
	}
	$state['products'] = $products;
	$state['categories'] = $categories;
	$state['media'] = $media;
	$state['posts'] = $posts;
	$state['about_page'] = (int) $about;
	$state['completed_at'] = current_time( 'mysql' );
	update_option( 'voiceweb_leather_seed_v1', $state, false );
	delete_transient( 'wc_products_onsale' );
	flush_rewrite_rules( false );
	echo wp_json_encode( array( 'mode' => 'applied', 'front_page' => $page, 'state' => $state, 'preserved_existing_content' => true ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n";
} catch ( Throwable $error ) {
	fwrite( STDERR, 'Seed stopped safely: ' . $error->getMessage() . "\n" );
	exit( 1 );
}
