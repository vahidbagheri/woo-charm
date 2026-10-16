<?php
/**
 * Template Name: راهنمای خرید هدیه و مناسبت‌ها
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$page_id = get_the_ID();
	if ( post_password_required() ) {
		echo '<main class="vw-home"><div class="vw-store-container">' . get_the_password_form() . '</div></main>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		continue;
	}
	$products   = voiceweb_gift_products( $page_id );
	$categories = voiceweb_gift_categories( $page_id );
	$related    = voiceweb_gift_related( $page_id );
	$tips       = array_values( array_filter( (array) voiceweb_gift_value( 'tips', array(), $page_id ), static fn( $row ) => is_array( $row ) && ! empty( $row['title'] ) && ! empty( $row['text'] ) ) );
	$faqs       = array_values( array_filter( (array) voiceweb_gift_value( 'faq', array(), $page_id ), static fn( $row ) => is_array( $row ) && ! empty( $row['question'] ) && ! empty( $row['answer'] ) ) );
	$image_id   = absint( voiceweb_gift_value( 'hero_image', 0, $page_id ) ) ?: (int) get_post_thumbnail_id();
	$hub_id     = voiceweb_gift_hub_id();
	$has_guide  = trim( get_the_content() ) !== '' || $tips || trim( (string) voiceweb_gift_value( 'guide_intro', '', $page_id ) ) !== '';
?>
<main id="gift-guide" class="vw-home vw-gift">
	<div class="vw-store-container">
		<nav class="vw-gift-breadcrumb" aria-label="مسیر صفحه">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true">/</span>
			<?php foreach ( array_reverse( get_post_ancestors( $page_id ) ) as $ancestor_id ) : $ancestor = get_post( $ancestor_id ); if ( ! $ancestor instanceof WP_Post || $ancestor->post_status !== 'publish' || $ancestor->post_password ) { continue; } ?>
			<a href="<?php echo esc_url( get_permalink( $ancestor_id ) ); ?>"><?php echo esc_html( get_the_title( $ancestor_id ) ); ?></a><span aria-hidden="true">/</span>
			<?php endforeach; ?>
			<span aria-current="page"><?php the_title(); ?></span>
		</nav>
		<header class="vw-gift-hero<?php echo $image_id ? '' : ' vw-gift-hero--text'; ?>">
			<div class="vw-gift-hero__copy">
				<span class="vw-store-eyebrow"><?php echo esc_html( voiceweb_gift_value( 'eyebrow', 'هدیه‌ای با فکر، برای کسی که دوستش دارید', $page_id ) ); ?></span>
				<h1><?php the_title(); ?></h1>
				<p><?php echo esc_html( voiceweb_gift_value( 'intro', get_the_excerpt(), $page_id ) ); ?></p>
				<div class="vw-gift-hero__actions">
					<a class="vw-store-button" href="#gift-products"><?php echo esc_html( voiceweb_gift_value( 'primary_label', 'دیدن پیشنهادهای هدیه', $page_id ) ); ?><?php echo voiceweb_front_icon( 'arrow' ); ?></a>
					<?php if ( $has_guide ) : ?><a class="vw-store-link" href="#gift-advice">راهنمای انتخاب<?php echo voiceweb_front_icon( 'arrow' ); ?></a><?php endif; ?>
				</div>
			</div>
			<?php if ( $image_id ) : ?>
			<div class="vw-gift-hero__image"><?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 100vw, 50vw' ) ); ?></div>
			<?php endif; ?>
		</header>

		<nav class="vw-gift-jump" aria-label="بخش‌های راهنمای هدیه">
			<a href="#gift-products">پیشنهادهای خرید</a>
			<?php if ( $has_guide ) : ?><a href="#gift-advice">چطور انتخاب کنیم؟</a><?php endif; ?>
			<?php if ( $faqs ) : ?><a href="#gift-faq">پرسش‌های شما</a><?php endif; ?>
			<?php if ( $related ) : ?><a href="#gift-related">مناسبت‌های دیگر</a><?php endif; ?>
		</nav>

		<section id="gift-products" class="vw-store-section" aria-labelledby="gift-products-title">
			<div class="vw-store-heading">
				<div><span class="vw-store-eyebrow">برای انتخابی متناسب با سلیقهٔ او</span><h2 id="gift-products-title"><?php echo esc_html( voiceweb_gift_value( 'products_title', 'پیشنهادهای این مناسبت', $page_id ) ); ?></h2><p>قیمت، رنگ و جزئیات هر مدل را در صفحهٔ محصول بررسی کنید.</p></div>
			</div>
			<?php if ( $categories ) : ?>
			<div class="vw-gift-categories" aria-label="دیدن همهٔ مدل‌های هر دسته">
				<?php foreach ( $categories as $category ) : $url = get_term_link( $category ); if ( is_wp_error( $url ) ) { continue; } ?>
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $category->name ); ?><?php echo voiceweb_front_icon( 'arrow' ); ?></a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<?php if ( $products ) : ?>
			<ul class="products vw-product-grid" style="--vw-grid-columns:4">
				<?php foreach ( $products as $item ) { get_template_part( 'template-parts/woocommerce/product-card', null, array( 'product' => $item ) ); } ?>
			</ul>
			<?php else : ?>
			<div class="vw-gift-empty"><p>در حال حاضر پیشنهادی در این مجموعه نمایش داده نمی‌شود. می‌توانید مدل‌های دسته‌های بالا را بررسی کنید یا از راهنمای انتخاب کمک بگیرید.</p></div>
			<?php endif; ?>
		</section>

		<?php if ( $has_guide ) : ?>
		<section id="gift-advice" class="vw-store-section vw-gift-advice" aria-labelledby="gift-advice-title">
			<div class="vw-gift-advice__heading"><span class="vw-store-eyebrow">یک انتخاب شخصی‌تر</span><h2 id="gift-advice-title"><?php echo esc_html( voiceweb_gift_value( 'guide_title', 'چه هدیه‌ای مناسب‌تر است؟', $page_id ) ); ?></h2><p><?php echo esc_html( voiceweb_gift_value( 'guide_intro', '', $page_id ) ); ?></p></div>
			<?php if ( $tips ) : ?><div class="vw-gift-tips">
				<?php foreach ( $tips as $i => $tip ) : if ( empty( $tip['title'] ) || empty( $tip['text'] ) ) { continue; } ?>
				<article><span class="vw-gift-tips__number" aria-hidden="true"><?php echo esc_html( number_format_i18n( $i + 1 ) ); ?></span><h3><?php echo esc_html( $tip['title'] ); ?></h3><p><?php echo esc_html( $tip['text'] ); ?></p></article>
				<?php endforeach; ?>
			</div><?php endif; ?>
			<div class="vw-gift-prose"><?php the_content(); ?></div>
		</section>
		<?php endif; ?>

		<?php if ( $faqs ) : ?>
		<section id="gift-faq" class="vw-store-faq" aria-labelledby="gift-faq-title">
			<div><span class="vw-store-eyebrow">پیش از خرید</span><h2 id="gift-faq-title">پاسخ به پرسش‌های شما</h2><p>چند نکته برای انتخاب هدیه‌ای که بیشتر به کار می‌آید.</p></div>
			<div><?php foreach ( $faqs as $faq ) : if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) { continue; } ?>
				<details><summary><?php echo esc_html( $faq['question'] ); ?><?php echo voiceweb_front_icon( 'plus' ); ?></summary><div><?php echo wpautop( esc_html( $faq['answer'] ) ); ?></div></details>
			<?php endforeach; ?></div>
		</section>
		<?php endif; ?>

		<?php if ( $related ) : ?>
		<section id="gift-related" class="vw-store-section" aria-labelledby="gift-related-title">
			<div class="vw-store-heading"><h2 id="gift-related-title">برای مناسبت‌های دیگر</h2><?php if ( $hub_id ) { voiceweb_front_link( array( 'url' => get_permalink( $hub_id ), 'title' => 'همهٔ مناسبت‌ها' ) ); } ?></div>
			<?php get_template_part( 'template-parts/gifts/cards', null, array( 'pages' => $related ) ); ?>
		</section>
		<?php endif; ?>
	</div>
</main>
<?php endwhile; get_footer(); ?>
