<?php
/** Shared product presentation. WooCommerce remains responsible for prices, stock and checkout. */
defined( 'ABSPATH' ) || exit;

function voiceweb_wc_display_text( string $english, string $persian ): string {
	$translated = cn_translate( $english );
	return $translated !== $english ? $translated : ( cn_is_persian_language() ? $persian : $english );
}

/** Compare matched regular/sale prices, including each variation's own price pair. */
function voiceweb_wc_product_discount( WC_Product $product ): int {
	if ( ! $product->is_on_sale() ) { return 0; }
	$discount = 0;
	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		foreach ( $prices['price'] as $id => $current ) {
			$variation = wc_get_product( $id );
			if ( ! $variation instanceof WC_Product_Variation || ! $variation->is_on_sale() ) { continue; }
			$regular = (float) ( $prices['regular_price'][ $id ] ?? 0 );
			$sale = $prices['sale_price'][ $id ] ?? '';
			if ( $sale !== '' && $regular > 0 && (float) $sale < $regular && (float) $current === (float) $sale ) {
				$discount = max( $discount, (int) floor( 100 * ( $regular - (float) $sale ) / $regular ) );
			}
		}
	} else {
		$regular = (float) $product->get_regular_price();
		$sale = $product->get_sale_price();
		if ( $regular > 0 && $sale !== '' && (float) $sale < $regular ) { $discount = (int) floor( 100 * ( $regular - (float) $sale ) / $regular ); }
	}
	return max( 0, min( 100, $discount ) );
}

function voiceweb_wc_product_sale_badge( string $html, WP_Post $post, WC_Product $product ): string {
	$discount = voiceweb_wc_product_discount( $product );
	if ( ! $discount ) { return $html; }
	$label = ( $product->is_type( 'variable' ) ? voiceweb_wc_display_text( 'Up to ', 'تا ' ) : '' ) . cn_localize_digits( (string) $discount ) . ( cn_is_persian_language() ? '٪' : '%' );
	return '<span class="onsale">' . esc_html( $label ) . '</span>';
}

/** A noninteractive color preview: actual selection still happens in the native variation form. */
function voiceweb_wc_product_card_colors(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! taxonomy_exists( 'pa_color' ) ) { return; }
	$terms = wc_get_product_terms( $product->get_id(), 'pa_color', array( 'fields' => 'all' ) );
	if ( is_wp_error( $terms ) || ! $terms ) { return; }
	echo '<div class="vw-product-card__colors" aria-label="' . esc_attr( voiceweb_wc_display_text( 'Available colors', 'رنگ‌های محصول' ) ) . '">';
	foreach ( array_slice( $terms, 0, 4 ) as $term ) {
		$color = sanitize_hex_color( get_term_meta( $term->term_id, 'product_attribute_color', true ) );
		if ( ! $color ) { continue; }
		echo '<i style="--swatch:' . esc_attr( $color ) . '" title="' . esc_attr( $term->name ) . '"><span class="screen-reader-text">' . esc_html( $term->name ) . '</span></i>';
	}
	if ( count( $terms ) > 4 ) { echo '<span>+' . esc_html( cn_localize_digits( (string) ( count( $terms ) - 4 ) ) ) . '</span>'; }
	echo '</div>';
}

/** Card favorites use the same customer-owned native Store API as the detail page. */
function voiceweb_wc_product_card_wishlist( WC_Product $product ): void {
	if ( ! voiceweb_wc_wishlist_enabled() || ! $product->is_type( array( 'simple', 'variable' ) ) ) { return; }
	$label = voiceweb_wc_display_text( 'Add to favorites', 'افزودن به علاقه‌مندی‌ها' );
	if ( $product->is_type( 'variable' ) ) {
		echo '<a class="vw-product-card__favorite" href="' . esc_url( $product->get_permalink() ) . '#vw-product-options" aria-label="' . esc_attr( voiceweb_wc_display_text( 'Choose options to save this product', 'انتخاب رنگ و افزودن به علاقه‌مندی‌ها' ) ) . '">' . voiceweb_wc_icon( 'heart' ) . '</a>';
	} elseif ( ! is_user_logged_in() ) {
		if ( function_exists( 'voiceweb_auth_account_link' ) ) {
			$link = voiceweb_auth_account_link( array( 'class' => 'vw-product-card__favorite', 'label' => $label, 'redirect_to' => $product->get_permalink() ) );
			echo str_replace( '>' . esc_html( $label ) . '</a>', '>' . voiceweb_wc_icon( 'heart' ) . '<span class="screen-reader-text">' . esc_html( $label ) . '</span></a>', $link );
		} else { echo '<a class="vw-product-card__favorite" href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" aria-label="' . esc_attr( $label ) . '">' . voiceweb_wc_icon( 'heart' ) . '</a>'; }
	} else {
		echo '<button class="vw-product-card__favorite" type="button" data-vw-wishlist-product="' . esc_attr( $product->get_id() ) . '" data-vw-wishlist-variable="false" aria-label="' . esc_attr( $label ) . '" aria-pressed="false" disabled>' . voiceweb_wc_icon( 'heart' ) . '<span class="screen-reader-text">' . esc_html( $label ) . '</span></button>';
	}
}

