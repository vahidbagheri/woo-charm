/* Real seeded-product swatches: product/stock data are read-only. One temporary customer bypasses local Coming soon. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_WC_SITE || 'http://woo-theme-1405.local';
const output = process.env.VOICEWEB_WC_SCREENSHOTS;

function php(source) {
  const bootstrap = `<?php define('DISABLE_WP_CRON',true);define('WP_HTTP_BLOCK_EXTERNAL',true);define('CN_LOG_DIR',sys_get_temp_dir().'/voiceweb-leather-content-tests');$_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE')?:'http://woo-theme-1405.local',PHP_URL_HOST);$_SERVER['REQUEST_URI']='/';require getcwd().'/wp-load.php';add_filter('pre_wp_mail','__return_false',PHP_INT_MAX);add_filter('pre_http_request',static fn()=>new WP_Error('test_no_network','External requests disabled.'),PHP_INT_MAX);`;
  const result = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '3306')], { input: bootstrap + source, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error(result.error?.message || result.stderr || 'PHP bootstrap failed.');
  return result.stdout;
}

function snapshotPHP(ids) {
  return `$data=[];foreach([${ids.map(Number).join(',')}] as $id){$meta=get_post_meta($id);ksort($meta);$post=get_post($id);$data[$id]=['post'=>$post,'meta'=>$meta];}echo hash('sha256',wp_json_encode($data));`;
}

(async () => {
  let fixture, browser;
  let unchanged = false;
  const checks = [];
  try {
    fixture = JSON.parse(php(`
      $state=get_option('voiceweb_leather_seed_v1',[]);$products=[];$ids=[];
      if(get_option('voiceweb_wc_purchase_mode','purchase')!=='purchase'){fwrite(STDERR,'Normal global purchasing must remain enabled.');exit(1);}
      foreach(['aria','rona'] as $key){
        $id=(int)($state['products'][$key]??0);$p=wc_get_product($id);
        if(!$p||!$p->is_type('variable')||$p->get_meta('_voiceweb_leather_seed')!=='product:'.$key||voiceweb_wc_product_is_inquiry($p)){fwrite(STDERR,'The owned variable product must use normal purchasing.');exit(1);}
        $rows=[];$ids[]=$id;
        foreach($p->get_children() as $child){$v=wc_get_product($child);$native=$p->get_available_variation($v);$ids[]=$child;$rows[]=['id'=>$child,'parent'=>$id,'attributes'=>$v->get_attributes(),'price'=>(float)wc_get_price_to_display($v),'native'=>$native];}
        if(count($rows)!==4){fwrite(STDERR,'Exactly four real variations are required.');exit(1);}
        $products[]=['id'=>$id,'key'=>$key,'name'=>$p->get_name(),'url'=>get_permalink($id),'rows'=>$rows];
      }
      $prefix='vwleather_test_'.bin2hex(random_bytes(5));
      $uid=wp_insert_user(['user_login'=>$prefix,'user_pass'=>wp_generate_password(40),'display_name'=>'مشتری آزمایشی چرم','user_email'=>'','role'=>'customer']);
      if(is_wp_error($uid)){fwrite(STDERR,'Temporary customer creation failed.');exit(1);}
      (new WP_User($uid))->add_cap('manage_woocommerce');update_user_meta($uid,'show_admin_bar_front','false');
      echo wp_json_encode(['uid'=>$uid,'prefix'=>$prefix,'cookie_name'=>LOGGED_IN_COOKIE,'cookie'=>wp_generate_auth_cookie($uid,time()+900,'logged_in'),'products'=>$products,'ids'=>$ids,'currency'=>get_woocommerce_currency(),'globalMode'=>get_option('voiceweb_wc_purchase_mode','purchase'),'headerMenu'=>cn_get_site_menu_id('cn_header_menu'),'shop'=>wc_get_page_permalink('shop')]);
    `));
    const before = php(snapshotPHP(fixture.ids));
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.addCookies([{ name: fixture.cookie_name, value: fixture.cookie, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' }]);
    const page = await context.newPage();
    const errors = [];
    const blockedWrites = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/*', route => {
      const request = route.request();
      const url = new URL(request.url());
      if (/^https?:$/.test(url.protocol) && url.hostname !== new URL(site).hostname) return route.abort();
      if (!['GET', 'HEAD'].includes(request.method())) { blockedWrites.push(url.pathname); return route.abort(); }
      if (url.searchParams.has('add-to-cart')) return route.abort();
      return route.continue();
    });
    for (const product of fixture.products) {
      await page.goto(product.url, { waitUntil: 'networkidle' });
      await page.locator('form.variations_form').waitFor();
      assert.equal(await page.locator('.summary h1').textContent(), product.name);
      assert.ok(await page.locator('.cn-header-desktop a[href]').evaluateAll((links, shop) => links.some(link => link.href.replace(/\/$/, '') === shop.replace(/\/$/, '')), fixture.shop), 'The actual selected desktop header needs the native shop link.');
      assert.ok((await page.locator('.cn-header-desktop').textContent()).includes('مجلهٔ چرم'));
      await page.evaluate(() => {
        jQuery('form.variations_form').on('found_variation.voicewebReadOnlyTest', (_event, variation) => { window.voicewebSelectedTestVariation = variation; });
      });
      async function selectSwatch(attribute, value, values) {
        const select = page.locator(`form.variations_form select[name="attribute_${attribute}"]`);
        if (await select.inputValue() === value) {
          const other = values.find(candidate => candidate !== value);
          await page.locator(`form.variations_form .variable-item[data-value="${other}"]`).click();
          await page.waitForFunction(({ attribute, other }) => document.querySelector(`select[name="attribute_${attribute}"]`).value === other, { attribute, other });
        }
        const swatch = page.locator(`form.variations_form .variable-item[data-value="${value}"]`);
        assert.equal(await swatch.count(), 1, 'Use the actual installed plugin swatch rather than editing native select values.');
        await swatch.click();
        await page.waitForFunction(({ attribute, value }) => document.querySelector(`select[name="attribute_${attribute}"]`).value === value, { attribute, value });
      }
      const colors = [...new Set(product.rows.map(row => row.attributes.pa_color))];
      const sizes = [...new Set(product.rows.map(row => row.attributes.pa_size))];
      for (const expected of product.rows) {
        await selectSwatch('pa_color', expected.attributes.pa_color, colors);
        await selectSwatch('pa_size', expected.attributes.pa_size, sizes);
        await page.waitForFunction(id => Number(document.querySelector('form.variations_form input[name="variation_id"]').value) === id, expected.id);
        await page.waitForFunction(expectedImage => {
          const image = document.querySelector('.woocommerce-product-gallery__wrapper .flex-active-slide img:not(.zoomImg)') || document.querySelector('.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image img.wp-post-image');
          return image && image.dataset.large_image === expectedImage && image.complete && image.naturalWidth > 1;
        }, expected.native.image.full_src);
        const actual = await page.evaluate(() => {
          const form = document.querySelector('form.variations_form');
          const button = form.querySelector('.single_add_to_cart_button');
          const image = document.querySelector('.woocommerce-product-gallery__wrapper .flex-active-slide img:not(.zoomImg)') || document.querySelector('.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image img.wp-post-image');
          const variation = window.voicewebSelectedTestVariation;
          return { id: Number(form.querySelector('[name="variation_id"]').value), price: variation.display_price, regular: variation.display_regular_price, purchasable: variation.is_purchasable, image: image.getAttribute('src'), fullImage: image.dataset.large_image, buttonReady: !button.disabled && !button.classList.contains('disabled'), priceText: form.querySelector('.woocommerce-variation-price').textContent.trim() };
        });
        assert.equal(actual.id, expected.id);
        assert.equal(actual.price, expected.price);
        assert.equal(actual.price, expected.native.display_price);
        assert.equal(actual.regular, expected.native.display_regular_price);
        assert.equal(actual.purchasable, true);
        assert.equal(actual.buttonReady, true);
        assert.equal(actual.image, expected.native.image.src);
        assert.equal(actual.fullImage, expected.native.image.full_src);
        assert.ok(actual.priceText.length > 0);
        checks.push({ parent: product.id, variation: expected.id, color: expected.attributes.pa_color, size: expected.attributes.pa_size, nativePrice: actual.price, imageMatched: true, normalPurchaseReady: true });
      }
      for (const width of [1440, 390]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(() => { scrollTo({ top: 0, left: 0, behavior: 'instant' }); return document.fonts.ready; });
        await page.waitForFunction(() => Math.abs(scrollY) < 1);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${product.key}: horizontal overflow at ${width}`);
        assert.ok(await page.locator('.woocommerce-product-gallery').isVisible());
        if (output) {
          fs.mkdirSync(output, { recursive: true });
          await page.screenshot({ path: path.join(output, `leather-${product.key}-${width}.png`), fullPage: true });
          await page.screenshot({ path: path.join(output, `leather-${product.key}-${width}-viewport.png`) });
        }
      }
    }
    assert.deepEqual(errors, []);
    assert.equal(checks.length, 8);
    unchanged = before === php(snapshotPHP(fixture.ids));
    assert.equal(unchanged, true, 'Reading and changing swatches must leave the existing product/variation data unchanged.');
    console.log(JSON.stringify({ actualSeededProducts: fixture.products.map(p => p.id), currency: fixture.currency, globalMode: fixture.globalMode, selectedHeaderMenu: fixture.headerMenu, combinations: checks, responsive: [1440, 390], productDataUnchanged: unchanged, javascriptErrors: errors.length, addToCartClicks: 0, orders: 0, leads: 0, SMS: 0, payments: 0, blockedWriteRequests: blockedWrites.length }));
  } finally {
    if (browser) await browser.close();
    if (fixture) {
      const cleaned = JSON.parse(php(`require_once ABSPATH.'wp-admin/includes/user.php';global $wpdb;$uid=${Number(fixture.uid)};$user=get_user_by('id',$uid);if($user&&str_starts_with($user->user_login,'vwleather_test_')){$wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>(string)$uid]);wp_delete_user($uid);}echo wp_json_encode(['temporaryCustomerRemoved'=>!get_user_by('id',$uid),'sessionRemoved'=>!(bool)$wpdb->get_var($wpdb->prepare("SELECT session_key FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key=%s",(string)$uid))]);`));
      assert.equal(cleaned.temporaryCustomerRemoved && cleaned.sessionRemoved, true, 'The temporary customer and its Woo session must be removed.');
    }
  }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
