<?php
/** Store footer; existing menu options and footer keyboard/accordion behavior are preserved. */
defined( 'ABSPATH' ) || exit;
$site_name = (string) get_bloginfo( 'name' );
$description = (string) cn_get_site_setting( 'cn_footer_description', voiceweb_front_value( 'about_description', 'همراه‌هایی برای روزهای ماندگار؛ با توجه به جزئیات، کاربرد و سلیقهٔ شما.' ) );
$phone = trim( (string) cn_get_site_setting( 'cn_footer_phone', '' ) );
$phone_href = cn_site_phone_href( (string) cn_get_site_setting( 'cn_footer_phone_link', $phone ) );
$email = sanitize_email( (string) cn_get_site_setting( 'cn_footer_email', '' ) );
$columns = cn_get_footer_menu_columns();
$privacy = get_privacy_policy_url();
$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$about = voiceweb_front_value( 'about_link' );
$logo = absint( cn_get_site_setting( 'cn_footer_logo', 0 ) );
$reading = is_singular( 'post' );
?>
<footer class="cn-site-footer vw-store-footer" data-cn-footer>
  <div class="cn-footer__container">
    <div class="cn-footer__main" style="--cn-footer-menu-columns:<?php echo esc_attr( max(1,count($columns)) ); ?>">
      <div class="cn-footer__brand">
        <a class="cn-footer__logo" href="<?php echo esc_url( home_url('/') ); ?>" aria-label="<?php echo esc_attr($site_name); ?>">
          <?php if($logo){ echo wp_get_attachment_image($logo,'medium',false,array('class'=>'cn-footer__logo-image','alt'=>$site_name,'loading'=>'lazy')); } else { ?>
          <img class="cn-footer__logo-image" src="<?php echo esc_url(get_template_directory_uri().'/assets/images/voiceweb-store-logo.svg'); ?>" width="172" height="46" alt="<?php echo esc_attr($site_name); ?>" loading="lazy">
          <?php } ?>
        </a>
        <p class="cn-footer__description"><?php echo esc_html($description); ?></p>
        <?php voiceweb_front_link($about,'vw-store-footer__story-link'); ?>
      </div>
      <?php foreach($columns as $i=>$column): $panel='cn-footer-menu-'.($i+1); ?>
      <section class="cn-footer__column" data-cn-footer-section data-cn-menu-location="<?php echo esc_attr($column['location']); ?>">
        <h2 class="cn-footer__heading"><button type="button" data-cn-footer-toggle aria-expanded="true" aria-controls="<?php echo esc_attr($panel); ?>"><span><?php echo esc_html($column['title']); ?></span><span class="cn-footer__toggle-icon"><?php echo voiceweb_front_icon('plus'); ?></span></button></h2>
        <div class="cn-footer__panel" id="<?php echo esc_attr($panel); ?>" data-cn-footer-panel>
          <?php wp_nav_menu(array('menu'=>(int)$column['menu_id'],'container'=>false,'menu_class'=>'cn-footer__menu','depth'=>1,'fallback_cb'=>false,'cn_footer_menu'=>true)); ?>
        </div>
      </section>
      <?php endforeach; ?>
      <section class="vw-store-footer__help"><h2>همراه شما هستیم</h2><p>برای انتخاب محصول، ثبت درخواست تماس یا پیگیری سفارش، از مسیرهای زیر استفاده کنید.</p><a href="<?php echo esc_url($account); ?>">حساب و سفارش‌های من</a><a href="<?php echo esc_url($shop); ?>">کشف محصولات</a>
        <div class="cn-footer__contact">
          <?php if($phone && $phone_href): ?><a href="<?php echo esc_url('tel:'.$phone_href); ?>"><bdi><?php echo esc_html(cn_localize_digits($phone)); ?></bdi></a><?php endif; ?>
          <?php if($email): ?><a href="<?php echo esc_url('mailto:'.$email); ?>"><bdi><?php echo esc_html($email); ?></bdi></a><?php endif; ?>
        </div>
      </section>
    </div>
    <div class="cn-footer__bottom"><p>© <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html($site_name); ?>. <?php echo esc_html(cn_get_site_setting('cn_footer_copyright','تمام حقوق محفوظ است.')); ?></p><?php if($privacy): ?><a href="<?php echo esc_url($privacy); ?>">حریم خصوصی</a><?php endif; ?><span>طراحی برای جزئیات زندگی</span></div>
  </div>
  <button class="cn-footer__back-to-top" type="button" data-cn-back-to-top aria-label="بازگشت به بالا"><?php echo voiceweb_front_icon('right'); ?></button>
  <?php if($reading): ?><span id="cn-reading-progress" class="cn-footer__reading-progress" data-cn-reading-progress aria-label="پیشرفت مطالعه" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></span><?php endif; ?>
</footer>
<?php wp_footer(); ?>
</body>
</html>
