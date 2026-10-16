<?php
defined( 'ABSPATH' ) || exit;
$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$blog_id = absint( get_option( 'page_for_posts' ) );
$blog_url = $blog_id ? get_permalink( $blog_id ) : get_post_type_archive_link( 'post' );
$slides = array_values( array_filter( (array) voiceweb_front_value( 'slides', array() ), static fn( $slide ) => ! empty( $slide['title'] ) ) );
if ( ! $slides ) { $slides[] = array( 'eyebrow' => 'LEATHER COLLECTION', 'title' => "جزئیات،\nسبک زندگی شماست", 'description' => 'همراه‌هایی برای روزمره؛ انتخابی با توجه به جزئیات.', 'primary' => array( 'url' => $shop, 'title' => 'مشاهدهٔ محصولات' ) ); }
?>
<main id="front-page" class="vw-home">
	<div class="vw-store-container">
		<?php get_template_part( 'template-parts/front/hero', null, array( 'slides' => $slides ) ); ?>
		<?php get_template_part( 'template-parts/front/collections' ); ?>
		<?php get_template_part( 'template-parts/front/categories', null, array( 'categories' => voiceweb_front_categories(), 'shop' => $shop ) ); ?>
		<?php get_template_part( 'template-parts/front/gifts' ); ?>
		<?php if ( voiceweb_front_enabled( 'offers' ) ) { get_template_part( 'template-parts/front/products', null, array( 'section' => 'offers', 'shop' => $shop ) ); } ?>
		<?php get_template_part( 'template-parts/front/story' ); ?>
		<?php if ( voiceweb_front_enabled( 'arrivals' ) ) { get_template_part( 'template-parts/front/products', null, array( 'section' => 'arrivals', 'shop' => $shop ) ); } ?>
		<?php get_template_part( 'template-parts/front/about' ); ?>
		<?php get_template_part( 'template-parts/front/blog', null, array( 'blog_url' => $blog_url ) ); ?>
		<?php get_template_part( 'template-parts/front/faq' ); ?>
	</div>
</main>
