<?php
/** Shared WooCommerce card for the front page, archives, upsells and related products. */
defined( 'ABSPATH' ) || exit;
$card_product = $args['product'] ?? ( $GLOBALS['product'] ?? null );
if ( ! $card_product instanceof WC_Product || ! $card_product->is_visible() ) { return; }
$previous_product = $GLOBALS['product'] ?? null;
$previous_post = $GLOBALS['post'] ?? null;
$GLOBALS['post'] = get_post( $card_product->get_id() );
setup_postdata( $GLOBALS['post'] );
$GLOBALS['product'] = $card_product;
?>
<li <?php wc_product_class( 'vw-product-card' . ( function_exists( 'voiceweb_wc_product_is_inquiry' ) && voiceweb_wc_product_is_inquiry( $card_product ) ? ' vw-product-card--inquiry' : ' vw-product-card--icon' ), $card_product ); ?>>
	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>
	<div class="vw-product-card__media"><?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?></div>
	<div class="vw-product-card__body">
		<?php do_action( 'woocommerce_shop_loop_item_title' ); ?>
		<?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>
	</div>
	<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
	<?php voiceweb_wc_product_card_wishlist( $card_product ); ?>
	<p class="vw-wishlist-feedback screen-reader-text" role="status" aria-live="polite" hidden></p>
</li>
<?php
$GLOBALS['post'] = $previous_post;
if ( $previous_post instanceof WP_Post ) { setup_postdata( $previous_post ); }
$GLOBALS['product'] = $previous_product;
