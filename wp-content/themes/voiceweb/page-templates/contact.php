<?php
/**
 * Template Name: ارتباط با برند
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$page_id = get_the_ID();
	if ( post_password_required() ) {
		echo '<main class="vw-home vw-brand"><div class="vw-store-container">' . get_the_password_form() . '</div></main>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		continue;
	}
	$intro     = (string) voiceweb_brand_value( 'intro', get_the_excerpt(), $page_id );
	$image_id  = absint( voiceweb_brand_value( 'hero_image', 0, $page_id ) ) ?: (int) get_post_thumbnail_id();
	$phone     = trim( (string) voiceweb_brand_value( 'phone', '', $page_id ) );
	$digits    = array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9' );
	$phone_uri = preg_replace( '/[^0-9+]/', '', strtr( $phone, $digits ) );
	$email     = sanitize_email( (string) voiceweb_brand_value( 'email', '', $page_id ) );
	$address   = trim( (string) voiceweb_brand_value( 'address', '', $page_id ) );
	$hours     = trim( (string) voiceweb_brand_value( 'hours', '', $page_id ) );
	$instagram = esc_url( (string) voiceweb_brand_value( 'instagram_url', '', $page_id ), array( 'https', 'http' ) );
	$whatsapp  = preg_replace( '/[^0-9]/', '', strtr( (string) voiceweb_brand_value( 'whatsapp', '', $page_id ), $digits ) );
	if ( strpos( $whatsapp, '00' ) === 0 ) { $whatsapp = substr( $whatsapp, 2 ); }
	if ( preg_match( '/^09[0-9]{9}$/', $whatsapp ) ) { $whatsapp = '98' . substr( $whatsapp, 1 ); }
	$methods = array();
	if ( $phone && preg_match( '/^\+?[0-9]{6,15}$/', $phone_uri ) ) { $methods[] = array( 'label' => 'تماس تلفنی', 'value' => $phone, 'url' => 'tel:' . $phone_uri, 'note' => 'برای گفت‌وگو دربارهٔ مدل، جنس چرم و سفارش', 'ltr' => true, 'external' => false ); }
	if ( $email && is_email( $email ) ) { $methods[] = array( 'label' => 'ایمیل', 'value' => $email, 'url' => 'mailto:' . $email, 'note' => 'برای فرستادن توضیحات و پرسش‌های شما', 'ltr' => true, 'external' => false ); }
	if ( preg_match( '/^[1-9][0-9]{7,14}$/', $whatsapp ) ) { $methods[] = array( 'label' => 'واتساپ', 'value' => 'گفت‌وگو در واتساپ', 'url' => 'https://wa.me/' . $whatsapp, 'note' => 'نام یا لینک محصول را همراه پرسشتان بفرستید', 'ltr' => false, 'external' => true ); }
	if ( $instagram ) { $methods[] = array( 'label' => 'اینستاگرام', 'value' => 'دیدن صفحهٔ آوان چرم', 'url' => $instagram, 'note' => 'برای آشنایی بیشتر و ارتباط با ما', 'ltr' => false, 'external' => true ); }
	$faqs  = array_values( array_filter( (array) voiceweb_brand_value( 'faq', array(), $page_id ), static fn( $row ) => is_array( $row ) && ! empty( $row['question'] ) && ! empty( $row['answer'] ) ) );
	$shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
	$about = voiceweb_brand_page_url( 'about' );
?>
<main id="brand-contact" class="vw-home vw-brand vw-brand-contact">
	<div class="vw-store-container">
		<nav class="vw-brand-breadcrumb" aria-label="مسیر صفحه"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true">/</span><span aria-current="page"><?php the_title(); ?></span></nav>
		<header class="vw-brand-hero vw-brand-hero--contact<?php echo $image_id ? '' : ' vw-brand-hero--text'; ?>">
			<div class="vw-brand-hero__copy"><span class="vw-store-eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> / همراه انتخاب شما</span><h1><?php the_title(); ?></h1><?php if ( $intro !== '' ) : ?><p><?php echo esc_html( $intro ); ?></p><?php endif; ?>
				<div class="vw-brand-actions"><?php if ( $methods ) : ?><a class="vw-store-button" href="#brand-contact-methods">راه‌های ارتباط با ما<?php echo voiceweb_front_icon( 'arrow' ); ?></a><?php elseif ( $shop ) : ?><a class="vw-store-button" href="<?php echo esc_url( $shop ); ?>">بررسی مدل‌ها و جزئیات<?php echo voiceweb_front_icon( 'arrow' ); ?></a><?php endif; ?><?php if ( $faqs ) : ?><a class="vw-store-link" href="#brand-contact-faq">پرسش‌های پیش از تماس<?php echo voiceweb_front_icon( 'arrow' ); ?></a><?php endif; ?></div>
			</div>
			<?php if ( $image_id ) : ?><div class="vw-brand-hero__image"><?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 100vw, 45vw' ) ); ?></div><?php endif; ?>
		</header>
		<?php if ( $methods ) : ?>
		<section id="brand-contact-methods" class="vw-brand-contact-methods vw-store-section" aria-labelledby="brand-contact-methods-title"><div class="vw-brand-section-heading"><span class="vw-store-eyebrow">از پرسش شما شروع می‌کنیم</span><h2 id="brand-contact-methods-title">راهی که برای شما راحت‌تر است</h2></div>
			<div class="vw-brand-contact-cards"><?php foreach ( $methods as $method ) : ?><a class="vw-brand-contact-card" href="<?php echo esc_url( $method['url'] ); ?>"<?php echo $method['external'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><span class="vw-store-eyebrow"><?php echo esc_html( $method['label'] ); ?></span><strong<?php echo $method['ltr'] ? ' dir="ltr"' : ''; ?>><?php echo esc_html( $method['value'] ); ?></strong><span class="vw-brand-contact-card__note"><?php echo esc_html( $method['note'] ); ?></span><span class="vw-brand-contact-card__arrow" aria-hidden="true"><?php echo voiceweb_front_icon( 'arrow' ); ?></span></a><?php endforeach; ?></div>
		</section>
		<?php endif; ?>
		<?php if ( $address || $hours ) : ?>
		<section class="vw-brand-contact-details vw-store-section" aria-label="اطلاعات ارتباط با آوان چرم"><?php if ( $address ) : ?><div><span class="vw-store-eyebrow">نشانی</span><p><?php echo nl2br( esc_html( $address ) ); ?></p></div><?php endif; ?><?php if ( $hours ) : ?><div><span class="vw-store-eyebrow">زمان پاسخ‌گویی</span><p><?php echo nl2br( esc_html( $hours ) ); ?></p></div><?php endif; ?></section>
		<?php endif; ?>
		<?php if ( trim( get_the_content() ) !== '' ) : ?>
		<section class="vw-brand-story vw-store-section" aria-labelledby="brand-contact-guide-title"><div class="vw-brand-section-heading"><span class="vw-store-eyebrow">برای گفت‌وگویی دقیق‌تر</span><h2 id="brand-contact-guide-title">چه چیزی را با ما در میان بگذارید؟</h2><p>نام محصول و کاربردی که در نظر دارید، کمک می‌کند پرسشتان روشن‌تر باشد.</p></div><div class="vw-brand-prose"><?php the_content(); ?></div></section>
		<?php endif; ?>
		<?php if ( $faqs ) : ?>
		<section id="brand-contact-faq" class="vw-store-faq vw-brand-faq" aria-labelledby="brand-contact-faq-title"><div><span class="vw-store-eyebrow">پیش از گفت‌وگو</span><h2 id="brand-contact-faq-title">شاید پاسخ شما همین‌جا باشد</h2><p>چند نکته برای انتخاب مدل و پیگیری سفارش.</p></div><div><?php foreach ( $faqs as $faq ) : ?><details><summary><?php echo esc_html( $faq['question'] ); ?><?php echo voiceweb_front_icon( 'plus' ); ?></summary><div><?php echo wpautop( esc_html( $faq['answer'] ) ); ?></div></details><?php endforeach; ?></div></section>
		<?php endif; ?>
		<section class="vw-brand-next vw-store-section" aria-labelledby="brand-contact-next-title"><div><span class="vw-store-eyebrow">آشنایی بیشتر با آوان چرم</span><h2 id="brand-contact-next-title">از داستان ساخت تا انتخاب شما</h2><p>دربارهٔ مواد و مسیر ساخت بخوانید یا مدل‌های فروشگاه را کنار هم ببینید.</p></div><div class="vw-brand-actions"><?php if ( $shop ) : ?><a class="vw-store-button vw-store-button--light" href="<?php echo esc_url( $shop ); ?>">مشاهدهٔ محصولات<?php echo voiceweb_front_icon( 'arrow' ); ?></a><?php endif; ?><?php if ( $about ) : ?><a class="vw-store-link" href="<?php echo esc_url( $about ); ?>">داستان آوان چرم<?php echo voiceweb_front_icon( 'arrow' ); ?></a><?php endif; ?></div></section>
	</div>
</main>
<?php endwhile; get_footer(); ?>
