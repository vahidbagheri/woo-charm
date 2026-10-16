<?php
/** Homepage discovery for gift guides; automatically reflects published pages. */
defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'voiceweb_gift_hub_id' ) || ! function_exists( 'voiceweb_gift_pages' ) ) { return; }
$hub_id = voiceweb_gift_hub_id();
$pages = voiceweb_gift_pages( 4 );
if ( ! $pages || ! $hub_id || ! voiceweb_front_enabled( 'gifts' ) ) { return; }
?>
<section class="vw-store-section" aria-labelledby="home-gifts-title">
	<div class="vw-store-heading"><div><span class="vw-store-eyebrow">هدیه‌ای برای هر بهانه</span><h2 id="home-gifts-title">از مناسبت شروع کنید</h2><p>پیشنهادهای خرید و راهنمای انتخاب کیف و کیف پول برای عزیزانتان.</p></div><?php voiceweb_front_link( array( 'url' => get_permalink( $hub_id ), 'title' => 'همهٔ مناسبت‌ها' ) ); ?></div>
	<?php get_template_part( 'template-parts/gifts/cards', null, array( 'pages' => $pages ) ); ?>
</section>