function voiceweb_wc_product_summary_intro(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) { return; }
	$terms = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'fields' => 'all' ) );
	if ( is_wp_error( $terms ) || ! $terms ) { return; }
	$term = reset( $terms ); $url = get_term_link( $term );
	if ( ! is_wp_error( $url ) ) { echo '<a class="vw-product-collection" href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>'; }
}

function voiceweb_wc_product_deadline( WC_Product $product ): int {
	$date = $product->get_date_on_sale_to();
	return $product->is_on_sale() && $date && $date->getTimestamp() > time() ? $date->getTimestamp() : 0;
}

function voiceweb_wc_product_offer_timer(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ( ! $product->is_type( 'variable' ) && ! voiceweb_wc_product_deadline( $product ) ) ) { return; }
	$deadline = $product->is_type( 'variable' ) ? 0 : voiceweb_wc_product_deadline( $product );
	?>
	<div class="vw-product-offer" data-vw-product-offer <?php echo $deadline ? '' : 'hidden'; ?>>
		<div><strong><?php echo esc_html( voiceweb_wc_display_text( 'A special opportunity', 'فرصتی برای یک انتخاب ویژه' ) ); ?></strong><span><?php echo esc_html( voiceweb_wc_display_text( 'This offer ends in', 'زمان باقی‌ماندهٔ این تخفیف' ) ); ?></span></div>
		<time class="vw-product-offer__time" data-vw-product-deadline="<?php echo esc_attr( $deadline ); ?>" <?php echo $deadline ? 'datetime="' . esc_attr( gmdate( 'c', $deadline ) ) . '"' : ''; ?>></time>
		<?php if ( $deadline ) : ?><noscript><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' H:i', $deadline ) ); ?></noscript><?php endif; ?>
	</div>
	<?php
}

function voiceweb_wc_product_variation_deadline( array $data, WC_Product $product, WC_Product_Variation $variation ): array {
	$data['voiceweb_sale_deadline'] = voiceweb_wc_product_deadline( $variation );
	return $data;
}

function voiceweb_wc_product_options_anchor(): void { echo '<span id="vw-product-options" class="vw-product-options-anchor" aria-hidden="true"></span>'; }

/** Preserve the native href, AJAX classes, data and accessible description; only replace its visual label. */
function voiceweb_wc_product_loop_icon( string $html, WC_Product $product ): string {
	if ( function_exists( 'voiceweb_wc_product_is_inquiry' ) && voiceweb_wc_product_is_inquiry( $product ) ) { return $html; }
	$icon = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ? 'bag' : 'arrow';
	$count = 0;
	$updated = preg_replace_callback( '/(<a\b[^>]*>)([^<]*)(<\/a>)/i', static function ( array $match ) use ( $icon ): string {
		return $match[1] . voiceweb_wc_icon( $icon ) . '<span class="screen-reader-text">' . $match[2] . '</span>' . $match[3];
	}, $html, 1, $count );
	// Extensions can provide richer controls. Leave those controls intact.
	if ( ! $count || ! is_string( $updated ) ) { return $html; }
	$processor = new WP_HTML_Tag_Processor( $updated );
	if ( $processor->next_tag( 'A' ) ) {
		$processor->add_class( 'vw-product-card__buy' );
		if ( $icon === 'arrow' ) { $processor->add_class( 'vw-product-card__buy--options' ); }
		$processor->set_attribute( 'title', $product->add_to_cart_text() );
		if ( ! $processor->get_attribute( 'aria-label' ) ) { $processor->set_attribute( 'aria-label', $product->add_to_cart_description() ); }
	}
	return $processor->get_updated_html();
}

add_filter( 'woocommerce_sale_flash', 'voiceweb_wc_product_sale_badge', 20, 3 );
add_action( 'woocommerce_after_shop_loop_item_title', 'voiceweb_wc_product_card_colors', 20 );
add_action( 'woocommerce_single_product_summary', 'voiceweb_wc_product_summary_intro', 4 );
add_action( 'woocommerce_single_product_summary', 'voiceweb_wc_product_offer_timer', 11 );
add_action( 'woocommerce_before_add_to_cart_form', 'voiceweb_wc_product_options_anchor', 1 );
add_filter( 'woocommerce_available_variation', 'voiceweb_wc_product_variation_deadline', 20, 3 );
add_filter( 'woocommerce_loop_add_to_cart_link', 'voiceweb_wc_product_loop_icon', 40, 2 );
