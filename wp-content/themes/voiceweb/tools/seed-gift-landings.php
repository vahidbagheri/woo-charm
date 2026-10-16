<?php
/**
 * Create gift guides once, preserving every later editorial change.
 * CLI: php -d mysqli.default_port=10113 .../seed-gift-landings.php [--apply] [--publish-local]
 * --apply creates drafts by default. Publishing here is restricted to local development hosts.
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-gift-seed' );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
$_SERVER['REQUEST_URI'] = '/';
$GLOBALS['wp_filter']['pre_http_request'][PHP_INT_MAX]['voiceweb_gift_seed_offline'] = array( 'function' => static fn() => new WP_Error( 'gift_seed_no_network', 'External requests disabled.' ), 'accepted_args' => 3 );
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX]['voiceweb_gift_seed_no_mail'] = array( 'function' => static fn() => false, 'accepted_args' => 2 );
require dirname( __DIR__, 4 ) . '/wp-load.php';
add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
add_filter( 'pre_http_request', static fn() => new WP_Error( 'gift_seed_no_network', 'External requests disabled.' ), PHP_INT_MAX );

if ( ! function_exists( 'update_field' ) || ! function_exists( 'voiceweb_gift_products' ) || ! function_exists( 'wc_get_products' ) ) {
	fwrite( STDERR, "VoiceWeb, ACF and WooCommerce must be active.\n" ); exit( 1 );
}
$apply = in_array( '--apply', $argv, true );
$publish = in_array( '--publish-local', $argv, true );
$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
if ( $publish && ! preg_match( '/(?:\.local|\.test)$|^localhost$|^127\.0\.0\.1$/i', $host ) ) {
	fwrite( STDERR, "--publish-local only supports a local development site.\n" ); exit( 1 );
}
$specs = require dirname( __DIR__ ) . '/config/gift-occasions.php';
$status = $publish ? 'publish' : 'draft';

function vwg_seed_find( string $key ): int {
	$ids = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ), 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_voiceweb_gift_seed', 'meta_value' => $key, 'suppress_filters' => true ) );
	return (int) ( $ids[0] ?? 0 );
}
function vwg_seed_fields( int $id, array $values ): void {
	foreach ( $values as $name => $value ) { update_field( 'field_vw_gift_' . $name, $value, $id ); }
}
function vwg_seed_create( string $key, array $post ): int {
	$post['post_type'] = 'page';
	$post['comment_status'] = 'closed';
	$post['ping_status'] = 'closed';
	$post['meta_input']['_voiceweb_gift_seed'] = $key;
	$id = wp_insert_post( wp_slash( $post ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	return (int) $id;
}

$errors = array();
$resolved = array();
$hub_id = vwg_seed_find( 'hub' );
$collision = get_page_by_path( 'gifts' );
if ( $collision && (int) $collision->ID !== $hub_id ) { $errors[] = 'An unowned /gifts/ page already exists; it has not been modified.'; }
foreach ( $specs as $slug => $spec ) {
	$existing = vwg_seed_find( $slug );
	$collision = get_page_by_path( 'gifts/' . $slug );
	if ( $collision && (int) $collision->ID !== $existing ) { $errors[] = 'Unowned path: gifts/' . $slug; }
	$terms = array();
	foreach ( $spec['category_keys'] as $term_slug ) {
		$term = get_term_by( 'slug', $term_slug, 'product_cat' );
		if ( ! $term ) { $errors[] = 'Missing product category: ' . $term_slug; }
		else { $terms[] = $term->term_id; }
	}
	$images = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_voiceweb_leather_seed', 'meta_value' => 'asset:' . $spec['image_key'], 'suppress_filters' => true ) );
	$resolved[ $slug ] = array( 'existing' => $existing, 'categories' => $terms, 'image' => (int) ( $images[0] ?? 0 ) );
}
if ( $errors ) { fwrite( STDERR, implode( "\n", array_unique( $errors ) ) . "\n" ); exit( 1 ); }
if ( ! $apply ) {
	echo wp_json_encode( array( 'mode' => 'dry-run', 'home' => home_url(), 'new_status' => $status, 'existing_hub' => $hub_id, 'planned_pages' => array_map( static fn( $slug, $row ) => array( 'slug' => $slug, 'existing' => $row['existing'], 'categories' => $row['categories'], 'image' => $row['image'] ), array_keys( $resolved ), array_values( $resolved ) ), 'preserves_existing_content' => true ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
	exit;
}
if ( ! acf_get_field( 'field_vw_gift_intro' ) ) { fwrite( STDERR, "Gift ACF schema is not registered.\n" ); exit( 1 ); }

try {
	if ( ! $hub_id ) {
		$hub_id = vwg_seed_create( 'hub', array(
			'post_title' => 'راهنمای خرید هدیه برای مناسبت‌ها', 'post_name' => 'gifts', 'post_status' => $status,
			'post_excerpt' => 'از روز مادر و روز پدر تا نوروز و تولد؛ ایده‌های هدیه با کیف، کیف پول و جاکارتی را متناسب با سلیقه و نیاز عزیزانتان مقایسه کنید.',
			'post_content' => '<p>برای کسی که دوستش دارید، از زندگی روزمره‌اش شروع کنید. یک کیف همراه کار، کیف پول برای وسایل ضروری یا جاکارتی کم‌حجم، هرکدام برای نیاز متفاوتی مناسب‌اند. در راهنماهای زیر، پیشنهادهای هر مناسبت را کنار نکته‌های انتخاب و محصولات مرتبط می‌بینید.</p><p>مناسبت را انتخاب کنید، مدل‌ها را مقایسه کنید و پیش از سفارش، مشخصات و موجودی رنگ دلخواه را در صفحهٔ محصول بخوانید.</p>',
			'meta_input' => array( '_wp_page_template' => 'page-templates/gift-hub.php' ),
		) );
		vwg_seed_fields( $hub_id, array( 'seo_title' => 'خرید هدیه برای مناسبت‌ها | راهنمای کیف و کیف پول', 'seo_description' => 'برای روز مادر، روز پدر، روز زن، نوروز، یلدا و تولد هدیه انتخاب کنید؛ راهنمای خرید کیف و کیف پول همراه با محصولات مرتبط و نکته‌های کاربردی.' ) );
	}
	$ids = array(); $created = array();
	foreach ( $specs as $slug => $spec ) {
		$id = $resolved[ $slug ]['existing'];
		if ( ! $id ) {
			$id = vwg_seed_create( $slug, array(
				'post_title' => $spec['title'], 'post_name' => $slug, 'post_parent' => $hub_id, 'post_status' => $status,
				'post_content' => $spec['content'], 'post_excerpt' => $spec['excerpt'], 'menu_order' => $spec['priority'] * 10 + count( $ids ),
				'meta_input' => array( '_wp_page_template' => 'page-templates/gift-landing.php' ),
			) );
			$fields = array_intersect_key( $spec, array_flip( array( 'intro', 'eyebrow', 'seo_title', 'seo_description', 'products_title', 'guide_title', 'guide_intro', 'tips', 'faq' ) ) );
			vwg_seed_fields( $id, array_merge( $fields, array( 'hero_image' => $resolved[ $slug ]['image'], 'primary_label' => 'دیدن پیشنهادهای هدیه', 'product_source' => 'categories', 'categories' => $resolved[ $slug ]['categories'], 'product_limit' => 12, 'product_order' => 'date', 'in_stock' => 1 ) ) );
			if ( $resolved[ $slug ]['image'] ) { set_post_thumbnail( $id, $resolved[ $slug ]['image'] ); }
			$created[ $slug ] = $id;
		}
		$ids[ $slug ] = $id;
	}
	foreach ( $created as $slug => $id ) {
		$related = array_values( array_filter( array_map( static fn( $key ) => $ids[ $key ] ?? 0, $specs[ $slug ]['related'] ) ) );
		vwg_seed_fields( $id, array( 'related_pages' => $related ) );
		// Native category links belong in the editable article as well as the collection controls.
		$links = array();
		foreach ( voiceweb_gift_categories( $id ) as $term ) { $url = get_term_link( $term ); if ( ! is_wp_error( $url ) ) { $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>'; } }
		if ( $links ) { wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $specs[ $slug ]['content'] . '<p>برای مقایسهٔ مدل‌های بیشتر: ' . implode( '، ', $links ) . '.</p>' ) ) ); }
	}
	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id ) {
		foreach ( array( 'seo_title' => 'خرید کیف چرم و کیف پول مردانه و زنانه', 'seo_description' => 'مدل‌های کیف چرم مردانه و زنانه، کیف پول و جاکارتی را مقایسه کنید؛ راهنمای انتخاب برای استفادهٔ روزمره و خرید هدیه در مناسبت‌های مختلف.' ) as $key => $value ) {
			if ( ! metadata_exists( 'post', $front_id, 'vw_front_' . $key ) ) { update_field( 'field_vw_front_' . $key, $value, $front_id ); }
		}
		// Upgrade only the known starter headline; never replace an editor's own copy.
		$slides = voiceweb_front_value( 'slides', array() );
		if ( ! get_post_meta( $front_id, '_voiceweb_gift_home_copy_v1', true ) && is_array( $slides ) && ( $slides[0]['title'] ?? '' ) === 'جزئیات، سبک زندگی شماست' ) {
			$slides[0]['title'] = "کیف و کیف پول چرم،\nبرای روزهای شما";
			update_field( 'field_vw_front_slides', $slides, $front_id );
			update_post_meta( $front_id, '_voiceweb_gift_home_copy_v1', 1 );
		}
	}
	$result = array();
	foreach ( $ids as $slug => $id ) { $result[] = array( 'id' => $id, 'slug' => $slug, 'status' => get_post_status( $id ), 'created' => isset( $created[ $slug ] ), 'products' => count( voiceweb_gift_products( $id ) ), 'url' => get_permalink( $id ) ); }
	echo wp_json_encode( array( 'hub' => get_permalink( $hub_id ), 'created' => count( $created ), 'pages' => $result ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
} catch ( Throwable $error ) { fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 ); }
