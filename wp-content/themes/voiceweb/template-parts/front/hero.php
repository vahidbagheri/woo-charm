<?php
defined( 'ABSPATH' ) || exit;
$slides = $args['slides'];
?>
<section class="vw-store-hero" data-vw-hero data-autoplay="<?php echo voiceweb_front_value( 'autoplay', false ) ? '1' : '0'; ?>" data-interval="<?php echo esc_attr( max( 5, min( 20, (int) voiceweb_front_value( 'interval', 8 ) ) ) ); ?>" aria-label="کالکشن‌های منتخب" aria-roledescription="carousel">
	<?php foreach ( $slides as $i => $slide ) : $tone = in_array( $slide['tone'] ?? '', array( 'ice', 'ink', 'rose' ), true ) ? $slide['tone'] : 'ice'; ?>
	<article class="vw-store-hero__slide" data-vw-slide data-tone="<?php echo esc_attr( $tone ); ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( sprintf( '%s از %s', number_format_i18n( $i + 1 ), number_format_i18n( count( $slides ) ) ) ); ?>"<?php echo $i ? ' hidden inert aria-hidden="true"' : ' aria-hidden="false"'; ?>>
		<div class="vw-store-hero__stage">
			<picture>
				<?php $mobile = absint( $slide['mobile_image'] ?? 0 ); $src = $mobile ? wp_get_attachment_image_url( $mobile, 'large' ) : ''; $srcset = $mobile ? wp_get_attachment_image_srcset( $mobile, 'large' ) : ''; if ( $src ) : ?>
				<source media="(max-width: 767px)" <?php echo $i ? 'data-vw-srcset' : 'srcset'; ?>="<?php echo esc_attr( $srcset ?: $src ); ?>" sizes="100vw">
				<?php endif; ?>
				<?php voiceweb_front_image( absint( $slide['image'] ?? 0 ), 'vw-store-hero__image', $i === 0, $i > 0, '(max-width: 767px) 100vw, 58vw' ); ?>
			</picture>
			<?php foreach ( array_slice( (array) ( $slide['floating_images'] ?? array() ), 0, 3 ) as $n => $floating ) : if ( empty( $floating['image'] ) ) { continue; } ?>
			<span class="vw-store-hero__float vw-store-hero__float--<?php echo esc_attr( $n + 1 ); ?>" aria-hidden="true"><?php voiceweb_front_image( absint( $floating['image'] ), '', false, $i > 0, '120px' ); ?></span>
			<?php endforeach; ?>
		</div>
		<div class="vw-store-hero__copy">
			<span class="vw-store-eyebrow"><?php echo esc_html( $slide['eyebrow'] ?? 'LEATHER COLLECTION' ); ?></span>
			<?php $tag = $i === 0 ? 'h1' : 'h2'; echo '<' . $tag . '>' . nl2br( esc_html( $slide['title'] ) ) . '</' . $tag . '>'; ?>
			<p><?php echo esc_html( $slide['description'] ?? '' ); ?></p>
			<div class="vw-store-hero__actions"><?php voiceweb_front_link( $slide['primary'] ?? null, 'vw-store-button' ); voiceweb_front_link( $slide['secondary'] ?? null ); ?></div>
		</div>
	</article>
	<?php endforeach; ?>
	<?php if ( count( $slides ) > 1 ) : ?>
	<div class="vw-store-hero__controls" data-vw-hero-controls hidden>
		<button type="button" data-vw-next aria-label="اسلاید بعدی"><?php echo voiceweb_front_icon( is_rtl() ? 'left' : 'right' ); ?></button>
		<div class="vw-store-hero__dots"><?php foreach ( $slides as $i => $slide ) : ?><button type="button" data-vw-slide-to="<?php echo esc_attr( $i ); ?>" aria-label="<?php echo esc_attr( 'اسلاید ' . number_format_i18n( $i + 1 ) ); ?>" aria-pressed="<?php echo $i ? 'false' : 'true'; ?>"><i></i></button><?php endforeach; ?></div>
		<span data-vw-slide-number>۰۱ / <?php echo esc_html( cn_localize_digits( str_pad( (string) count( $slides ), 2, '0', STR_PAD_LEFT ) ) ); ?></span>
		<button type="button" data-vw-previous class="vw-store-sr" aria-label="اسلاید قبلی"><?php echo voiceweb_front_icon( is_rtl() ? 'right' : 'left' ); ?></button>
		<button type="button" data-vw-pause aria-pressed="false" hidden>توقف چرخش</button>
	</div>
	<p class="vw-store-sr" data-vw-slide-status role="status" aria-live="polite"></p>
	<?php endif; ?>
</section>
