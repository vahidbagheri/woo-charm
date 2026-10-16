/* Native shopping + multiple shared wishlist controls and real sale timers; isolated fixtures, no payment/SMS. */
const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
const { spawnSync } = require('node:child_process');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_WC_SITE || 'http://woo-theme-1405.local';
function php(source) {
  const bootstrap = `<?php define('CN_LOG_DIR',sys_get_temp_dir().'/voiceweb-product-design-tests');define('DISABLE_WP_CRON',true);$_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE') ?: 'http://woo-theme-1405.local',PHP_URL_HOST);$_SERVER['REQUEST_URI']='/';require getcwd().'/wp-load.php';add_filter('pre_wp_mail','__return_false');add_filter('pre_http_request',static fn()=>new WP_Error('test_external_disabled','No external requests.'),PHP_INT_MAX);`;
  const result = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '3306')], { input: bootstrap + source, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error(result.error ? result.error.message : (result.stderr || result.stdout || 'PHP fixture process failed.'));
  return result.stdout;
}
(async () => {
  let fixture, browser;
  try {
    fixture = JSON.parse(php(`
      $prefix='vwdesign_test_'.bin2hex(random_bytes(5)); $ids=[];
      $uid=wp_insert_user(['user_login'=>$prefix,'user_pass'=>wp_generate_password(40),'display_name'=>'مشتری آزمایشی طراحی','role'=>'customer','user_email'=>'']); if(is_wp_error($uid))exit(1);
      (new WP_User($uid))->add_cap('manage_woocommerce');update_user_meta($uid,'show_admin_bar_front','false');
      $term=wp_insert_term('دستهٔ آزمایشی طراحی','product_cat',['slug'=>$prefix]); if(is_wp_error($term))exit(1);
      $deadline=time()+86400;
      foreach(['one','two'] as $key) {
        $p=new WC_Product_Simple();$p->set_name('کارت چرمی آزمایشی '.$key);$p->set_status('publish');$p->set_regular_price('1000000');$p->set_sale_price('800000');$p->set_date_on_sale_to($deadline);$p->set_category_ids([$term['term_id']]);$p->update_meta_data('_voiceweb_test_fixture',$prefix);$p->update_meta_data('_voiceweb_purchase_mode','purchase');$p->save();$ids[$key]=$p->get_id();
      }
      $v=new WC_Product_Variable();$v->set_name('کیف متغیر آزمایشی طراحی');$v->set_status('publish');$v->set_category_ids([$term['term_id']]);$a=new WC_Product_Attribute();$a->set_name('color');$a->set_options(['Brown','Black']);$a->set_variation(true);$a->set_visible(true);$v->set_attributes([$a]);$v->update_meta_data('_voiceweb_test_fixture',$prefix);$v->update_meta_data('_voiceweb_purchase_mode','purchase');$v->save();$ids['variable']=$v->get_id();
      foreach(['Brown','Black'] as $color) {$child=new WC_Product_Variation();$child->set_parent_id($v->get_id());$child->set_status('publish');$child->set_attributes(['color'=>$color]);$child->set_regular_price('2000000');if($color==='Brown'){$child->set_sale_price('1400000');$child->set_date_on_sale_to($deadline);}$child->save();$ids[$color]=$child->get_id();} WC_Product_Variable::sync($v->get_id());
      $v=wc_get_product($v->get_id());$p=wc_get_product($ids['one']);$previous_post=$GLOBALS['post']??null;$previous_product=$GLOBALS['product']??null;ob_start();get_template_part('template-parts/woocommerce/product-card',null,['product'=>$p]);$card=ob_get_clean();
      if(!str_contains($card,$p->get_name())||($GLOBALS['post']??null)!==$previous_post||($GLOBALS['product']??null)!==$previous_product||voiceweb_wc_product_discount($v)!==30){fwrite(STDERR,'Shared card/global restore/paired variable discount failed.');exit(1);}
      echo wp_json_encode(['uid'=>$uid,'ids'=>$ids,'term'=>$term['term_id'],'prefix'=>$prefix,'cookie_name'=>LOGGED_IN_COOKIE,'cookie'=>wp_generate_auth_cookie($uid,time()+1800,'logged_in'),'archive'=>get_term_link($term['term_id'],'product_cat'),'simple'=>get_permalink($ids['one']),'variable'=>get_permalink($ids['variable']),'deadline'=>$deadline]);
    `));
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.addCookies([{ name: fixture.cookie_name, value: fixture.cookie, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' }]);
    const page = await context.newPage(); const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/voiceweb-auth/v1/request-code', route => route.abort());
    await page.goto(fixture.archive, { waitUntil: 'networkidle' });
    assert.equal(await page.locator('.vw-product-card').count(), 3);
    const first = page.locator(`[data-vw-wishlist-product="${fixture.ids.one}"]`);
    const second = page.locator(`[data-vw-wishlist-product="${fixture.ids.two}"]`);
    await page.waitForFunction(() => [...document.querySelectorAll('.vw-product-card__favorite[data-vw-wishlist-product]')].every(button => !button.disabled));
    await first.click(); await page.waitForFunction(id => document.querySelector(`[data-vw-wishlist-product="${id}"]`).getAttribute('aria-pressed') === 'true', fixture.ids.one);
    await second.click(); await page.waitForFunction(id => document.querySelector(`[data-vw-wishlist-product="${id}"]`).getAttribute('aria-pressed') === 'true', fixture.ids.two);
    await first.click(); await page.waitForFunction(id => document.querySelector(`[data-vw-wishlist-product="${id}"]`).getAttribute('aria-pressed') === 'false', fixture.ids.one);
    assert.equal(await second.getAttribute('aria-pressed'), 'true');
    await page.locator(`.vw-product-card .add_to_cart_button[data-product_id="${fixture.ids.one}"]`).click();
    await page.waitForFunction(() => [...document.querySelectorAll('[data-vw-cart-count]')].some(node => Number(node.dataset.vwCartCount) === 1));
    await page.goto(site + '/cart/', { waitUntil: 'networkidle' });
    // Native Blocks hydrate after document load; await their item rather than the placeholder shell.
    await page.locator('.wc-block-cart .wc-block-components-product-name').filter({ hasText: 'one' }).waitFor();
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await page.goto(site + '/checkout/', { waitUntil: 'networkidle' });
    assert.ok(await page.locator('.wc-block-checkout').count());
    await page.goto(fixture.simple, { waitUntil: 'networkidle' });
    assert.equal(Number(await page.locator('[data-vw-product-deadline]').getAttribute('data-vw-product-deadline')), fixture.deadline);
    assert.ok(await page.locator('[data-vw-product-deadline]').textContent());
    await page.evaluate(() => { document.querySelector('[data-vw-product-deadline]').dataset.vwProductDeadline = String(Math.floor(Date.now()/1000)-1); });
    await page.waitForFunction(() => document.querySelector('[data-vw-product-offer]').hidden);
    await page.goto(fixture.variable, { waitUntil: 'networkidle' });
    async function choose(value) {
      const swatch = page.locator(`.variable-item[data-value="${value}"]`);
      if (await swatch.count()) await swatch.click();
      else await page.locator('select[name="attribute_color"]').selectOption(value);
    }
    await choose('Brown');
    await page.waitForFunction(() => !document.querySelector('[data-vw-product-offer]').hidden);
    assert.equal(Number(await page.locator('[data-vw-product-deadline]').getAttribute('data-vw-product-deadline')), fixture.deadline);
    await choose('Black'); await page.waitForFunction(() => document.querySelector('[data-vw-product-offer]').hidden);
    for (const width of [320, 390, 768, 1440]) {
      await page.setViewportSize({ width, height: 900 });
      assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Product overflow at ' + width);
      if (process.env.VOICEWEB_WC_SCREENSHOTS) {
        fs.mkdirSync(process.env.VOICEWEB_WC_SCREENSHOTS, { recursive: true });
        await page.screenshot({ path: path.join(process.env.VOICEWEB_WC_SCREENSHOTS, 'product-design-' + width + '.png'), fullPage: true });
      }
    }
    assert.deepEqual(errors, []);
    console.log(JSON.stringify({ sharedCardGlobals: true, matchedVariationDiscount: true, multipleNativeWishlistButtons: true, nativeAjaxCart: true, nativeBlocksCartCheckout: true, realExpiryTimer: true, perVariationTimer: true, responsive: [320,390,768,1440], javascriptErrors: 0, realSMS: 0, payments: 0, ordersSubmitted: 0 }));
  } finally {
    if (browser) await browser.close();
    if (fixture) php(`require_once ABSPATH.'wp-admin/includes/user.php';global $wpdb;$uid=${Number(fixture.uid)};$u=get_user_by('id',$uid);if($u&&str_starts_with($u->user_login,'vwdesign_test_')){$wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>(string)$uid]);wp_delete_user($uid);}foreach([${Object.values(fixture.ids).map(Number).join(',')}] as $id){$parent=wp_get_post_parent_id($id);if(str_starts_with((string)get_post_meta($parent?:$id,'_voiceweb_test_fixture',true),'vwdesign_test_'))wp_delete_post($id,true);} $t=get_term(${Number(fixture.term)},'product_cat');if($t&&!is_wp_error($t)&&str_starts_with($t->slug,'vwdesign_test_'))wp_delete_term($t->term_id,'product_cat');`);
  }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
