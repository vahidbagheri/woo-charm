<?php
/** Shared, crawlable links to published gift guides. */
defined( 'ABSPATH' ) || exit;
$pages = $args['pages'] ?? array();
if ( ! $pages ) { return; }
?>
<div class="vw-gift-cards">
	<?php foreach ( $pages as $gift_page ) :
		if ( ! $gift_page instanceof WP_Post || $gift_page->post_status !== 'publish' || $gift_page->post_password ) { continue; }
		$image_id = absint( voiceweb_gift_value( 'hero_image', 0, $gift_page->ID ) ) ?: (int) get_post_thumbnail_id( $gift_page );
	?>
	<a class="vw-gift-card" href="<?php echo esc_url( get_permalink( $gift_page ) ); ?>">
		<?php if ( $image_id ) : ?><div class="vw-gift-card__image"><?php echo wp_get_attachment_image( $image_id, 'medium_large', false, array( 'loading' => 'lazy', 'sizes' => '(max-width: 380px) 100vw, (max-width: 1100px) 50vw, 25vw', 'alt' => '' ) ); ?></div><?php endif; ?>
		<div class="vw-gift-card__body"><h3><?php echo esc_html( get_the_title( $gift_page ) ); ?></h3><p><?php echo esc_html( wp_trim_words( voiceweb_gift_value( 'intro', $gift_page->post_excerpt, $gift_page->ID ), 22 ) ); ?></p><span class="vw-store-link">کشف هدیه‌ها<?php echo voiceweb_front_icon( 'arrow' ); ?></span></div>
	</a>
	<?php endforeach; ?>
</div>
