<?php
defined( 'ABSPATH' ) || exit;
$section = ( $args['section'] ?? '' ) === 'offers' ? 'offers' : 'arrivals';
$products = voiceweb_front_products( $section ); if ( ! $products ) { return; }
$deadline = $section === 'offers' ? voiceweb_front_offer_deadline( $products ) : 0;
$remaining = max( 0, $deadline - time() );
$units = array( 'days' => array( (int) floor( $remaining / DAY_IN_SECONDS ), 'روز' ), 'hours' => array( (int) floor( $remaining % DAY_IN_SECONDS / HOUR_IN_SECONDS ), 'ساعت' ), 'minutes' => array( (int) floor( $remaining % HOUR_IN_SECONDS / MINUTE_IN_SECONDS ), 'دقیقه' ), 'seconds' => array( $remaining % MINUTE_IN_SECONDS, 'ثانیه' ) );
?>
<section class="vw-store-section vw-store-products" aria-labelledby="home-<?php echo esc_attr( $section ); ?>-title">
	<div class="vw-store-heading">
		<div><h2 id="home-<?php echo esc_attr( $section ); ?>-title"><?php echo esc_html( voiceweb_front_value( $section . '_title', $section === 'offers' ? 'پیشنهادهای ویژه' : 'تازه‌های کالکشن' ) ); ?></h2><?php if ( $section === 'offers' ) : ?><p><?php echo esc_html( voiceweb_front_value( 'offers_description' ) ); ?></p><?php endif; ?></div>
		<?php if ( $deadline ) : ?><div class="vw-store-countdown" data-vw-offer-deadline="<?php echo esc_attr( $deadline * 1000 ); ?>" data-vw-server-now="<?php echo esc_attr( time() * 1000 ); ?>"><span>تا پایان تخفیف</span><time datetime="<?php echo esc_attr( gmdate( 'c', $deadline ) ); ?>"><?php foreach ( $units as $unit => $value ) : ?><b data-vw-time-<?php echo esc_attr( $unit ); ?>><?php echo esc_html( cn_localize_digits( str_pad( (string) $value[0], 2, '0', STR_PAD_LEFT ) ) ); ?></b><small><?php echo esc_html( $value[1] ); ?></small><?php endforeach; ?></time><span class="vw-store-sr">پایان تخفیف: <?php echo esc_html( wp_date( 'Y/m/d H:i', $deadline ) ); ?></span></div><?php endif; ?>
		<?php voiceweb_front_link( array( 'url' => $args['shop'], 'title' => 'مشاهدهٔ همه' ) ); ?>
	</div>
	<ul class="products vw-product-grid" style="--vw-grid-columns:<?php echo esc_attr( min( 6, count( $products ) ) ); ?>">
		<?php foreach ( $products as $item ) { get_template_part( 'template-parts/woocommerce/product-card', null, array( 'product' => $item ) ); } ?>
	</ul>
</section>
