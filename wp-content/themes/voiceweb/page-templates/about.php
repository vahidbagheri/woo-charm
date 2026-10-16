<?php
/**
 * Template Name: دربارهٔ برند
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
	$image_id = absint( voiceweb_brand_value( 'hero_image', 0, $page_id ) ) ?: (int) get_post_thumbnail_id();
	$intro    = (string) voiceweb_brand_value( 'intro', get_the_excerpt(), $page_id );
	$process  = array_values( array_filter( (array) voiceweb_brand_value( 'process', array(), $page_id ), static fn( $row ) => is_array( $row ) && ! empty( $row['title'] ) && ! empty( $row['text'] ) ) );
	$leathers = array_values( array_filter( (array) voiceweb_brand_value( 'leather', array(), $page_id ), static fn( $row ) => is_array( $row ) && ! empty( $row['title'] ) && ! empty( $row['text'] ) ) );
	$faqs     = array_values( array_filter( (array) voiceweb_brand_value( 'faq', array(), $page_id ), static fn( $row ) => is_array( $row ) && ! empty( $row['question'] ) && ! empty( $row['answer'] ) ) );
	$contact  = voiceweb_brand_page_url( 'contact' );
	$shop     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
	$category_specs = array( 'leather-men-bags' => 'کیف مردانه', 'leather-women-bags' => 'کیف زنانه', 'leather-men-wallets' => 'کیف پول مردانه', 'leather-women-wallets' => 'کیف پول زنانه' );
?>
<main id="brand-about" class="vw-home vw-brand vw-brand-about">
	<div class="vw-store-container">
		<nav class="vw-brand-breadcrumb" aria-label="مسیر صفحه"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true">/</span><span aria-current="page"><?php the_title(); ?></span></nav>
		<header class="vw-brand-hero<?php echo $image_id ? '' : ' vw-brand-hero--text'; ?>">
			<div class="vw-brand-hero__copy">
				<span class="vw-store-eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> / داستان ما</span>
				<h1><?php the_title(); ?></h1>
				<?php if ( $intro !== '' ) : ?><p><?php echo esc_html( $intro ); ?></p><?php endif; ?>
				<div class="vw-brand-actions">
					<?php if ( $shop ) : ?><a class="vw-store-button" href="<?php echo esc_url( $shop ); ?>">دیدن ساخته‌های ما<?php echo voiceweb_front_icon( 'arrow' ); ?></a><?php endif; ?>
					<?php if ( $process ) : ?><a class="vw-store-link" href="#brand-process">از انتخاب چرم تا ساخت<?php echo voiceweb_front_icon( 'arrow' ); ?></a><?php endif; ?>
				</div>
			</div>
			<?php if ( $image_id ) : ?><div class="vw-brand-hero__image"><?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 100vw, 55vw' ) ); ?></div><?php endif; ?>
		</header>
		<?php if ( trim( get_the_content() ) !== '' ) : ?>
		<section class="vw-brand-story vw-store-section" aria-labelledby="brand-story-title">
			<div class="vw-brand-section-heading"><span class="vw-store-eyebrow">همهٔ مسیر، در تیم خودمان</span><h2 id="brand-story-title">از جنس انتخاب،<br>تا جزئیات ساخت</h2><p>چیزی که دربارهٔ ما می‌خوانید، در انتخاب مواد و مسیر ساخت محصولاتمان معنا پیدا می‌کند.</p></div>
			<div class="vw-brand-prose"><?php the_content(); ?></div>
		</section>
		<?php endif; ?>
		<?php if ( $process ) : ?>
		<section id="brand-process" class="vw-brand-process vw-store-section" aria-labelledby="brand-process-title">
			<div class="vw-brand-section-heading"><span class="vw-store-eyebrow">از صفر تا صد</span><h2 id="brand-process-title">یک مسیر پیوسته برای ساخت هر محصول</h2><p>مراحل ساخت در تیم خودمان پیش می‌روند؛ از فکر اولیه تا آماده‌شدن محصول.</p></div>
			<ol class="vw-brand-steps">
				<?php foreach ( $process as $index => $step ) : ?><li><span class="vw-brand-step-number" aria-hidden="true"><?php echo esc_html( number_format_i18n( $index + 1 ) ); ?></span><h3><?php echo esc_html( $step['title'] ); ?></h3><p><?php echo esc_html( $step['text'] ); ?></p></li><?php endforeach; ?>
			</ol>
		</section>
		<?php endif; ?>
		<?php if ( $leathers ) : ?>
		<section class="vw-brand-leather vw-store-section" aria-labelledby="brand-leather-title">
			<div class="vw-brand-section-heading"><span class="vw-store-eyebrow">چرم طبیعی، متناسب با هر محصول</span><h2 id="brand-leather-title">جنس را با نیاز محصول انتخاب می‌کنیم</h2><p>در آوان چرم، چرم طبیعی گاوی و بزی با توجه به نیاز هر محصول انتخاب می‌شوند.</p></div>
			<div class="vw-brand-materials"><?php foreach ( $leathers as $leather ) : ?><article><span class="vw-brand-material-mark" aria-hidden="true"><?php echo voiceweb_front_icon( 'check' ); ?></span><h3><?php echo esc_html( $leather['title'] ); ?></h3><p><?php echo esc_html( $leather['text'] ); ?></p></article><?php endforeach; ?></div>
		</section>
		<?php endif; ?>
		<?php if ( $faqs ) : ?>
		<section class="vw-store-faq vw-brand-faq" aria-labelledby="brand-faq-title">
			<div><span class="vw-store-eyebrow">بیشتر دربارهٔ ما</span><h2 id="brand-faq-title">پرسش‌های شما، پاسخ‌های ما</h2><p>برای شناخت بهتر مواد، ساخت و انتخاب محصول.</p></div>
			<div><?php foreach ( $faqs as $faq ) : ?><details><summary><?php echo esc_html( $faq['question'] ); ?><?php echo voiceweb_front_icon( 'plus' ); ?></summary><div><?php echo wpautop( esc_html( $faq['answer'] ) ); ?></div></details><?php endforeach; ?></div>
		</section>
		<?php endif; ?>
		<section class="vw-brand-next vw-store-section" aria-labelledby="brand-next-title">
			<div><span class="vw-store-eyebrow">حالا از نزدیک‌تر ببینید</span><h2 id="brand-next-title">همراه بعدی شما، کدام است؟</h2><p>مدل‌ها را بر اساس کاربرد، اندازه و سلیقهٔ خودتان مقایسه کنید.</p></div>
			<div class="vw-brand-next__links"><div class="vw-brand-category-links"><?php foreach ( $category_specs as $slug => $label ) : $url = voiceweb_brand_category_url( $slug ); if ( ! $url ) { continue; } ?><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?><?php echo voiceweb_front_icon( 'arrow' ); ?></a><?php endforeach; ?></div><?php if ( $contact ) : ?><a class="vw-store-link" href="<?php echo esc_url( $contact ); ?>">گفت‌وگو با آوان چرم<?php echo voiceweb_front_icon( 'arrow' ); ?></a><?php endif; ?></div>
		</section>
	</div>
</main>
<?php endwhile; get_footer(); ?>
