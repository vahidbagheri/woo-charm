/* Native Woo variation + callback modal checks. Temporary fixtures only; no payments/SMS/mail. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_WC_SITE || 'http://woo-theme-1405.local';
function php(source) {
  const bootstrap = `<?php define('CN_LOG_DIR',sys_get_temp_dir().'/voiceweb-inquiry-browser-tests');define('DISABLE_WP_CRON',true);$_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE') ?: 'http://woo-theme-1405.local',PHP_URL_HOST);$_SERVER['REQUEST_URI']='/';require getcwd().'/wp-load.php';require_once ABSPATH.'wp-admin/includes/user.php';add_filter('pre_wp_mail','__return_false');add_filter('pre_http_request',static fn()=>new WP_Error('test_network_disabled','External requests disabled.'),PHP_INT_MAX);`;
  const result = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '3306')], { input: bootstrap + source, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error(result.error ? result.error.message : (result.stderr || result.stdout || 'Fixture command failed.'));
  return result.stdout;
}
(async () => {
  let fixture, browser, device, phone, context;
  const guard = path.join(process.cwd(), 'wp-content', 'mu-plugins', 'voiceweb-inquiry-test-guard.php');
  assert.ok(!fs.existsSync(guard), 'Never overwrite an existing fixture guard.');
  try {
    fs.mkdirSync(path.dirname(guard), { recursive: true });
    fs.writeFileSync(guard, `<?php // Temporary fixture-only IP isolation; removed in finally.
      add_filter('voiceweb/inquiries/client-ip',static function($ip){$key=$_SERVER['HTTP_X_VWI_TEST'] ?? '';return preg_match('/^vwi_browser_[a-f0-9]{8}$/D',$key) && wp_get_current_user()->user_login===$key ? $key : $ip;});
    `);
    fixture = JSON.parse(php(`
      $prefix='vwi_browser_'.bin2hex(random_bytes(4));$ids=[];
      $uid=wp_insert_user(['user_login'=>$prefix,'user_pass'=>wp_generate_password(40),'role'=>'customer','user_email'=>'']);if(is_wp_error($uid))exit(1);(new WP_User($uid))->add_cap('manage_woocommerce');update_user_meta($uid,'show_admin_bar_front','false');
      $p=new WC_Product_Simple();$p->set_name('کیف آزمایشی تماس');$p->set_status('publish');$p->set_regular_price('1200000');$p->set_manage_stock(true);$p->set_stock_quantity(4);$p->update_meta_data('_voiceweb_purchase_mode','inquiry');$ids['simple']=$p->save();
      $p=new WC_Product_Variable();$p->set_name('کیف متغیر آزمایشی تماس');$p->set_status('publish');$p->update_meta_data('_voiceweb_purchase_mode','inquiry');$a=new WC_Product_Attribute();$a->set_name('رنگ');$a->set_options(['سبز','مشکی']);$a->set_visible(true);$a->set_variation(true);$p->set_attributes([$a]);$ids['variable']=$p->save();
      foreach(['green'=>['سبز','instock'],'black'=>['مشکی','outofstock']] as $key=>$props){$v=new WC_Product_Variation();$v->set_parent_id($p->get_id());$v->set_status('publish');$v->set_attributes([sanitize_title('رنگ')=>$props[0]]);$v->set_regular_price('1400000');$v->set_stock_status($props[1]);$ids[$key]=$v->save();}WC_Product_Variable::sync($p->get_id());
      echo wp_json_encode(['prefix'=>$prefix,'user'=>$uid,'cookie'=>wp_generate_auth_cookie($uid,time()+1200,'logged_in'),'cookie_name'=>LOGGED_IN_COOKIE,'ids'=>$ids,'urls'=>['simple'=>get_permalink($ids['simple']),'variable'=>get_permalink($ids['variable'])]]);
    `));
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, extraHTTPHeaders: { 'X-VWI-Test': fixture.prefix } });
    await context.addCookies([{ name: fixture.cookie_name, value: fixture.cookie, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' }]);
    await context.route('**/*', route => {
      const url = new URL(route.request().url());
      if (url.hostname !== new URL(site).hostname || url.pathname.includes('/voiceweb-auth/v1/request-code')) return route.abort();
      return route.continue();
    });
    const page = await context.newPage(); const errors = []; page.on('pageerror', error => errors.push(error.message));
    await page.goto(fixture.urls.simple, { waitUntil: 'networkidle' });
    assert.equal(await page.locator('.summary .single_add_to_cart_button').count(), 0, 'A simple inquiry product exposes no native purchase button.');
    const trigger = page.locator('.summary button[data-vw-inquiry-product]').first(); await trigger.click();
    const dialog = page.locator('#vw-inquiry-dialog'); await dialog.waitFor({ state: 'visible' });
    assert.ok(await page.evaluate(() => document.activeElement === document.querySelector('#vw-inquiry-dialog input[name="phone"]')));
    await page.keyboard.press('Shift+Tab'); assert.ok(await page.evaluate(() => !!document.activeElement.closest('#vw-inquiry-dialog')), 'Keyboard focus stays inside the native modal.');
    await page.keyboard.press('Escape'); assert.equal(await dialog.isVisible(), false); assert.ok(await trigger.evaluate(node => node === document.activeElement));
    for (const width of [320, 390, 768]) {
      await page.setViewportSize({ width, height: 844 }); await trigger.click();
      assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'No document overflow at ' + width);
      assert.ok(await dialog.evaluate(node => node.getBoundingClientRect().width <= innerWidth), 'Dialog fits the viewport.');
      if (process.env.VOICEWEB_WC_SCREENSHOTS && width === 390) {
        fs.mkdirSync(process.env.VOICEWEB_WC_SCREENSHOTS, { recursive: true });
        await page.screenshot({ path: path.join(process.env.VOICEWEB_WC_SCREENSHOTS, 'inquiry-modal-mobile.png') });
      }
      await page.keyboard.press('Escape');
    }
    await trigger.click();
    phone = '099' + String(Math.floor(Math.random() * 89999999) + 10000000);
    await dialog.locator('[name=phone]').fill(phone); await dialog.locator('[name=name]').fill('Fixture callback customer'); await dialog.locator('[name=consent]').check();
    await dialog.locator('[type=submit]').click();
    await dialog.locator('form.is-complete').waitFor();
    assert.match(await dialog.locator('[data-vwi-feedback]').innerText(), /درخواست شما ثبت شد/);
    await dialog.locator('[data-vwi-close]').click();
    const privateRecord = JSON.parse(php(`global $wpdb;echo wp_json_encode($wpdb->get_row($wpdb->prepare("SELECT product_id,variation_id,user_id,status FROM {$wpdb->prefix}voiceweb_inquiries WHERE user_id=%d ORDER BY created_at DESC LIMIT 1",${fixture.user}),ARRAY_A));`));
    assert.equal(Number(privateRecord.user_id), fixture.user); assert.equal(Number(privateRecord.product_id), fixture.ids.simple); assert.equal(privateRecord.status, 'new');
    await page.goto(fixture.urls.variable, { waitUntil: 'networkidle' });
    const variationForm = page.locator('form.variations_form'); await variationForm.waitFor();
    const variableTrigger = variationForm.locator('button[data-vw-inquiry-variable]'); assert.equal(await variableTrigger.isDisabled(), true);
    assert.equal(await variationForm.locator('[name="add-to-cart"],.single_add_to_cart_button').count(), 0, 'The retained variation form contains no purchase submit action.');
    const select = variationForm.locator('select[name^="attribute_"]').first();
    const availableValue = await select.evaluate(node => Array.from(node.options).find(option => option.text === 'سبز').value);
    const swatch = variationForm.locator('.variable-item').filter({ hasText: 'سبز' });
    if (await swatch.count()) await swatch.first().click(); else await select.selectOption(availableValue);
    await variableTrigger.waitFor({ state: 'visible' }); await page.waitForFunction(() => !document.querySelector('[data-vw-inquiry-variable]').disabled);
    await variableTrigger.click(); assert.match(await dialog.locator('[data-vwi-product-label]').innerText(), /سبز/); await page.keyboard.press('Escape');
    await variationForm.locator('.reset_variations').click(); await page.waitForFunction(() => document.querySelector('[data-vw-inquiry-variable]').disabled);
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    assert.deepEqual(errors, []);
    console.log('PASS: callback modal, focus/Escape, 320/390/768px, private lead, native variation selection/reset and no native purchase button.');
  } finally {
    if (context) { const cookies = await context.cookies(); device = cookies.find(cookie => cookie.name === 'vw_inquiry_device')?.value; }
    if (browser) await browser.close();
    try {
      if (fixture) {
        const keys = ['ip|' + fixture.prefix]; if (device) keys.push('device|' + device); if (phone) keys.push('phone|' + phone, 'cooldown|' + phone);
        php(`global $wpdb;$ids=[${Object.values(fixture.ids).join(',')}];$wpdb->query("DELETE FROM {$wpdb->prefix}voiceweb_inquiries WHERE product_id IN (".implode(',',$ids).")");foreach(json_decode('${JSON.stringify(keys)}',true) as $key){$wpdb->delete($wpdb->prefix.'voiceweb_inquiry_limits',['key_hash'=>hash_hmac('sha256',$key,wp_salt('auth'))]);}foreach(array_reverse($ids) as $id){$p=wc_get_product($id);if($p)$p->delete(true);}wp_delete_user(${fixture.user});`);
      }
    } finally { if (fs.existsSync(guard)) fs.unlinkSync(guard); }
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
