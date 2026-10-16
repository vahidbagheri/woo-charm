<?php
defined( 'ABSPATH' ) || exit;
$articles = voiceweb_front_enabled( 'blog' ) ? voiceweb_front_posts() : array(); if ( ! $articles ) { return; }
$archive_link = voiceweb_front_value( 'blog_link', array( 'url' => $args['blog_url'], 'title' => 'همهٔ نوشته‌ها' ) );
?>
<section class="vw-store-section" aria-labelledby="home-blog-title">
	<div class="vw-store-heading"><div><h2 id="home-blog-title"><?php echo esc_html( voiceweb_front_value( 'blog_title', 'مجلهٔ ویس وب' ) ); ?></h2><p><?php echo esc_html( voiceweb_front_value( 'blog_description' ) ); ?></p></div><?php voiceweb_front_link( $archive_link ); ?></div>
	<div class="vw-store-posts"><?php foreach ( $articles as $article ) : ?><article class="vw-store-post"><a class="vw-store-post__image" href="<?php echo esc_url( get_permalink( $article ) ); ?>" tabindex="-1" aria-hidden="true"><?php voiceweb_front_image( get_post_thumbnail_id( $article ), '', false, false, '(max-width: 767px) 100vw, 33vw' ); ?></a><div><time datetime="<?php echo esc_attr( get_the_date( 'c', $article ) ); ?>"><?php echo esc_html( get_the_date( '', $article ) ); ?></time><h3><a href="<?php echo esc_url( get_permalink( $article ) ); ?>"><?php echo esc_html( get_the_title( $article ) ); ?></a></h3><p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $article ) ), 18 ) ); ?></p><a class="vw-store-link" href="<?php echo esc_url( get_permalink( $article ) ); ?>">خواندن نوشته<?php echo voiceweb_front_icon( 'arrow' ); ?></a></div></article><?php endforeach; ?></div>
</section>
