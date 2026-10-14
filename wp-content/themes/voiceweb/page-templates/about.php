<?php
/**
 * Template Name: About VoiceWeb
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main class="vw-home vw-about-page"><div class="vw-store-container">
	<header class="vw-about-page__heading"><span class="vw-store-eyebrow">VOICEWEB / OUR STORY</span><h1><?php echo esc_html( get_the_title() ); ?></h1></header>
	<?php get_template_part( 'template-parts/front/about' ); ?>
	<div class="vw-about-page__content"><?php while ( have_posts() ) { the_post(); the_content(); } ?></div>
	<?php get_template_part( 'template-parts/front/story' ); ?>
</div></main>
<?php get_footer(); ?>
