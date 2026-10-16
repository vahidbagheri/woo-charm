<?php
defined( 'ABSPATH' ) || exit;
$story = (string) voiceweb_front_value( 'story_title' );
$collection = (string) voiceweb_front_value( 'collection_title' );
if ( ! $story && ! $collection ) { return; }
?>
<section class="vw-store-editorial" aria-label="<?php echo esc_attr( 'داستان و کالکشن ' . get_bloginfo( 'name' ) ); ?>">
	<?php if ( $story ) : ?><article class="vw-store-story"><div class="vw-store-story__image"><?php voiceweb_front_image( absint( voiceweb_front_value( 'story_image' ) ) ); ?></div><div class="vw-store-story__copy"><span class="vw-store-eyebrow">OUR CRAFTSMANSHIP</span><h2><?php echo esc_html( $story ); ?></h2><p><?php echo esc_html( voiceweb_front_value( 'story_description' ) ); ?></p><?php voiceweb_front_link( voiceweb_front_value( 'story_link' ) ); ?></div></article><?php endif; ?>
	<?php if ( $collection ) : ?><article class="vw-store-new-collection"><div class="vw-store-new-collection__image"><?php voiceweb_front_image( absint( voiceweb_front_value( 'collection_image' ) ) ); ?></div><div><span class="vw-store-eyebrow">NEW COLLECTION</span><h2><?php echo esc_html( $collection ); ?></h2><p><?php echo esc_html( voiceweb_front_value( 'collection_description' ) ); ?></p><?php voiceweb_front_link( voiceweb_front_value( 'collection_link' ), 'vw-store-button vw-store-button--light' ); ?></div></article><?php endif; ?>
</section>
