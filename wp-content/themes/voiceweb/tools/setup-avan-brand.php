<?php
/** One-time local brand/content setup. Dry-run by default; preserves later editorial changes. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'DISABLE_WP_CRON', true );
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/avan-brand-setup' );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-charm-main.local';
$_SERVER['REQUEST_URI'] = '/';
$GLOBALS['wp_filter']['pre_http_request'][PHP_INT_MAX][] = array( 'function' => static fn() => new WP_Error( 'offline', 'External requests disabled.' ), 'accepted_args' => 3 );
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array( 'function' => static fn() => false, 'accepted_args' => 2 );
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! function_exists( 'update_field' ) || ! function_exists( 'wc_get_products' ) ) { fwrite( STDERR, "ACF and WooCommerce are required.\n" ); exit( 1 ); }
$apply = in_array( '--apply', $argv, true );
$preview_local = in_array( '--preview-local', $argv, true );
$site_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
if ( ( $apply || $preview_local ) && ! preg_match( '/(?:\.local|\.test)$|^localhost$|^127\.0\.0\.1$/i', $site_host ) ) { fwrite( STDERR, "This setup writes only to a local development site.\n" ); exit( 1 ); }
if ( $preview_local ) {
	if ( ! get_option( 'voiceweb_avan_brand_version' ) ) { fwrite( STDERR, "Apply the brand content before enabling local preview.\n" ); exit( 1 ); }
	$backup = get_option( 'voiceweb_avan_brand_backup_v1', array() );
	if ( ! isset( $backup['options'] ) ) { $backup['options'] = array(); }
	if ( ! array_key_exists( 'woocommerce_coming_soon', $backup['options'] ) ) {
		$backup['options']['woocommerce_coming_soon'] = get_option( 'woocommerce_coming_soon' );
		update_option( 'voiceweb_avan_brand_backup_v1', $backup, false );
	}
	update_option( 'woocommerce_coming_soon', 'no' );
	echo wp_json_encode( array( 'mode' => 'local-preview-enabled', 'home' => home_url(), 'coming_soon' => get_option( 'woocommerce_coming_soon' ), 'blog_public' => get_option( 'blog_public' ) ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n";
	exit;
}
$brand_spec = require dirname( __DIR__ ) . '/config/avan-brand-content.php';
$category_spec = require dirname( __DIR__ ) . '/config/category-landings.php';
$gift_spec = require dirname( __DIR__ ) . '/config/gift-occasions.php';
$front = (int) get_option( 'page_on_front' );
$page_ids = array();
foreach ( array( 'about', 'contact' ) as $kind ) {
	$page = get_page_by_path( $kind );
	$page_ids[ $kind ] = $page ? (int) $page->ID : 0;
}
$terms = array();
foreach ( $category_spec as $slug => $spec ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term ) { fwrite( STDERR, 'Missing product category: ' . $slug . "\n" ); exit( 1 ); }
	$terms[ $slug ] = $term;
}
$gifts = array();
foreach ( array_keys( $gift_spec ) as $slug ) { $page = get_page_by_path( 'gifts/' . $slug ); if ( $page ) { $gifts[ $slug ] = (int) $page->ID; } }
$main_menu = cn_get_site_menu_id( 'cn_header_menu' );
$footer_menus = cn_get_footer_menu_columns();

function avan_setup_fields( string $prefix, $object_id, array $fields ): void {
	foreach ( $fields as $name => $value ) { update_field( 'field_' . $prefix . $name, $value, $object_id ); }
}
function avan_setup_image( string $key ): int {
	$ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_voiceweb_leather_seed', 'meta_value' => 'asset:' . $key, 'suppress_filters' => true ) );
	return (int) ( $ids[0] ?? 0 );
}
function avan_setup_menu_link( int $menu, string $title, string $object, int $object_id, string $url ): void {
	if ( ! $menu || ! $object_id ) { return; }
	$found = null;
	foreach ( wp_get_nav_menu_items( $menu ) ?: array() as $item ) {
		if ( untrailingslashit( $item->url ) === untrailingslashit( $url ) || ( $item->object === $object && (int) $item->object_id === $object_id ) ) { $found = $item; break; }
	}
	$args = array( 'menu-item-title' => $title, 'menu-item-type' => $object === 'product_cat' ? 'taxonomy' : 'post_type', 'menu-item-object' => $object, 'menu-item-object-id' => $object_id, 'menu-item-status' => 'publish' );
	if ( $found ) { $args['menu-item-position'] = $found->menu_order; $args['menu-item-parent-id'] = $found->menu_item_parent; }
	$id = wp_update_nav_menu_item( $menu, $found ? $found->ID : 0, $args );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
}

$summary = array( 'home' => home_url(), 'brand' => 'آوان چرم', 'confirmed_phone' => '09101719687', 'existing_pages' => $page_ids, 'categories' => array_keys( $terms ), 'gift_count' => count( $gifts ), 'main_menu' => $main_menu, 'already_applied' => (bool) get_option( 'voiceweb_avan_brand_version' ) );
if ( ! $apply || get_option( 'voiceweb_avan_brand_version' ) ) {
	echo wp_json_encode( array_merge( array( 'mode' => $apply ? 'already-applied; editorial content preserved' : 'dry-run' ), $summary ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n"; exit;
}
if ( ! acf_get_field( 'field_vw_brand_intro' ) || ! acf_get_field( 'field_vw_cat_guide' ) ) { fwrite( STDERR, "Brand/category fields must be registered first.\n" ); exit( 1 ); }

// Preserve the exact previous content privately before this explicitly requested rebrand.
if ( ! get_option( 'voiceweb_avan_brand_backup_v1' ) ) {
	$backup = array( 'created_at' => gmdate( 'c' ), 'options' => array(), 'posts' => array(), 'terms' => array(), 'gift_seo' => array(), 'menus' => array(), 'menu_names' => array() );
	foreach ( array( 'blogname', 'blogdescription', 'site_icon' ) as $key ) { $backup['options'][ $key ] = get_option( $key ); }
	foreach ( array( 'cn_header_phone', 'cn_header_phone_link', 'cn_footer_phone', 'cn_footer_phone_link', 'cn_footer_description' ) as $key ) { $backup['options'][ 'options_' . $key ] = get_option( 'options_' . $key ); }
	foreach ( array_filter( array_merge( array( $front ), array_values( $page_ids ) ) ) as $id ) { $backup['posts'][ $id ] = array( 'post' => get_post( $id, ARRAY_A ), 'meta' => get_post_meta( $id ) ); }
	foreach ( $terms as $term ) { $backup['terms'][ $term->term_id ] = array( 'name' => $term->name, 'description' => $term->description, 'meta' => get_term_meta( $term->term_id ) ); }
	foreach ( array_filter( array_unique( array_merge( array_values( $gifts ), array( voiceweb_gift_hub_id() ) ) ) ) as $id ) { $backup['gift_seo'][ $id ] = array( 'title' => get_post_meta( $id, 'vw_gift_seo_title', true ), 'description' => get_post_meta( $id, 'vw_gift_seo_description', true ) ); }
	foreach ( array_unique( array_merge( array( $main_menu ), array_column( $footer_menus, 'menu_id' ) ) ) as $menu ) {
		if ( $menu ) { $backup['menus'][ $menu ] = wp_get_nav_menu_items( $menu ); $menu_object = wp_get_nav_menu_object( $menu ); $backup['menu_names'][ $menu ] = $menu_object ? $menu_object->name : ''; }
	}
	add_option( 'voiceweb_avan_brand_backup_v1', $backup, '', false );
}
try {
	update_option( 'blogname', 'آوان چرم' );
	update_option( 'blogdescription', 'تولید کیف و کیف پول با چرم طبیعی' );
	update_option( 'site_icon', 0 ); // The new vector brand mark is supplied by the theme until a custom icon is chosen.
	foreach ( array( 'cn_header_phone', 'cn_header_phone_link', 'cn_footer_phone', 'cn_footer_phone_link' ) as $key ) { update_field( 'field_' . $key, '09101719687', 'option' ); }
	update_field( 'field_cn_footer_description', 'آوان چرم؛ طراحی و تولید کیف، کیف پول و اکسسوری با چرم طبیعی. از انتخاب چرم گاوی یا بزی متناسب با هر مدل تا ساخت محصول، تمام مراحل را خودمان انجام می‌دهیم.', 'option' );
	foreach ( $brand_spec as $kind => $spec ) {
		$post_args = array( 'post_type' => 'page', 'post_title' => $spec['title'], 'post_content' => $spec['content'], 'post_excerpt' => $spec['intro'], 'comment_status' => 'closed' );
		if ( $page_ids[ $kind ] ) { $post_args['ID'] = $page_ids[ $kind ]; $id = wp_update_post( wp_slash( $post_args ), true ); }
		else { $post_args['post_name'] = $kind; $post_args['post_status'] = 'publish'; $id = wp_insert_post( wp_slash( $post_args ), true ); }
		if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
		$page_ids[ $kind ] = (int) $id;
		update_post_meta( $id, '_wp_page_template', 'page-templates/' . $kind . '.php' );
		$values = array_intersect_key( $spec, array_flip( array( 'intro', 'seo_title', 'seo_description', 'process', 'leather', 'faq' ) ) );
		$values['hero_image'] = avan_setup_image( $spec['image_key'] );
		if ( $kind === 'contact' ) { $values['phone'] = '09101719687'; }
		avan_setup_fields( 'vw_brand_', $id, $values );
		if ( $values['hero_image'] ) { set_post_thumbnail( $id, $values['hero_image'] ); }
	}
	foreach ( $category_spec as $slug => $spec ) {
		$term = $terms[ $slug ];
		$result = wp_update_term( $term->term_id, 'product_cat', array( 'name' => $spec['title'], 'description' => $spec['intro'] ) );
		if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
		$values = array_intersect_key( $spec, array_flip( array( 'intro', 'guide', 'tips', 'faq', 'seo_title', 'seo_description' ) ) );
		$values['related_categories'] = array_values( array_filter( array_map( static fn( $key ) => isset( $terms[ $key ] ) ? $terms[ $key ]->term_id : 0, $spec['related_categories'] ) ) );
		$values['related_gifts'] = array_values( array_filter( array_map( static fn( $key ) => $gifts[ $key ] ?? 0, $spec['related_gifts'] ) ) );
		avan_setup_fields( 'vw_cat_', 'product_cat_' . $term->term_id, $values );
	}
	foreach ( $gifts as $slug => $id ) {
		$spec = $gift_spec[ $slug ];
		avan_setup_fields( 'vw_gift_', $id, array( 'seo_title' => $spec['seo_title'], 'seo_description' => $spec['seo_description'] ) );
	}
	$hub = voiceweb_gift_hub_id();
	if ( $hub ) { avan_setup_fields( 'vw_gift_', $hub, array( 'seo_title' => 'راهنمای خرید هدیه برای مناسبت‌ها | آوان چرم', 'seo_description' => 'هدیه‌های آوان چرم برای روز مادر، روز پدر، نوروز، تولد و مناسبت‌های دیگر؛ کیف و کیف پول را همراه با راهنمای انتخاب و قیمت محصولات مقایسه کنید.' ) ); }
	if ( $front ) {
		avan_setup_fields( 'vw_front_', $front, array(
			'header_note' => 'آوان چرم؛ از چرم طبیعی تا همراه روزهای شما',
			'seo_title' => 'آوان چرم | خرید کیف و کیف پول چرم طبیعی مردانه و زنانه',
			'seo_description' => 'خرید کیف، کیف پول و جاکارتی چرم طبیعی از آوان چرم؛ طراحی و تولید در مجموعهٔ خودمان با چرم گاوی و بزی متناسب با هر مدل. محصولات و راهنمای هدیه را ببینید.',
			'story_title' => 'از انتخاب چرم تا آخرین جزئیات',
			'story_description' => 'در آوان چرم، مسیر ساخت محصول را صفر تا صد خودمان پیش می‌بریم. چرم طبیعی گاوی یا بزی را متناسب با نیاز هر مدل انتخاب می‌کنیم و طراحی، برش و دوخت را با توجه به کاربرد آن انجام می‌دهیم.',
			'story_link' => array( 'title' => 'با آوان چرم آشنا شوید', 'url' => get_permalink( $page_ids['about'] ), 'target' => '' ),
			'about_title' => 'آوان چرم؛ ساخته‌شده در مجموعهٔ ما',
			'about_description' => 'برای ما، اصالت از خود چرم شروع می‌شود. کیف‌ها و کیف پول‌های آوان چرم با چرم طبیعی ساخته می‌شوند و تمام مراحل شکل‌گیری آن‌ها در مجموعهٔ خودمان انجام می‌شود؛ از طرح اولیه تا محصولی که همراه شما می‌شود.',
			'about_link' => array( 'title' => 'داستان آوان چرم', 'url' => get_permalink( $page_ids['about'] ), 'target' => '' ),
			'blog_title' => 'مجلهٔ آوان چرم',
			'benefits' => array(
				array( 'title' => 'چرم طبیعی', 'description' => 'چرم گاوی یا بزی، متناسب با نیاز هر مدل.', 'icon' => 'check' ),
				array( 'title' => 'تولید در مجموعهٔ خودمان', 'description' => 'از طراحی تا ساخت، صفر تا صد با آوان چرم.', 'icon' => 'shield' ),
				array( 'title' => 'انتخاب بر اساس کاربرد', 'description' => 'توجه به اندازه، فرم و استفادهٔ روزمره.', 'icon' => 'user' ),
				array( 'title' => 'گفت‌وگوی مستقیم', 'description' => 'برای شناخت مدل و جنس آن با ما تماس بگیرید.', 'icon' => 'check' ),
			),
		) );
	}
	// Bind menu links to native objects so permalink changes do not leave stale custom URLs.
	if ( $main_menu ) {
		wp_update_nav_menu_object( $main_menu, array( 'menu-name' => 'آوان چرم | فروشگاه' ) );
		foreach ( array( 'leather-men-bags' => 'کیف مردانه', 'leather-women-bags' => 'کیف زنانه' ) as $slug => $label ) { avan_setup_menu_link( $main_menu, $label, 'product_cat', $terms[ $slug ]->term_id, get_term_link( $terms[ $slug ] ) ); }
		avan_setup_menu_link( $main_menu, 'دربارهٔ ما', 'page', $page_ids['about'], get_permalink( $page_ids['about'] ) );
		if ( $hub ) { avan_setup_menu_link( $main_menu, 'راهنمای هدیه', 'page', $hub, get_permalink( $hub ) ); }
		avan_setup_menu_link( $main_menu, 'تماس با ما', 'page', $page_ids['contact'], get_permalink( $page_ids['contact'] ) );
	}
	foreach ( $footer_menus as $column ) {
		$menu = (int) $column['menu_id'];
		$existing = wp_get_nav_menu_items( $menu ) ?: array();
		$category_menu = false;
		foreach ( $existing as $item ) { if ( strpos( $item->url, '/product-category/' ) !== false ) { $category_menu = true; break; } }
		if ( $category_menu ) { foreach ( $terms as $slug => $term ) { avan_setup_menu_link( $menu, $category_spec[ $slug ]['title'], 'product_cat', $term->term_id, get_term_link( $term ) ); } }
		else {
			avan_setup_menu_link( $menu, 'دربارهٔ آوان چرم', 'page', $page_ids['about'], get_permalink( $page_ids['about'] ) );
			avan_setup_menu_link( $menu, 'تماس با آوان چرم', 'page', $page_ids['contact'], get_permalink( $page_ids['contact'] ) );
			if ( $hub ) { avan_setup_menu_link( $menu, 'راهنمای هدیه', 'page', $hub, get_permalink( $hub ) ); }
		}
	}
	update_option( 'voiceweb_avan_brand_version', 1, false );
	echo wp_json_encode( array( 'mode' => 'applied', 'brand' => get_bloginfo( 'name' ), 'about' => get_permalink( $page_ids['about'] ), 'contact' => get_permalink( $page_ids['contact'] ), 'phone' => voiceweb_brand_value( 'phone', '', $page_ids['contact'] ), 'category_landings' => count( $terms ), 'gift_landings' => count( $gifts ), 'indexing_unchanged' => get_option( 'blog_public' ), 'coming_soon_unchanged' => get_option( 'woocommerce_coming_soon' ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
} catch ( Throwable $error ) { fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 ); }
