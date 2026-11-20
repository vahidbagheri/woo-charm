<?php
/** Catalog controls and native WooCommerce prices derived from a manually entered dollar rate. */
defined( 'ABSPATH' ) || exit;

const VOICEWEB_WC_USD_AMOUNT = '_voiceweb_usd_amount';
const VOICEWEB_WC_USD_RATE = 'voiceweb_usd_toman_rate';
const VOICEWEB_WC_USD_LOCAL_PRICE = '_voiceweb_before_usd_price';

function voiceweb_wc_pricing_modes(): array {
	return array( '' => 'پیش‌فرض فروشگاه', 'inquiry' => 'تماس بگیرید', 'purchase' => 'خرید آنلاین' );
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

/** Write regular, effective and lookup prices through the WooCommerce data store. */
function voiceweb_wc_pricing_set_amount( WC_Product $product, string $usd, string $rate ): void {
	$previous = $product->get_meta( VOICEWEB_WC_USD_LOCAL_PRICE, true, 'edit' );
	if ( $usd === '' ) {
		$product->delete_meta_data( VOICEWEB_WC_USD_AMOUNT );
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
	$product->set_sale_price( '' );
	$product->set_date_on_sale_from( null );
	$product->set_date_on_sale_to( null );
	$product->set_price( $price );
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
		<p>برای استفاده از قیمت معمول ووکامرس، مبلغ دلاری را خالی بگذارید. حالت تماس بگیرید مبلغ را از دید مشتری پنهان می‌کند.</p>
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
			<table class="widefat striped" style="margin-top:12px">
				<thead><tr><th scope="col">محصول</th><th scope="col">روش سفارش</th><th scope="col">مبلغ پایه (دلار)</th><th scope="col">قیمت پایه در ووکامرس</th></tr></thead>
				<tbody>
				<?php foreach ( $list->posts as $post ) :
					$product = wc_get_product( $post->ID );
					if ( ! $product ) { continue; }
					$id = $product->get_id();
					?>
					<tr>
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
						<tr><th scope="row" style="padding-inline-start:2em"><?php echo esc_html( wc_get_formatted_variation( $child, true, true ) ?: '#' . $child_id ); ?></th><td>تابع محصول اصلی</td><?php voiceweb_wc_pricing_cells( $child ); ?></tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( 'ذخیرهٔ محصولات این صفحه' ); ?>
		</form>
		<?php echo wp_kses_post( paginate_links( array( 'base' => str_replace( '987654321', '%#%', voiceweb_wc_pricing_url( array( 'catalog_page' => 987654321, 'catalog_search' => $search ) ) ), 'current' => $page, 'total' => $list->max_num_pages ) ) ); ?>
	</div>
	<?php
}

function voiceweb_wc_pricing_cells( WC_Product $product ): void {
	$regular = $product->get_regular_price( 'edit' );
	echo '<td>';
	if ( $product->is_type( array( 'simple', 'variation' ) ) && current_user_can( 'edit_product', $product->get_parent_id() ?: $product->get_id() ) ) {
		$id = $product->get_id();
		echo '<label class="screen-reader-text" for="vw-usd-' . esc_attr( $id ) . '">' . esc_html( 'مبلغ دلاری ' . $product->get_name() ) . '</label>';
		echo '<input type="text" inputmode="decimal" dir="ltr" class="small-text" id="vw-usd-' . esc_attr( $id ) . '" name="usd_amount[' . esc_attr( $id ) . ']" value="' . esc_attr( $product->get_meta( VOICEWEB_WC_USD_AMOUNT, true, 'edit' ) ) . '">';
	} else { echo '—'; }
	echo '</td><td>' . ( $regular === '' ? '—' : wp_kses_post( wc_price( $regular ) ) ) . '</td>';
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
		$amounts = $modes = array();
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
			foreach ( (array) ( $input['product_mode'] ?? array() ) as $id => $mode ) {
				$product = voiceweb_wc_pricing_editable_product( $id, array( 'simple', 'variable' ) );
				if ( ! is_string( $mode ) || ! array_key_exists( $mode, voiceweb_wc_pricing_modes() ) ) { throw new InvalidArgumentException( 'روش سفارش معتبر نیست.' ); }
				if ( $mode === $product->get_meta( '_voiceweb_purchase_mode', true, 'edit' ) ) { continue; }
				$modes[ $id ] = $mode;
				$products[ $id ] = $product;
			}
			foreach ( (array) ( $input['usd_amount'] ?? array() ) as $id => $amount ) {
				$product = $products[ $id ] ?? voiceweb_wc_pricing_editable_product( $id, array( 'simple', 'variation' ) );
				if ( ! $product->is_type( array( 'simple', 'variation' ) ) ) { throw new InvalidArgumentException( 'نوع محصول برای قیمت دلاری معتبر نیست.' ); }
				$amount = voiceweb_wc_pricing_number( $amount, 2 );
				if ( $amount === $product->get_meta( VOICEWEB_WC_USD_AMOUNT, true, 'edit' ) ) { continue; }
				$amounts[ $id ] = $amount;
				$products[ $id ] = $product;
			}
		}
		foreach ( $amounts as $amount ) { voiceweb_wc_pricing_local_amount( $amount, $rate ); }

		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { throw new RuntimeException( 'Could not start the price transaction.' ); }
		try {
			foreach ( $products as $id => $product ) {
				if ( isset( $modes[ $id ] ) ) { $product->update_meta_data( '_voiceweb_purchase_mode', $modes[ $id ] ); }
				if ( isset( $amounts[ $id ] ) ) {
					voiceweb_wc_pricing_set_amount( $product, $amounts[ $id ], $rate );
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
