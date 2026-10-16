<?php
/** Buying advice and discovery links on the native category archive. */
defined( 'ABSPATH' ) || exit;
if ( ! is_tax( 'product_cat' ) || ! function_exists( 'voiceweb_category_value' ) ) { return; }
$term = get_queried_object();
if ( ! $term instanceof WP_Term ) { return; }
$guide = voiceweb_category_value( 'guide', '', $term->term_id );
$tips = (array) voiceweb_category_value( 'tips', array(), $term->term_id );
$faqs = (array) voiceweb_category_value( 'faq', array(), $term->term_id );
$categories = voiceweb_category_related_terms( $term->term_id );
$gifts = voiceweb_category_related_gifts( $term->term_id );
$show_guide = voiceweb_category_show_guide() && ( $guide || $tips || $faqs );
if ( ! $show_guide && ! $categories && ! $gifts ) { return; }
?>
<div class="vw-category-guide">
	<?php if ( $show_guide ) : ?>
	<section id="category-advice" class="vw-category-advice" aria-labelledby="category-advice-title">
		<header><span class="vw-eyebrow">انتخاب آگاهانه، همراهی هر روز</span><h2 id="category-advice-title">راهنمای خرید <?php echo esc_html( $term->name ); ?></h2></header>
		<?php if ( $tips ) : ?><div class="vw-category-tips">
			<?php foreach ( $tips as $i => $tip ) : if ( empty( $tip['title'] ) || empty( $tip['text'] ) ) { continue; } ?>
			<article><span aria-hidden="true"><?php echo esc_html( number_format_i18n( $i + 1 ) ); ?></span><h3><?php echo esc_html( $tip['title'] ); ?></h3><p><?php echo esc_html( $tip['text'] ); ?></p></article>
			<?php endforeach; ?>
		</div><?php endif; ?>
		<?php if ( is_string( $guide ) && trim( $guide ) !== '' ) : ?><div class="vw-category-prose"><?php echo wp_kses_post( wpautop( $guide ) ); ?></div><?php endif; ?>
	</section>
	<?php if ( $faqs ) : ?>
	<section class="vw-category-faq" aria-labelledby="category-faq-title"><div><span class="vw-eyebrow">پیش از انتخاب</span><h2 id="category-faq-title">پرسش‌های خرید <?php echo esc_html( $term->name ); ?></h2></div><div>
		<?php foreach ( $faqs as $faq ) : if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) { continue; } ?>
		<details><summary><?php echo esc_html( $faq['question'] ); ?><span aria-hidden="true">+</span></summary><div><?php echo wpautop( esc_html( $faq['answer'] ) ); ?></div></details>
		<?php endforeach; ?>
	</div></section>
	<?php endif; ?>
	<?php endif; ?>
	<?php if ( $categories || $gifts ) : ?>
	<nav class="vw-category-discovery" aria-label="دسته‌ها و راهنماهای مرتبط">
		<?php if ( $categories ) : ?><section aria-labelledby="category-related-title"><h2 id="category-related-title">انتخاب‌های مکمل</h2><div><?php foreach ( $categories as $related ) : ?><a href="<?php echo esc_url( get_term_link( $related ) ); ?>"><?php echo esc_html( $related->name ); ?><?php echo voiceweb_wc_icon( 'arrow' ); ?></a><?php endforeach; ?></div></section><?php endif; ?>
		<?php if ( $gifts ) : ?><section aria-labelledby="category-gifts-title"><h2 id="category-gifts-title">برای هدیه انتخاب می‌کنید؟</h2><div><?php foreach ( $gifts as $gift ) : ?><a href="<?php echo esc_url( get_permalink( $gift ) ); ?>"><?php echo esc_html( get_the_title( $gift ) ); ?><?php echo voiceweb_wc_icon( 'arrow' ); ?></a><?php endforeach; ?></div></section><?php endif; ?>
	</nav>
	<?php endif; ?>
</div>
