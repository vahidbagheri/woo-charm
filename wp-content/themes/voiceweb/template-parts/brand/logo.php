<?php
/** Shared Avan Charm signature; uploaded logos keep their original artwork. */
defined( 'ABSPATH' ) || exit;
$brand_context = ( $args['context'] ?? 'header' ) === 'footer' ? 'footer' : 'header';
$brand_logo_id = absint( $args['logo_id'] ?? 0 );
$brand_name = trim( (string) get_bloginfo( 'name' ) ) ?: 'آوان چرم';
$brand_image_class = $brand_context === 'footer' ? 'cn-footer__logo-image' : 'cn-header-logo__image';
$brand_custom_image = $brand_logo_id ? wp_get_attachment_image( $brand_logo_id, 'full', false, array(
	'class' => $brand_image_class . ' avan-brand__custom-image',
	'alt' => '',
	'loading' => $brand_context === 'footer' ? 'lazy' : 'eager',
	'decoding' => 'async',
) ) : '';
$brand_class = ( $brand_context === 'footer' ? 'cn-footer__logo' : 'cn-header-logo' ) . ' avan-brand avan-brand--' . $brand_context . ( $brand_custom_image ? ' avan-brand--custom' : ' avan-brand--default' );
$brand_label = ( $brand_custom_image ? $brand_name : 'آوان چرم' ) . '، صفحهٔ اصلی';
?>
<a class="<?php echo esc_attr( $brand_class ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $brand_label ); ?>">
	<?php if ( $brand_custom_image ) : ?>
		<?php echo $brand_custom_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress attachment markup. ?>
	<?php else : ?>
		<img class="avan-brand__mark" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/avan-charm-mark.svg' ) ); ?>" width="46" height="46" alt="" aria-hidden="true" decoding="async" loading="<?php echo $brand_context === 'footer' ? 'lazy' : 'eager'; ?>">
		<span class="avan-brand__wordmark" aria-hidden="true">
			<span class="avan-brand__name" lang="fa" dir="rtl">آوان <span>چرم</span></span>
			<span class="avan-brand__latin" lang="en" dir="ltr">AVAN CHARM</span>
		</span>
	<?php endif; ?>
</a>
