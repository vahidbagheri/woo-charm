/* Catalog form, responsive timelines and real expiry using disposable local fixtures. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const crypto = require('node:crypto');
const { spawnSync } = require('node:child_process');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_WC_SITE || 'http://woo-charm-main.local';
const prefix = 'vwpricing_browser_' + crypto.randomBytes(5).toString('hex');
const screenshots = path.join(os.tmpdir(), prefix);
const guard = path.join(process.cwd(), 'wp-content/mu-plugins/voiceweb-pricing-browser-test-guard.php');
function php(source) {
  const bootstrap = `<?php define('CN_LOG_DIR',sys_get_temp_dir().'/voiceweb-pricing-browser');define('DISABLE_WP_CRON',true);$_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE')?:'http://woo-charm-main.local',PHP_URL_HOST);$_SERVER['REQUEST_URI']='/';require getcwd().'/wp-load.php';add_filter('pre_wp_mail','__return_false');add_filter('pre_http_request',static fn()=>new WP_Error('test_no_network','No external requests.'),PHP_INT_MAX);`;
  const result = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '10046')], { input: bootstrap + source, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error(result.error ? result.error.message : result.stderr || result.stdout || 'PHP fixture failed.');
  return result.stdout;
}
(async () => {
  let fixture, browser;
  assert.ok(!fs.existsSync(guard), 'Never overwrite another test guard.');
  try {
    fixture = JSON.parse(php(`
      if(!in_array(wp_get_environment_type(),['local','development','staging'],true))exit(1);
      $prefix='${prefix}';$ids=[];$uid=wp_insert_user(['user_login'=>$prefix,'user_pass'=>wp_generate_password(40),'role'=>'shop_manager','user_email'=>'']);if(is_wp_error($uid))exit(1);
      update_user_meta($uid,'show_admin_bar_front','false');$term=wp_insert_term('مجموعهٔ آزمایشی تخفیف','product_cat',['slug'=>$prefix]);if(is_wp_error($term))exit(1);
      $sample=get_posts(['post_type'=>'product','post_status'=>'publish','numberposts'=>1,'meta_key'=>'_thumbnail_id']);$image=$sample?get_post_thumbnail_id($sample[0]->ID):0;
      foreach(['simple'=>'کیف چرمی با تخفیف','plain'=>'کیف بدون تخفیف'] as $key=>$name){$p=new WC_Product_Simple();$p->set_name($name.' '.$prefix);$p->set_status('publish');$p->set_regular_price('1000000');$p->set_category_ids([$term['term_id']]);$p->set_image_id($image);$p->set_manage_stock(true);$p->set_stock_quantity(7);$p->update_meta_data('_voiceweb_purchase_mode','purchase');$ids[$key]=$p->save();}
      $p=new WC_Product_Variable();$p->set_name('کیف چندمدلی '.$prefix);$p->set_status('publish');$p->set_category_ids([$term['term_id']]);$p->set_image_id($image);$a=new WC_Product_Attribute();$a->set_name('model');$a->set_options(['standard','large']);$a->set_variation(true);$a->set_visible(true);$p->set_attributes([$a]);$p->set_default_attributes(['model'=>'standard']);$p->update_meta_data('_voiceweb_purchase_mode','purchase');$ids['variable']=$p->save();
      foreach(['standard','large'] as $model){$v=new WC_Product_Variation();$v->set_parent_id($p->get_id());$v->set_status('publish');$v->set_attributes(['model'=>$model]);$v->set_regular_price('2000000');$v->set_manage_stock(true);$v->set_stock_quantity(5);$ids[$model]=$v->save();}WC_Product_Variable::sync($p->get_id());
      echo wp_json_encode(['uid'=>$uid,'ids'=>$ids,'term'=>$term['term_id'],'archive'=>get_term_link($term['term_id'],'product_cat'),'simple'=>get_permalink($ids['simple']),'variable'=>get_permalink($ids['variable']),'plain'=>get_permalink($ids['plain']),'from'=>voiceweb_wc_pricing_date_input(time()-96*DAY_IN_SECONDS-7*HOUR_IN_SECONDS),'to'=>voiceweb_wc_pricing_date_input(time()+96*DAY_IN_SECONDS+7*HOUR_IN_SECONDS),'logged_name'=>LOGGED_IN_COOKIE,'logged_cookie'=>wp_generate_auth_cookie($uid,time()+1800,'logged_in'),'auth_name'=>AUTH_COOKIE,'auth_cookie'=>wp_generate_auth_cookie($uid,time()+1800,'auth')]);
    `));
    fs.mkdirSync(path.dirname(guard), { recursive: true });
    fs.writeFileSync(guard, `<?php // Disposable guard for this test user only.
      function voiceweb_pricing_fixture_request(){return wp_get_current_user()->user_login==='${prefix}';}
      add_action('plugins_loaded',static function(){if(voiceweb_pricing_fixture_request()&&!defined('DISABLE_WP_CRON'))define('DISABLE_WP_CRON',true);},1);
      add_filter('action_scheduler_allow_async_request_runner',static fn($allow)=>voiceweb_pricing_fixture_request()?false:$allow);
      add_filter('pre_wp_mail',static fn($value)=>voiceweb_pricing_fixture_request()?false:$value,PHP_INT_MAX);
      add_filter('pre_http_request',static fn($value)=>voiceweb_pricing_fixture_request()?new WP_Error('test_no_external','No external requests.'):$value,PHP_INT_MAX);
    `);
    fs.mkdirSync(screenshots, { recursive: true });
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
    await context.addCookies([
      { name: fixture.logged_name, value: fixture.logged_cookie, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' },
      { name: fixture.auth_name, value: fixture.auth_cookie, domain: new URL(site).hostname, path: '/wp-admin', httpOnly: true, sameSite: 'Lax' }
    ]);
    await context.route('**/*', route => new URL(route.request().url()).hostname !== new URL(site).hostname ? route.abort() : route.continue());
    const page = await context.newPage(), errors = [];
    context.on('page', p => p.on('pageerror', error => errors.push(error.message)));
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(site + '/wp-admin/admin.php?page=voiceweb-catalog-pricing&catalog_search=' + prefix, { waitUntil: 'networkidle' });
    assert.ok(await page.getByRole('columnheader', { name: 'مبلغ با تخفیف (دلار)', exact: true }).count());
    assert.ok(await page.getByRole('columnheader', { name: 'اولویت نمایش', exact: true }).count());
    for (const [key, priority] of [['simple', '2'], ['variable', '10'], ['plain', '1']]) await page.locator(`[name="display_priority[${fixture.ids[key]}]"]`).fill(priority);
    const startInput = page.locator(`[name="sale_from[${fixture.ids.simple}]"]`);
    assert.equal(await startInput.getAttribute('type'), 'text');
    await startInput.click();
    await page.locator('jdp-container').waitFor({ state: 'visible' });
    assert.ok(await page.locator('jdp-container .jdp-time-container').isVisible(), 'The Jalali calendar also selects the sale time.');
    assert.match(await page.locator('jdp-container .jdp-month').innerText(), /فروردین|اردیبهشت|خرداد|تیر|مرداد|شهریور|مهر|آبان|آذر|دی|بهمن|اسفند/);
    await page.locator('jdp-container .jdp-day:not(.not-in-month):not(.disabled-day)').filter({ hasText: /^15$/ }).click();
    assert.match(await startInput.inputValue(), /^1[34]\d{2}\/\d{2}\/15 /, 'Clicking a calendar day writes a Jalali date.');
    await page.screenshot({ path: path.join(screenshots, 'jalali-picker.png'), fullPage: true });
    await page.locator('jdp-container .jdp-btn-close').click();
    for (const [key, base, sale] of [['simple','50','40'], ['standard','50','40'], ['large','100','70']]) {
      const id = fixture.ids[key];
      await page.locator(`[name="usd_amount[${id}]"]`).fill(base);
      await page.locator(`[name="usd_sale_amount[${id}]"]`).fill(sale);
      await page.locator(`[name="sale_from[${id}]"]`).fill(fixture.from);
      await page.locator(`[name="sale_to[${id}]"]`).fill(fixture.to);
    }
    await page.getByRole('button', { name: 'ذخیرهٔ محصولات این صفحه', exact: true }).click();
    await page.waitForLoadState('networkidle');
    assert.ok(await page.locator('.notice-success').count(), 'The real admin POST saves the complete schedule.');
    assert.equal(await page.locator(`[name="sale_to[${fixture.ids.simple}]"]`).inputValue(), fixture.to, 'Jalali input is preserved after the real admin POST.');
    assert.equal(await page.locator(`[name="display_priority[${fixture.ids.variable}]"]`).inputValue(), '10');
    await page.screenshot({ path: path.join(screenshots, 'admin.png'), fullPage: true });
    const native = JSON.parse(php(`$p=wc_get_product(${fixture.ids.simple});echo wp_json_encode(['base'=>$p->get_regular_price(),'sale'=>$p->get_sale_price(),'rate'=>get_option(VOICEWEB_WC_USD_RATE),'discount'=>voiceweb_wc_product_discount($p)]);`));
    assert.equal(Number(native.base), 50 * Number(native.rate) * 10);
    assert.equal(Number(native.sale), 40 * Number(native.rate) * 10);
    assert.equal(native.discount, 20);
    // Admin translation-plugin scripts also run here; assert storefront errors separately.
    errors.length = 0;
    const orderedIds = () => page.locator('[data-vw-catalog] [data-vw-product-id]').evaluateAll(nodes => nodes.map(node => Number(node.dataset.vwProductId)));
    const priorityOrder = [fixture.ids.variable, fixture.ids.simple, fixture.ids.plain];
    await page.goto(site + '/shop/?vw_search=' + prefix, { waitUntil: 'networkidle' });
    assert.deepEqual(await orderedIds(), priorityOrder, 'The shop orders larger priorities first.');
    await page.goto(site + '/shop/?vw_search=' + prefix + '&orderby=price', { waitUntil: 'networkidle' });
    assert.deepEqual(await orderedIds(), [fixture.ids.plain, fixture.ids.simple, fixture.ids.variable], 'Explicit price sorting remains numeric and independent of priority.');
    // Device clock is deliberately wrong; all countdowns must use the HTTP server clock.
    await context.addInitScript(() => { const actual = Date.now.bind(Date); Date.now = () => actual() + 8 * 86400000; });
    await page.goto(fixture.simple, { waitUntil: 'networkidle' });
    await page.locator('[data-vw-product-deadline] [data-vw-sale-unit="days"]').waitFor();
    assert.equal(await page.locator('[data-vw-product-deadline] [data-vw-sale-value="days"]').innerText(), '۹۶');
    assert.ok(await page.locator('[data-vw-product-offer]').isVisible());
    assert.match(await page.locator('[data-vw-product-offer] [data-vw-sale-percent]').innerText(), /۲۰|20/);
    const progress = Number(await page.locator('[data-vw-product-offer] [data-vw-sale-track]').getAttribute('aria-valuenow'));
    assert.ok(progress > 40 && progress < 60, 'Progress uses the actual start/end window and the server clock.');
    await page.locator('.summary').screenshot({ path: path.join(screenshots, 'product.png') });
    await page.goto(fixture.variable, { waitUntil: 'networkidle' });
    await page.waitForFunction(() => document.querySelector('[data-vw-product-offer]')?.dataset.vwSaleDiscount === '20');
    const largeSwatch = page.locator('.variable-item[data-value="large"]');
    if (await largeSwatch.count()) await largeSwatch.click();
    else await page.locator('select[name="attribute_model"]').selectOption('large');
    await page.waitForFunction(() => document.querySelector('[data-vw-product-offer]')?.dataset.vwSaleDiscount === '30');
    await page.locator('.reset_variations').click();
    await page.waitForFunction(() => document.querySelector('[data-vw-product-offer]').hidden);
    await page.goto(fixture.archive, { waitUntil: 'networkidle' });
    assert.deepEqual(await orderedIds(), priorityOrder, 'Category archives apply the same priority order as the shop.');
    const simpleCard = page.locator(`[data-vw-product-id="${fixture.ids.simple}"]`);
    const variableCard = page.locator(`[data-vw-product-id="${fixture.ids.variable}"]`);
    assert.ok(await simpleCard.locator('[data-vw-card-offer]').isVisible());
    assert.equal(await variableCard.locator('[data-vw-card-offer]').getAttribute('data-vw-sale-discount'), '30');
    assert.equal(await page.locator(`[data-vw-product-id="${fixture.ids.plain}"] [data-vw-card-offer]`).count(), 0);
    const clockOrder = () => simpleCard.locator('[data-vw-card-deadline]').evaluate(clock => {
      const days = clock.querySelector('[data-vw-sale-unit="days"]'), hours = clock.querySelector('[data-vw-sale-unit="hours"]');
      return days.getBoundingClientRect().right < hours.getBoundingClientRect().left && days.querySelector('b').getBoundingClientRect().right <= days.querySelector('span').getBoundingClientRect().left;
    });
    assert.ok(await clockOrder(), 'The day block is physically left of hours, and the day number is left of its Persian label.');
    assert.match(await simpleCard.locator('[data-vw-card-deadline]').getAttribute('aria-label'), /۹۶ روز و .*ساعت.*مانده/);
    await page.emulateMedia({ reducedMotion: 'reduce' });
    assert.equal(await simpleCard.locator('[data-vw-card-offer]').evaluate(node => getComputedStyle(node).animationName), 'none');
    assert.equal(await simpleCard.locator('[data-vw-sale-track] i').evaluate(node => getComputedStyle(node, '::after').display), 'none');
    await page.emulateMedia({ reducedMotion: 'no-preference' });
    await page.screenshot({ path: path.join(screenshots, 'cards-desktop.png'), fullPage: true });
    for (const width of [320, 390, 768]) {
      await page.setViewportSize({ width, height: 1000 });
      assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'No document overflow at ' + width);
      assert.ok(await simpleCard.evaluate(card => [...card.querySelectorAll('.vw-sale-timeline, .vw-sale-timeline time')].every(node => node.getBoundingClientRect().left >= card.getBoundingClientRect().left && node.getBoundingClientRect().right <= card.getBoundingClientRect().right + 1)), 'Countdown stays inside the card at ' + width);
      assert.ok(await clockOrder(), 'Day-first visual order stays correct at ' + width);
    }
    await page.setViewportSize({ width: 390, height: 1000 });
    await page.screenshot({ path: path.join(screenshots, 'cards-mobile.png'), fullPage: true });
    const detail = await context.newPage();
    const ends = Number(php(`$end=time()+12;foreach([${fixture.ids.simple},${fixture.ids.standard},${fixture.ids.large}] as $id){$p=wc_get_product($id);$offer=voiceweb_wc_pricing_offer($p);$offer['to']=$end;voiceweb_wc_pricing_set_amount($p,$p->get_meta(VOICEWEB_WC_USD_AMOUNT),(string)get_option(VOICEWEB_WC_USD_RATE),$offer);$p->save();}WC_Product_Variable::sync(${fixture.ids.variable});echo $end;`));
    await Promise.all([page.reload({ waitUntil: 'domcontentloaded' }), detail.goto(fixture.simple, { waitUntil: 'domcontentloaded' })]);
    assert.equal(Number(await detail.locator('[data-vw-product-deadline]').getAttribute('data-vw-product-deadline')), ends);
    await detail.waitForFunction(() => !document.querySelector('.summary > .price ins'), null, { timeout: 25000 });
    await page.waitForFunction(ids => ids.every(id => !document.querySelector(`[data-vw-product-id="${id}"] .price ins`) && !document.querySelector(`[data-vw-product-id="${id}"] .onsale`)), [fixture.ids.simple, fixture.ids.variable], { timeout: 25000 });
    assert.equal(await page.locator('[data-vw-card-offer]:visible').count(), 0, 'Expired cards do not restart their countdown.');
    assert.equal(await detail.locator('[data-vw-product-offer]:visible').count(), 0);
    assert.deepEqual(errors, []);
    console.log('PASS: Jalali calendar selection, admin save, numeric priorities in shop/categories, explicit price sorting, dollar conversion, variation selection, server clock, responsive cards and real expiry. Screenshots: ' + screenshots);
  } finally {
    if (browser) await browser.close();
    if (fs.existsSync(guard)) fs.unlinkSync(guard);
    if (fixture) php(`foreach(array_reverse(${JSON.stringify(Object.values(fixture.ids))}) as $id){as_unschedule_all_actions('wc_product_start_scheduled_sale',['product_id'=>$id],'woocommerce-sales');as_unschedule_all_actions('wc_product_end_scheduled_sale',['product_id'=>$id],'woocommerce-sales');$p=wc_get_product($id);if($p)$p->delete(true);}wp_delete_term(${fixture.term},'product_cat');require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user(${fixture.uid});`);
  }
})().catch(error => { console.error(error); process.exit(1); });
