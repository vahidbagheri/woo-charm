<?php
defined( 'ABSPATH' ) || exit;
$title = (string) voiceweb_front_value( 'about_title' ); if ( ! $title ) { return; }
$link = is_page_template( 'page-templates/about.php' ) && function_exists( 'wc_get_page_permalink' ) ? array( 'url' => wc_get_page_permalink( 'shop' ), 'title' => 'کشف کالکشن' ) : voiceweb_front_value( 'about_link' );
?>
<section class="vw-store-about" aria-labelledby="home-about-title">
	<div class="vw-store-about__image"><?php voiceweb_front_image( absint( voiceweb_front_value( 'about_image' ) ) ); ?></div>
	<div class="vw-store-about__copy"><span class="vw-store-eyebrow"><?php echo esc_html( 'داستان ' . get_bloginfo( 'name' ) ); ?></span><h2 id="home-about-title"><?php echo esc_html( $title ); ?></h2><p><?php echo esc_html( voiceweb_front_value( 'about_description' ) ); ?></p>
		<div class="vw-store-values"><?php foreach ( array_slice( (array) voiceweb_front_value( 'benefits', array() ), 0, 4 ) as $row ) : ?><div><?php echo voiceweb_front_icon( $row['icon'] ?? 'check' ); ?><strong><?php echo esc_html( $row['title'] ?? '' ); ?></strong><p><?php echo esc_html( $row['description'] ?? '' ); ?></p></div><?php endforeach; ?></div>
		<?php voiceweb_front_link( $link, 'vw-store-button' ); ?>
	</div>
</section>
