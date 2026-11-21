<?php
/** Catalog controls and native WooCommerce prices derived from a manually entered dollar rate. */
defined( 'ABSPATH' ) || exit;

const VOICEWEB_WC_USD_AMOUNT = '_voiceweb_usd_amount';
const VOICEWEB_WC_USD_OFFER = '_voiceweb_usd_offer';
const VOICEWEB_WC_USD_RATE = 'voiceweb_usd_toman_rate';
const VOICEWEB_WC_USD_LOCAL_PRICE = '_voiceweb_before_usd_price';

function voiceweb_wc_pricing_modes(): array {
	return array( '' => 'پیش‌فرض فروشگاه', 'inquiry' => 'تماس بگیرید', 'purchase' => 'خرید آنلاین' );
}

function voiceweb_wc_pricing_digits( string $value ): string {
	return strtr( $value, array_combine( preg_split( '//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY ), str_split( '01234567890123456789' ) ) );
}

function voiceweb_wc_pricing_priority( $value ): int {
	if ( ! is_string( $value ) ) { throw new InvalidArgumentException( 'اولویت نمایش معتبر نیست.' ); }
	$value = trim( voiceweb_wc_pricing_digits( $value ) );
	if ( $value === '' ) { return 0; }
	if ( ! preg_match( '/^[0-9]{1,9}$/D', $value ) ) { throw new InvalidArgumentException( 'اولویت نمایش باید عدد صحیح از صفر تا ۹۹۹۹۹۹۹۹۹ باشد.' ); }
	return (int) $value;
}

function voiceweb_wc_pricing_number( $value, int $decimals ): string {
	if ( ! is_string( $value ) ) { throw new InvalidArgumentException( 'مقدار عددی معتبر نیست.' ); }
	$value = trim( strtr( $value, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		'٫' => '.', '٬' => ',',
	) ) );
	if ( $value === '' ) { return ''; }
	$fraction = $decimals ? '(?:\.[0-9]{1,' . $decimals . '})?' : '';
	if ( ! preg_match( '/^(?:[0-9]{1,9}|[0-9]{1,3}(?:,[0-9]{3}){1,2})' . $fraction . '$/D', $value ) ) {
		throw new InvalidArgumentException( 'عدد را به صورت مثبت و با حداکثر ۹ رقم صحیح وارد کنید. قیمت دلاری حداکثر دو رقم اعشار دارد.' );
	}
	$value = str_replace( ',', '', $value );
	if ( (float) $value <= 0 ) { throw new InvalidArgumentException( 'قیمت و نرخ باید بزرگ‌تر از صفر باشند.' ); }
	return wc_format_decimal( $value, $decimals, true );
}

function voiceweb_wc_pricing_local_amount( string $usd, string $rate ): string {
	if ( $usd === '' ) { return ''; }
	$currency = get_woocommerce_currency();
	if ( $currency === 'USD' ) { return $usd; }
	if ( $rate === '' ) { throw new InvalidArgumentException( 'پیش از ثبت مبلغ دلاری، نرخ دلار را ذخیره کنید.' ); }
	if ( ! in_array( $currency, array( 'IRR', 'IRT' ), true ) ) {
		throw new InvalidArgumentException( 'تبدیل نرخ برای واحد پول ریال، تومان یا دلار پشتیبانی می‌شود.' );
	}
	$amount = round( (float) $usd * (float) $rate * ( $currency === 'IRR' ? 10 : 1 ) );
	// WooCommerce's lookup columns have 15 integer digits.
	if ( $amount < 1 || $amount >= 1e15 ) { throw new InvalidArgumentException( 'حاصل تبدیل یکی از قیمت‌ها خارج از محدودهٔ قابل ثبت است.' ); }
	return number_format( $amount, 0, '.', '' );
}

/** Keep the dollar schedule after Woo clears an ended native sale, so changing rates cannot restart it. */
function voiceweb_wc_pricing_offer( WC_Product $product ): array {
	$offer = $product->get_meta( VOICEWEB_WC_USD_OFFER, true, 'edit' );
	return is_array( $offer ) ? $offer : array();
}

function voiceweb_wc_pricing_date( $value ): ?int {
	if ( ! is_string( $value ) ) { throw new InvalidArgumentException( 'تاریخ تخفیف معتبر نیست.' ); }
	$value = trim( voiceweb_wc_pricing_digits( $value ) );
	if ( $value === '' ) { return null; }
	if ( preg_match( '/^([0-9]{4})\/([0-9]{2})\/([0-9]{2}) ([0-9]{2}):([0-9]{2})(?::([0-9]{2}))?$/D', $value, $parts ) && voiceweb_wc_pricing_has_jalali() ) {
		[ , $year, $month, $day, $hour, $minute ] = $parts;
		$second = $parts[6] ?? '00';
		if ( (int) $year < 1200 || (int) $year > 1600 || (int) $month < 1 || (int) $month > 12 || (int) $day < 1 || (int) $day > 31 || (int) $hour > 23 || (int) $minute > 59 || (int) $second > 59 ) { throw new InvalidArgumentException( 'تاریخ و ساعت شمسی تخفیف معتبر نیست.' ); }
		$calendar = \WPParsidate\Core\WPP_ParsiDate::getInstance();
		$gregorian = $calendar->persian_to_gregorian( (int) $year, (int) $month, (int) $day );
		$roundtrip = array_map( 'intval', $calendar->gregorian_to_persian( ...$gregorian ) );
		if ( $roundtrip !== array( (int) $year, (int) $month, (int) $day ) ) { throw new InvalidArgumentException( 'روز انتخاب‌شده در تقویم شمسی وجود ندارد.' ); }
		$value = sprintf( '%04d-%02d-%02dT%02d:%02d:%02d', ...array_merge( $gregorian, array( (int) $hour, (int) $minute, (int) $second ) ) );
	}
	// Continue accepting an already-open legacy Gregorian form without reinterpreting its dates.
	$format = strlen( $value ) === 16 ? 'Y-m-d\TH:i' : 'Y-m-d\TH:i:s';
	$date = DateTimeImmutable::createFromFormat( '!' . $format, $value, wp_timezone() );
	if ( ! $date || $date->format( $format ) !== $value ) { throw new InvalidArgumentException( 'تاریخ و ساعت تخفیف معتبر نیست.' ); }
	return $date->getTimestamp();
}

function voiceweb_wc_pricing_has_jalali(): bool {
	return class_exists( '\WPParsidate\Core\WPP_ParsiDate' );
}

/** Render the Jalali wall-clock value; native sale timestamps always stay timezone-aware UTC. */
function voiceweb_wc_pricing_date_input( ?int $timestamp ): string {
	if ( ! $timestamp ) { return ''; }
	$date = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() );
	if ( ! voiceweb_wc_pricing_has_jalali() ) { return $date->format( 'Y-m-d\TH:i:s' ); }
	$calendar = \WPParsidate\Core\WPP_ParsiDate::getInstance();
	$jalali = $calendar->gregorian_to_persian( (int) $date->format( 'Y' ), (int) $date->format( 'n' ), (int) $date->format( 'j' ) );
	return sprintf( '%04d/%02d/%02d ', ...array_map( 'intval', $jalali ) ) . $date->format( 'H:i:s' );
}

function voiceweb_wc_pricing_admin_assets( string $hook ): void {
	if ( $hook !== 'woocommerce_page_voiceweb-catalog-pricing' || ! voiceweb_wc_pricing_has_jalali() ) { return; }
	$handle = WP_PARSI_KEY . '_jalali_datepicker';
	wp_enqueue_script( $handle, \WPParsidate\Helper\Assets::url( 'js-admin/jalalidatepicker.min.js' ), array(), \WPParsidate\Helper\Assets::getVersion(), true );
	wp_enqueue_style( $handle, \WPParsidate\Helper\Assets::url( 'css-admin/jalalidatepicker.min.css' ), array(), \WPParsidate\Helper\Assets::getVersion() );
	$file = '/assets/js/admin/catalog-pricing.js';
	wp_enqueue_script( 'voiceweb-catalog-pricing', get_template_directory_uri() . $file, array( $handle ), filemtime( get_template_directory() . $file ), true );
}
add_action( 'admin_enqueue_scripts', 'voiceweb_wc_pricing_admin_assets', 100 );

/** Write regular, effective and lookup prices through the WooCommerce data store. */
function voiceweb_wc_pricing_set_amount( WC_Product $product, string $usd, string $rate, ?array $offer = null ): void {
	$offer = $offer ?? voiceweb_wc_pricing_offer( $product );
	$previous = $product->get_meta( VOICEWEB_WC_USD_LOCAL_PRICE, true, 'edit' );
	if ( $usd === '' ) {
		$product->delete_meta_data( VOICEWEB_WC_USD_AMOUNT );
		$product->delete_meta_data( VOICEWEB_WC_USD_OFFER );
		$product->delete_meta_data( VOICEWEB_WC_USD_LOCAL_PRICE );
		if ( is_array( $previous ) ) {
			$product->set_regular_price( $previous['regular'] );
			$product->set_sale_price( $previous['sale'] );
			$product->set_date_on_sale_from( $previous['from'] );
			$product->set_date_on_sale_to( $previous['to'] );
			$product->set_price( $product->is_on_sale( 'edit' ) ? $previous['sale'] : $previous['regular'] );
		}
		return;
	}
	if ( ! is_array( $previous ) ) {
		$product->update_meta_data( VOICEWEB_WC_USD_LOCAL_PRICE, array(
			'regular' => $product->get_regular_price( 'edit' ),
			'sale'    => $product->get_sale_price( 'edit' ),
			'from'    => $product->get_date_on_sale_from( 'edit' )?->getTimestamp(),
			'to'      => $product->get_date_on_sale_to( 'edit' )?->getTimestamp(),
		) );
	}
	$price = voiceweb_wc_pricing_local_amount( $usd, $rate );
	$product->update_meta_data( VOICEWEB_WC_USD_AMOUNT, $usd );
	$product->set_regular_price( $price );
	if ( $offer ) { $product->update_meta_data( VOICEWEB_WC_USD_OFFER, $offer ); }
	else { $product->delete_meta_data( VOICEWEB_WC_USD_OFFER ); }
	$expired = ! empty( $offer['to'] ) && $offer['to'] < time();
	$product->set_sale_price( $offer && ! $expired ? voiceweb_wc_pricing_local_amount( $offer['amount'], $rate ) : '' );
	$product->set_date_on_sale_from( $offer && ! $expired ? $offer['from'] : null );
	$product->set_date_on_sale_to( $offer && ! $expired ? $offer['to'] : null );
	$product->set_price( $product->is_on_sale( 'edit' ) ? $product->get_sale_price( 'edit' ) : $price );
}

function voiceweb_wc_pricing_preserve_usd( WC_Product $product ): void {
	$usd = (string) $product->get_meta( VOICEWEB_WC_USD_AMOUNT, true, 'edit' );
	if ( $usd === '' ) { return; }
	voiceweb_wc_pricing_set_amount( $product, $usd, (string) get_option( VOICEWEB_WC_USD_RATE, '' ) );
}
add_action( 'woocommerce_admin_process_product_object', 'voiceweb_wc_pricing_preserve_usd', 20 );
add_action( 'woocommerce_admin_process_variation_object', 'voiceweb_wc_pricing_preserve_usd', 20 );

function voiceweb_wc_pricing_menu(): void {
	add_submenu_page( 'woocommerce', 'کاتالوگ و قیمت‌گذاری', 'کاتالوگ و قیمت‌گذاری', 'manage_woocommerce', 'voiceweb-catalog-pricing', 'voiceweb_wc_pricing_page' );
}
add_action( 'admin_menu', 'voiceweb_wc_pricing_menu', 31 );

function voiceweb_wc_pricing_url( array $args = array() ): string {
	return add_query_arg( array_merge( array( 'page' => 'voiceweb-catalog-pricing' ), $args ), admin_url( 'admin.php' ) );
}

function voiceweb_wc_pricing_statuses(): array {
	return array( 'publish', 'draft', 'pending', 'private', 'future' );
}

function voiceweb_wc_pricing_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
	$rate = (string) get_option( VOICEWEB_WC_USD_RATE, '' );
	$page = max( 1, absint( $_GET['catalog_page'] ?? 1 ) );
	$search = isset( $_GET['catalog_search'] ) && is_string( $_GET['catalog_search'] ) ? sanitize_text_field( wp_unslash( $_GET['catalog_search'] ) ) : '';
	$list = new WP_Query( array(
		'post_type' => 'product', 'post_status' => voiceweb_wc_pricing_statuses(), 'posts_per_page' => 25,
		'paged' => $page, 's' => $search, 'orderby' => array( 'title' => 'ASC', 'ID' => 'ASC' ),
	) );
	$mode_options = voiceweb_wc_pricing_modes();
	$notice_key = 'voiceweb_catalog_notice_' . get_current_user_id();
	$notice = get_transient( $notice_key );
	delete_transient( $notice_key );
	?>
	<div class="wrap">
		<h1>کاتالوگ و قیمت‌گذاری</h1>
		<?php if ( is_array( $notice ) ) : ?>
			<div class="notice <?php echo $notice['error'] ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
		<?php endif; ?>
		<h2>نرخ دلار</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="voiceweb_catalog_pricing">
			<?php wp_nonce_field( 'voiceweb_catalog_rate' ); ?>
			<label for="vw-usd-rate">هر ۱ دلار به تومان</label>
			<input id="vw-usd-rate" name="usd_rate" type="text" inputmode="numeric" dir="ltr" value="<?php echo esc_attr( $rate ); ?>" required>
			<?php submit_button( 'ذخیره و به‌روزرسانی قیمت‌ها', 'primary', 'submit', false ); ?>
		</form>
		<p>با ذخیرهٔ نرخ، قیمت تمام محصولاتی که مبلغ دلاری دارند در ووکامرس به‌روز می‌شود. در فروشگاه ریالی، مبلغ تومانی در ۱۰ ضرب می‌شود. برای فروش آنلاین، روش سفارش و موجودی را هم بررسی کنید.</p>
		<h2>محصولات (<?php echo esc_html( number_format_i18n( $list->found_posts ) ); ?>)</h2>
		<p>عدد بزرگ‌تر در «اولویت نمایش»، محصول را در ترتیب پیشنهادی فروشگاه و تمام دسته‌بندی‌ها جلوتر می‌آورد. اولویت صفر، حالت عادی است؛ تنوع‌ها از اولویت محصول اصلی پیروی می‌کنند.</p>
		<p>مبلغ با تخفیف باید کمتر از مبلغ پایه باشد. شروع خالی یعنی اعمال فوری و پایان خالی یعنی بدون محدودیت زمانی. برای حذف تخفیف، مبلغ با تخفیف را پاک کنید. با پاک‌کردن مبلغ پایه، قیمت معمول ووکامرس برمی‌گردد. حالت تماس بگیرید مبلغ را از دید مشتری پنهان می‌کند.</p>
		<p id="vw-sale-timezone">تاریخ‌ها <?php echo voiceweb_wc_pricing_has_jalali() ? 'شمسی' : 'میلادی'; ?> و ساعت‌ها بر اساس منطقهٔ زمانی فروشگاه (<?php echo esc_html( wp_timezone_string() ); ?>) هستند. پس از پایان بازه، تخفیف اعمال نمی‌شود و قیمت پایه برمی‌گردد. برای نمایش تایم‌لاین، پایان تخفیف را مشخص کنید.</p>
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="voiceweb-catalog-pricing">
			<label class="screen-reader-text" for="vw-catalog-search">نام محصول</label>
			<input id="vw-catalog-search" name="catalog_search" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="نام محصول">
			<?php submit_button( 'جست‌وجو', 'secondary', 'submit', false ); ?>
		</form>
		<?php if ( ! $list->posts ) : ?><p>محصولی پیدا نشد.</p></div><?php return; endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="voiceweb_catalog_pricing">
			<input type="hidden" name="catalog_page" value="<?php echo esc_attr( $page ); ?>">
			<input type="hidden" name="catalog_search" value="<?php echo esc_attr( $search ); ?>">
			<?php wp_nonce_field( 'voiceweb_catalog_rows' ); ?>
			<div style="overflow-x:auto;margin-top:12px">
			<table class="widefat striped" style="min-width:1280px">
				<thead><tr><th scope="col" style="width:76px">تصویر</th><th scope="col">محصول</th><th scope="col">روش سفارش</th><th scope="col">اولویت نمایش</th><th scope="col">مبلغ پایه (دلار)</th><th scope="col">مبلغ با تخفیف (دلار)</th><th scope="col">شروع تخفیف</th><th scope="col">پایان تخفیف</th><th scope="col">قیمت در ووکامرس</th></tr></thead>
				<tbody>
				<?php foreach ( $list->posts as $post ) :
					$product = wc_get_product( $post->ID );
					if ( ! $product ) { continue; }
					$id = $product->get_id();
					?>
					<tr>
						<?php voiceweb_wc_pricing_image_cell( $product ); ?>
						<th scope="row"><a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
							<small><?php echo esc_html( $product->get_sku() ?: '#' . $id ); ?></small>
							<?php if ( 'publish' !== $product->get_status() ) : ?><small> · <?php echo esc_html( get_post_status_object( $product->get_status() )->label ); ?></small><?php endif; ?>
						</th>
						<td><?php if ( $product->is_type( array( 'simple', 'variable' ) ) && current_user_can( 'edit_product', $id ) ) : ?>
							<label for="vw-mode-<?php echo esc_attr( $id ); ?>" class="screen-reader-text"><?php echo esc_html( 'روش سفارش ' . $product->get_name() ); ?></label>
							<select name="product_mode[<?php echo esc_attr( $id ); ?>]" id="vw-mode-<?php echo esc_attr( $id ); ?>">
								<?php foreach ( $mode_options as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $product->get_meta( '_voiceweb_purchase_mode', true, 'edit' ) ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php else : ?>—<?php endif; ?></td>
						<?php voiceweb_wc_pricing_cells( $product ); ?>
					</tr>
					<?php foreach ( $product->is_type( 'variable' ) ? $product->get_children() : array() as $child_id ) :
						$child = wc_get_product( $child_id );
						if ( ! $child || $child->get_status() === 'trash' ) { continue; }
						?>
						<tr><?php voiceweb_wc_pricing_image_cell( $child ); ?><th scope="row" style="padding-inline-start:2em"><?php echo esc_html( wc_get_formatted_variation( $child, true, true ) ?: '#' . $child_id ); ?></th><td>تابع محصول اصلی</td><?php voiceweb_wc_pricing_cells( $child ); ?></tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<?php submit_button( 'ذخیرهٔ محصولات این صفحه' ); ?>
		</form>
		<?php echo wp_kses_post( paginate_links( array( 'base' => str_replace( '987654321', '%#%', voiceweb_wc_pricing_url( array( 'catalog_page' => 987654321, 'catalog_search' => $search ) ) ), 'current' => $page, 'total' => $list->max_num_pages ) ) ); ?>
	</div>
	<?php
}

function voiceweb_wc_pricing_image_cell( WC_Product $product ): void {
	echo '<td style="width:76px;vertical-align:middle">';
	echo $product->get_image( 'medium', array( 'class' => 'vw-catalog-product-image', 'alt' => $product->get_name(), 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '64px', 'style' => 'display:block;width:64px;height:64px;object-fit:contain;background:#fff;border:1px solid #dcdcde;border-radius:6px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce renders escaped image attributes and its native placeholder.
	echo '</td>';
}

function voiceweb_wc_pricing_cells( WC_Product $product ): void {
	$regular = $product->get_regular_price( 'edit' );
	$editable = $product->is_type( array( 'simple', 'variation' ) ) && current_user_can( 'edit_product', $product->get_parent_id() ?: $product->get_id() );
	$id = $product->get_id();
	$offer = voiceweb_wc_pricing_offer( $product );
	echo '<td>';
	if ( $product->is_type( 'variation' ) ) { echo 'تابع محصول اصلی'; }
	elseif ( current_user_can( 'edit_product', $id ) ) {
		echo '<label class="screen-reader-text" for="vw-priority-' . esc_attr( $id ) . '">' . esc_html( 'اولویت نمایش ' . $product->get_name() ) . '</label><input type="text" inputmode="numeric" dir="ltr" class="small-text" id="vw-priority-' . esc_attr( $id ) . '" name="display_priority[' . esc_attr( $id ) . ']" value="' . esc_attr( $product->get_menu_order( 'edit' ) ) . '">';
	} else { echo '—'; }
	echo '</td>';
	$date_type = voiceweb_wc_pricing_has_jalali() ? 'jalali' : 'datetime-local';
	$fields = array(
		'usd_amount' => array( 'مبلغ پایه دلاری', $product->get_meta( VOICEWEB_WC_USD_AMOUNT, true, 'edit' ), 'text' ),
		'usd_sale_amount' => array( 'مبلغ با تخفیف دلاری', $offer['amount'] ?? '', 'text' ),
		'sale_from' => array( 'شروع تخفیف', voiceweb_wc_pricing_date_input( $offer['from'] ?? null ), $date_type ),
		'sale_to' => array( 'پایان تخفیف', voiceweb_wc_pricing_date_input( $offer['to'] ?? null ), $date_type ),
	);
	foreach ( $fields as $key => $field ) {
		echo '<td>';
		if ( $editable ) {
			$field_id = 'vw-' . $key . '-' . $id;
			echo '<label class="screen-reader-text" for="' . esc_attr( $field_id ) . '">' . esc_html( $field[0] . ' ' . $product->get_name() ) . '</label>';
			$attributes = $field[2] === 'text' ? 'inputmode="decimal" class="small-text"' : 'aria-describedby="vw-sale-timezone"';
			$attributes .= $field[2] === 'jalali' ? ' data-vw-jalali placeholder="1405/09/01 12:00:00" autocomplete="off" style="width:190px"' : ( $field[2] === 'datetime-local' ? ' step="1"' : '' );
			echo '<input type="' . esc_attr( $field[2] === 'jalali' ? 'text' : $field[2] ) . '" dir="ltr" ' . $attributes . ' id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $key ) . '[' . esc_attr( $id ) . ']" value="' . esc_attr( $field[1] ) . '">';
		} else { echo $product->is_type( 'variable' ) ? 'برای هر تنوع' : '—'; }
		echo '</td>';
	}
	echo '<td>' . ( $regular === '' ? '—' : wp_kses_post( $product->is_on_sale( 'edit' ) ? wc_format_sale_price( $regular, $product->get_sale_price( 'edit' ) ) : wc_price( $regular ) ) );
	if ( $offer ) {
		$status = ! empty( $offer['to'] ) && $offer['to'] < time() ? 'پایان‌یافته' : ( ! empty( $offer['from'] ) && $offer['from'] > time() ? 'زمان‌بندی‌شده' : 'فعال' );
		echo '<br><small>' . esc_html( 'تخفیف: ' . $status ) . '</small>';
	}
	echo '</td>';
}

/** Validate a whole submission before persisting it and serialize catalog price jobs. */
function voiceweb_wc_pricing_save(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) ); }
	$rate_request = isset( $_POST['usd_rate'] );
	check_admin_referer( $rate_request ? 'voiceweb_catalog_rate' : 'voiceweb_catalog_rows' );
	$error = false;
	$message = 'تغییرات کاتالوگ ذخیره شد.';
	try {
		voiceweb_wc_pricing_apply( $rate_request, wp_unslash( $_POST ) );
	} catch ( InvalidArgumentException $exception ) {
		$error = true;
		$message = $exception->getMessage();
	} catch ( Throwable $exception ) {
		$error = true;
		$message = 'ذخیره ممکن نشد. صفحه را تازه کنید و دوباره تلاش کنید.';
		wc_get_logger()->error( $exception->getMessage(), array( 'source' => 'voiceweb-catalog-pricing' ) );
	}
	set_transient( 'voiceweb_catalog_notice_' . get_current_user_id(), array( 'error' => $error, 'message' => $message ), MINUTE_IN_SECONDS );
	$search = isset( $_POST['catalog_search'] ) && is_string( $_POST['catalog_search'] ) ? sanitize_text_field( wp_unslash( $_POST['catalog_search'] ) ) : '';
	wp_safe_redirect( voiceweb_wc_pricing_url( array( 'catalog_page' => max( 1, absint( $_POST['catalog_page'] ?? 1 ) ), 'catalog_search' => $search ) ) );
	exit;
}
add_action( 'admin_post_voiceweb_catalog_pricing', 'voiceweb_wc_pricing_save' );

