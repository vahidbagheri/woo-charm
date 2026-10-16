<?php
/**
 * Template Name: فهرست مناسبت‌ها و راهنمای هدیه
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main class="vw-home vw-gift vw-gift-hub"><div class="vw-store-container">
	<?php while ( have_posts() ) : the_post(); ?>
	<?php if ( post_password_required() ) { echo get_the_password_form(); continue; } ?>
	<nav class="vw-gift-breadcrumb" aria-label="مسیر صفحه"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true">/</span><span aria-current="page"><?php the_title(); ?></span></nav>
	<header class="vw-gift-hub__heading"><span class="vw-store-eyebrow">بهانه‌های کوچک، انتخاب‌های به‌یادماندنی</span><h1><?php the_title(); ?></h1><div class="vw-gift-prose"><?php the_content(); ?></div></header>
	<section class="vw-store-section" aria-labelledby="gift-occasions-title"><div class="vw-store-heading"><h2 id="gift-occasions-title">برای چه مناسبتی هدیه می‌خرید؟</h2></div><?php get_template_part( 'template-parts/gifts/cards', null, array( 'pages' => voiceweb_gift_pages() ) ); ?></section>
	<?php endwhile; ?>
</div></main>
<?php get_footer(); ?>
