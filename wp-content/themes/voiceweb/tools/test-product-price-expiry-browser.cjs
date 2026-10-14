/* Real native-sale boundary, disabled cron, no orders/payment/SMS; disposable product/customer fixtures. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const { spawnSync } = require('node:child_process');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_WC_SITE || 'http://woo-theme-1405.local';
const prefix = 'vwprice_browser_test_' + crypto.randomBytes(5).toString('hex');
const guard = path.join(process.cwd(), 'wp-content', 'mu-plugins', 'voiceweb-product-price-expiry-test-guard.php');
function php(source) {
  const bootstrap = `<?php define('CN_LOG_DIR',sys_get_temp_dir().'/voiceweb-product-price-browser');define('DISABLE_WP_CRON',true);$_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE')?:'http://woo-theme-1405.local',PHP_URL_HOST);$_SERVER['REQUEST_URI']='/';require getcwd().'/wp-load.php';add_filter('pre_wp_mail','__return_false');add_filter('pre_http_request',static fn()=>new WP_Error('test_no_network','No external requests.'),PHP_INT_MAX);`;
  const result = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '3306')], { input: bootstrap + source, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error(result.error ? result.error.message : result.stderr || 'PHP fixture failed.');
  return result.stdout;
}
(async () => {
  let fixture, browser;
  assert.ok(!fs.existsSync(guard), 'A previous test guard must not be overwritten.');
  try {
    fixture = JSON.parse(php(`
      $prefix='${prefix}';$uid=wp_insert_user(['user_login'=>$prefix,'user_pass'=>wp_generate_password(40),'role'=>'customer','user_email'=>'']);if(is_wp_error($uid))exit(1);(new WP_User($uid))->add_cap('manage_woocommerce');update_user_meta($uid,'show_admin_bar_front','false');$ids=[];
      foreach(['simple','plain'] as $type){$p=new WC_Product_Simple();$p->set_name($prefix.'_'.$type);$p->set_status('publish');$p->set_regular_price('1000000');if($type==='simple'){$p->set_sale_price('750000');$p->set_date_on_sale_from(time()-60);$p->set_date_on_sale_to(time()+7*DAY_IN_SECONDS);}$p->set_manage_stock(true);$p->set_stock_quantity(7);$p->update_meta_data('_voiceweb_purchase_mode','purchase');$p->update_meta_data('_voiceweb_price_browser_test',$prefix);$ids[$type]=$p->save();}
      $p=new WC_Product_Variable();$p->set_name($prefix.'_variable');$p->set_status('publish');$a=new WC_Product_Attribute();$a->set_name('model');$a->set_options(['standard','large']);$a->set_variation(true);$a->set_visible(true);$p->set_attributes([$a]);$p->set_default_attributes(['model'=>'standard']);$p->update_meta_data('_voiceweb_purchase_mode','purchase');$p->update_meta_data('_voiceweb_price_browser_test',$prefix);$ids['variable']=$p->save();
      foreach(['standard'=>['2000000','1500000'],'large'=>['3000000','2000000']] as $model=>$prices){$v=new WC_Product_Variation();$v->set_parent_id($p->get_id());$v->set_status('publish');$v->set_attributes(['model'=>$model]);$v->set_regular_price($prices[0]);$v->set_sale_price($prices[1]);$v->set_date_on_sale_from(time()-60);$v->set_date_on_sale_to(time()+7*DAY_IN_SECONDS);$v->set_manage_stock(true);$v->set_stock_quantity(5);$v->update_meta_data('_voiceweb_price_browser_test',$prefix);$ids[$model]=$v->save();}WC_Product_Variable::sync($p->get_id());
      echo wp_json_encode(['uid'=>$uid,'ids'=>$ids,'cookie_name'=>LOGGED_IN_COOKIE,'cookie'=>wp_generate_auth_cookie($uid,time()+1800,'logged_in'),'simple'=>get_permalink($ids['simple']),'variable'=>get_permalink($ids['variable']),'plain'=>get_permalink($ids['plain'])]);
    `));
    fs.mkdirSync(path.dirname(guard), { recursive: true });
    fs.writeFileSync(guard, `<?php // Disposable guard, applies only to this fixture customer's requests.
      function voiceweb_price_fixture_request(){return str_starts_with(wp_get_current_user()->user_login,'${prefix}');}
      add_action('plugins_loaded',static function(){if(voiceweb_price_fixture_request()&&!defined('DISABLE_WP_CRON'))define('DISABLE_WP_CRON',true);},1);
      add_filter('action_scheduler_allow_async_request_runner',static fn($allow)=>voiceweb_price_fixture_request()?false:$allow);
      add_filter('pre_wp_mail',static fn($value)=>voiceweb_price_fixture_request()?false:$value,PHP_INT_MAX);
      add_filter('pre_http_request',static fn($value)=>voiceweb_price_fixture_request()?new WP_Error('test_no_external','External requests disabled.'):$value,PHP_INT_MAX);
    `);
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
    await context.addCookies([{ name: fixture.cookie_name, value: fixture.cookie, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' }]);
    // Simulate a device clock ahead by eight days; only the server HTTP Date can correct it.
    await context.addInitScript(() => { const actual = Date.now.bind(Date); Date.now = () => actual() + 8 * 86400000; });
    await context.route('**/*', route => {
      const url = new URL(route.request().url());
      if (url.hostname !== new URL(site).hostname || (route.request().method() === 'POST' && /voiceweb-auth|inquiries|voiceweb-payments|\/checkout/.test(url.pathname))) return route.abort();
      return route.continue();
    });
    const simple = await context.newPage(), variable = await context.newPage(), plain = await context.newPage();
    const errors = [], refreshes = { simple: 0, variable: 0, plain: 0 };
    for (const [name,page] of Object.entries({simple,variable,plain})) {
      page.on('pageerror', error => errors.push(error.message));
      page.on('request', request => { if (request.method() === 'GET' && new URL(request.url()).searchParams.has('vw_price_refresh')) refreshes[name]++; });
    }
    await Promise.all([simple.goto(fixture.simple,{waitUntil:'networkidle'}),variable.goto(fixture.variable,{waitUntil:'networkidle'}),plain.goto(fixture.plain,{waitUntil:'networkidle'})]);
    await simple.waitForFunction(() => !!document.querySelector('[data-vw-product-deadline]').textContent);
    await variable.waitForFunction(() => !!document.querySelector('[data-vw-product-deadline]').textContent);
    assert.ok(await simple.locator('[data-vw-product-offer]').isVisible());
    assert.ok(await variable.locator('[data-vw-product-offer]').isVisible());
    assert.deepEqual(refreshes,{simple:0,variable:0,plain:0},'A long active sale and an inactive product perform no expiry GET, even with a wrong device clock.');
    const ends=Number(php(`$ends=time()+6;foreach([${fixture.ids.simple},${fixture.ids.standard},${fixture.ids.large}] as $id){$p=wc_get_product($id);$p->set_date_on_sale_to($ends);$p->save();}WC_Product_Variable::sync(${fixture.ids.variable});echo $ends;`));
    // Reload real server HTML; no DOM deadline/price mutation simulates this boundary.
    await Promise.all([simple.reload({waitUntil:'domcontentloaded'}),variable.reload({waitUntil:'domcontentloaded'})]);
    await variable.waitForFunction(id => Number(document.querySelector('form.variations_form input.variation_id').value)===id,fixture.ids.standard);
    assert.equal(Number(await simple.locator('[data-vw-product-deadline]').getAttribute('data-vw-product-deadline')),ends);
    assert.equal(Number(await variable.locator('[data-vw-product-deadline]').getAttribute('data-vw-product-deadline')),ends);
    assert.ok(await simple.locator('.summary > .price ins').count());
    await variable.locator('.woocommerce-variation-price .price ins').waitFor({state:'attached',timeout:5000});
    await variable.locator('form.variations_form .qty').fill('2');
    await variable.evaluate(() => { window.originalVariationForm=document.querySelector('form.variations_form'); });
    await simple.waitForFunction(() => !document.querySelector('.summary > .price ins') && document.querySelector('.summary > .price .amount'),null,{timeout:20000});
    await variable.waitForFunction(() => !document.querySelector('.summary > .price ins') && !document.querySelector('.woocommerce-variation-price .price ins') && document.querySelector('.woocommerce-variation-price .price .amount'),null,{timeout:20000});
    function amountValues(page, selector) {
      return page.locator(selector+' .amount').evaluateAll(nodes=>nodes.map(node=>Number(node.textContent.replace(/[۰-۹]/g,char=>'۰۱۲۳۴۵۶۷۸۹'.indexOf(char)).replace(/[٠-٩]/g,char=>'٠١٢٣٤٥٦٧٨٩'.indexOf(char)).replace(/\D/g,''))));
    }
    assert.deepEqual(await amountValues(simple,'.summary > .price'),[1000000]);
    assert.deepEqual(await amountValues(variable,'.woocommerce-variation-price .price'),[2000000]);
    assert.equal(await simple.locator('div.product > .onsale').count(),0);
    assert.equal(await variable.locator('div.product > .onsale').count(),0);
    assert.equal(await variable.evaluate(()=>document.querySelector('form.variations_form')===window.originalVariationForm),true);
    assert.equal(await variable.locator('form.variations_form .qty').inputValue(),'2');
    assert.equal(Number(await variable.locator('form.variations_form input.variation_id').inputValue()),fixture.ids.standard);
    const freshData=await variable.evaluate(()=>({attribute:JSON.parse(document.querySelector('form.variations_form').getAttribute('data-product_variations')),cache:jQuery('form.variations_form').data('product_variations')}));
    for(const data of [freshData.attribute,freshData.cache]){assert.ok(data.every(value=>value.voiceweb_sale_deadline===0));assert.deepEqual(data.map(value=>value.display_price).sort((a,b)=>a-b),[2000000,3000000]);}
    const swatch=variable.locator('.variable-item[data-value="large"]');
    if(await swatch.count())await swatch.click();else await variable.locator('select[name="attribute_model"]').selectOption('large');
    await variable.waitForFunction(id=>Number(document.querySelector('form.variations_form input.variation_id').value)===id,fixture.ids.large);
    await variable.waitForFunction(()=>{const price=document.querySelector('.woocommerce-variation-price .price .amount');return price&&Number(price.textContent.replace(/[۰-۹]/g,char=>'۰۱۲۳۴۵۶۷۸۹'.indexOf(char)).replace(/[٠-٩]/g,char=>'٠١٢٣٤٥٦٧٨٩'.indexOf(char)).replace(/\D/g,''))===3000000;},null,{timeout:5000});
    assert.deepEqual(await amountValues(variable,'.woocommerce-variation-price .price'),[3000000]);
    assert.deepEqual(refreshes,{simple:1,variable:1,plain:0});
    const state=JSON.parse(php(`echo wp_json_encode(['simple_stock'=>(int)wc_get_product(${fixture.ids.simple})->get_stock_quantity(),'variation_stock'=>(int)wc_get_product(${fixture.ids.standard})->get_stock_quantity(),'orders'=>count(wc_get_orders(['customer_id'=>${fixture.uid},'limit'=>-1]))]);`));
    assert.deepEqual(state,{simple_stock:7,variation_stock:5,orders:0});assert.deepEqual(errors,[]);
    console.log(JSON.stringify({actualBoundarySeconds:6,serverClockCorrectedEightDaySkew:true,longSaleNoExpiryGet:true,inactiveNoExpiryGet:true,simpleRegularPrice:true,variationDomAndNativeCacheFresh:true,nativeFormAndQuantityPreserved:true,reselectedVariationRegularPrice:true,oneGetPerBoundary:refreshes,stockUnchanged:true,orders:0,SMS:0,payments:0,javascriptErrors:0}));
  } finally {
    if(browser)await browser.close();
    php(`require_once ABSPATH.'wp-admin/includes/user.php';global $wpdb;$ids=$wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_voiceweb_price_browser_test' AND meta_value=%s",'${prefix}'));foreach(array_reverse($ids) as $id){as_unschedule_all_actions('wc_product_start_scheduled_sale',['product_id'=>(int)$id],'woocommerce-sales');as_unschedule_all_actions('wc_product_end_scheduled_sale',['product_id'=>(int)$id],'woocommerce-sales');$p=wc_get_product($id);if($p)$p->delete(true);}$u=get_user_by('login','${prefix}');if($u){$wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>(string)$u->ID]);wp_delete_user($u->ID);}`);
    if(fs.existsSync(guard))fs.unlinkSync(guard);
  }
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