function voiceweb_wc_pricing_apply( bool $rate_request, array $input ): void {
	global $wpdb;
	$lock = 'voiceweb_catalog_' . md5( DB_NAME . ':' . $wpdb->options );
	if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
		throw new RuntimeException( 'Could not acquire the catalog pricing lock.' );
	}
	$products = $parents = array();
	try {
		$rate = (string) get_option( VOICEWEB_WC_USD_RATE, '' );
		$amounts = $modes = $offers = $priorities = array();
		if ( $rate_request ) {
			$rate = voiceweb_wc_pricing_number( $input['usd_rate'] ?? '', 0 );
			if ( $rate === '' ) { throw new InvalidArgumentException( 'نرخ دلار را وارد کنید.' ); }
			$ids = get_posts( array(
				'post_type' => array( 'product', 'product_variation' ), 'post_status' => voiceweb_wc_pricing_statuses(),
				'meta_key' => VOICEWEB_WC_USD_AMOUNT, 'fields' => 'ids', 'numberposts' => -1,
			) );
			foreach ( $ids as $id ) {
				$product = wc_get_product( $id );
				if ( $product && $product->is_type( array( 'simple', 'variation' ) ) ) {
					$amounts[ $id ] = (string) $product->get_meta( VOICEWEB_WC_USD_AMOUNT, true, 'edit' );
					$products[ $id ] = $product;
				}
			}
		} else {
			if ( isset( $input['display_priority'] ) && ! is_array( $input['display_priority'] ) ) { throw new InvalidArgumentException( 'اطلاعات اولویت نمایش معتبر نیست.' ); }
			foreach ( $input['display_priority'] ?? array() as $id => $priority ) {
				$product = voiceweb_wc_pricing_editable_product( $id, array_keys( wc_get_product_types() ) );
				$priority = voiceweb_wc_pricing_priority( $priority );
				if ( $priority === $product->get_menu_order( 'edit' ) ) { continue; }
				$priorities[ $id ] = $priority;
				$products[ $id ] = $product;
			}
			foreach ( (array) ( $input['product_mode'] ?? array() ) as $id => $mode ) {
				$product = $products[ $id ] ?? voiceweb_wc_pricing_editable_product( $id, array( 'simple', 'variable' ) );
				if ( ! $product->is_type( array( 'simple', 'variable' ) ) ) { throw new InvalidArgumentException( 'نوع محصول برای روش سفارش معتبر نیست.' ); }
				if ( ! is_string( $mode ) || ! array_key_exists( $mode, voiceweb_wc_pricing_modes() ) ) { throw new InvalidArgumentException( 'روش سفارش معتبر نیست.' ); }
				if ( $mode === $product->get_meta( '_voiceweb_purchase_mode', true, 'edit' ) ) { continue; }
				$modes[ $id ] = $mode;
				$products[ $id ] = $product;
			}
			$row_ids = array();
			foreach ( array( 'usd_amount', 'usd_sale_amount', 'sale_from', 'sale_to' ) as $key ) {
				if ( isset( $input[ $key ] ) && ! is_array( $input[ $key ] ) ) { throw new InvalidArgumentException( 'اطلاعات قیمت معتبر نیست.' ); }
				$row_ids = array_merge( $row_ids, array_keys( $input[ $key ] ?? array() ) );
			}
			foreach ( array_unique( $row_ids ) as $id ) {
				$product = $products[ $id ] ?? voiceweb_wc_pricing_editable_product( $id, array( 'simple', 'variation' ) );
				if ( ! $product->is_type( array( 'simple', 'variation' ) ) ) { throw new InvalidArgumentException( 'نوع محصول برای قیمت دلاری معتبر نیست.' ); }
				$old_amount = (string) $product->get_meta( VOICEWEB_WC_USD_AMOUNT, true, 'edit' );
				$amount = voiceweb_wc_pricing_number( $input['usd_amount'][ $id ] ?? $old_amount, 2 );
				$old_offer = voiceweb_wc_pricing_offer( $product );
				$sale = voiceweb_wc_pricing_number( $input['usd_sale_amount'][ $id ] ?? ( $old_offer['amount'] ?? '' ), 2 );
				$from = array_key_exists( $id, $input['sale_from'] ?? array() ) ? voiceweb_wc_pricing_date( $input['sale_from'][ $id ] ) : ( $old_offer['from'] ?? null );
				$to = array_key_exists( $id, $input['sale_to'] ?? array() ) ? voiceweb_wc_pricing_date( $input['sale_to'][ $id ] ) : ( $old_offer['to'] ?? null );
				if ( $amount === '' && $old_amount !== '' ) { $sale = ''; }
				if ( $sale !== '' && ( $amount === '' || (float) $sale >= (float) $amount ) ) { throw new InvalidArgumentException( 'مبلغ با تخفیف باید کمتر از مبلغ پایهٔ دلاری باشد: ' . $product->get_name() ); }
				if ( $sale !== '' && $from && $to && $to <= $from ) { throw new InvalidArgumentException( 'پایان تخفیف باید بعد از شروع آن باشد: ' . $product->get_name() ); }
				if ( $sale === '' && ! $old_offer && ( $from || $to ) ) { throw new InvalidArgumentException( 'برای زمان‌بندی، مبلغ با تخفیف را هم وارد کنید: ' . $product->get_name() ); }
				$since = $from ?: ( empty( $old_offer['from'] ) ? ( $old_offer['since'] ?? time() ) : time() );
				$offer = $sale === '' ? array() : array( 'amount' => $sale, 'from' => $from, 'to' => $to, 'since' => $since );
				if ( $amount === $old_amount && $offer === $old_offer ) { continue; }
				$amounts[ $id ] = $amount;
				$offers[ $id ] = $offer;
				$products[ $id ] = $product;
			}
		}
		foreach ( $amounts as $id => $amount ) {
			$regular = voiceweb_wc_pricing_local_amount( $amount, $rate );
			$offer = $offers[ $id ] ?? voiceweb_wc_pricing_offer( $products[ $id ] );
			if ( $offer && (float) voiceweb_wc_pricing_local_amount( $offer['amount'], $rate ) >= (float) $regular ) { throw new InvalidArgumentException( 'قیمت با تخفیف پس از تبدیل باید کمتر از قیمت پایه باشد.' ); }
		}

		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { throw new RuntimeException( 'Could not start the price transaction.' ); }
		try {
			foreach ( $products as $id => $product ) {
				if ( isset( $priorities[ $id ] ) ) { $product->set_menu_order( $priorities[ $id ] ); }
				if ( isset( $modes[ $id ] ) ) { $product->update_meta_data( '_voiceweb_purchase_mode', $modes[ $id ] ); }
				if ( isset( $amounts[ $id ] ) ) {
					voiceweb_wc_pricing_set_amount( $product, $amounts[ $id ], $rate, $offers[ $id ] ?? null );
					if ( $product->get_parent_id() ) { $parents[ $product->get_parent_id() ] = true; }
				}
				if ( ! $product->save() || $wpdb->last_error ) { throw new RuntimeException( 'Price save failed for product ' . $id ); }
			}
			foreach ( array_keys( $parents ) as $id ) {
				WC_Product_Variable::sync( $id );
				if ( $wpdb->last_error ) { throw new RuntimeException( 'Could not synchronize variations for product ' . $id ); }
			}
			if ( $rate_request && $rate !== (string) get_option( VOICEWEB_WC_USD_RATE, '' ) && ! update_option( VOICEWEB_WC_USD_RATE, $rate, false ) ) {
				throw new RuntimeException( 'Could not save the dollar rate.' );
			}
			if ( false === $wpdb->query( 'COMMIT' ) ) { throw new RuntimeException( 'Could not commit the price transaction.' ); }
			do_action( 'voiceweb_catalog_pricing_committed' );
		} catch ( Throwable $exception ) {
			$wpdb->query( 'ROLLBACK' );
			wp_cache_delete( VOICEWEB_WC_USD_RATE, 'options' );
			wp_cache_delete( 'notoptions', 'options' );
			throw $exception;
		} finally {
			foreach ( array_unique( array_merge( array_keys( $products ), array_keys( $parents ) ) ) as $id ) {
				clean_post_cache( $id );
				WC_Cache_Helper::invalidate_cache_group( 'product_' . $id );
			}
			if ( $products ) { wc_delete_product_transients(); }
		}
	} finally {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}

function voiceweb_wc_pricing_editable_product( $id, array $types ): WC_Product {
	$product = ctype_digit( (string) $id ) ? wc_get_product( (int) $id ) : false;
	if ( ! $product || ! $product->is_type( $types ) || ! in_array( $product->get_status(), voiceweb_wc_pricing_statuses(), true ) ||
		! current_user_can( 'edit_product', $product->get_parent_id() ?: $product->get_id() ) ) {
		throw new InvalidArgumentException( 'یکی از محصولات قابل ویرایش نیست.' );
	}
	return $product;
}
