<?php
defined( 'ABSPATH' ) || exit;
$rows = (array) voiceweb_front_value( 'promos', array() );
if ( ! $rows ) { return; }
?>
<section class="vw-store-collections" aria-label="کالکشن‌های ویژه">
	<?php foreach ( array_slice( $rows, 0, 2 ) as $row ) : $tone = in_array( $row['tone'] ?? '', array( 'ice', 'ink', 'rose' ), true ) ? $row['tone'] : 'ice'; ?>
	<article class="vw-store-collection" data-tone="<?php echo esc_attr( $tone ); ?>">
		<div class="vw-store-collection__image"><?php voiceweb_front_image( absint( $row['image'] ?? 0 ), '', false, false, '(max-width: 767px) 100vw, 45vw' ); ?></div>
		<div class="vw-store-collection__copy"><h2><?php echo esc_html( $row['title'] ?? '' ); ?></h2><p><?php echo esc_html( $row['description'] ?? '' ); ?></p><?php voiceweb_front_link( $row['link'] ?? null, 'vw-store-button' ); ?></div>
	</article>
	<?php endforeach; ?>
</section>
