/* Real anonymous HTTP cache checks; fixture product only, no orders, payment or messages. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const site = process.env.VOICEWEB_WC_SITE || 'http://woo-charm-main.local';
const modulePath = path.resolve('wp-content/mu-plugins/voiceweb-cache.php');
const disabledPath = modulePath + '.test-disabled';
assert.ok(modulePath.startsWith(path.resolve(process.cwd()) + path.sep));
function php(source) {
  const start = `<?php define('DISABLE_WP_CRON',true);$_SERVER['HTTP_HOST']=parse_url('${site}',PHP_URL_HOST);$_SERVER['REQUEST_URI']='/';require getcwd().'/wp-load.php';add_filter('pre_wp_mail','__return_false');add_filter('pre_http_request',static fn()=>new WP_Error('cache_test_network','External requests disabled.'),PHP_INT_MAX);`;
  const result = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '10046')], { input: start + source, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error(result.error?.message || result.stderr || result.stdout);
  return result.stdout;
}
async function get(url, headers) { const response = await fetch(url, { headers }); return { response, html: await response.text(), cache: response.headers.get('x-voiceweb-cache') }; }
(async () => {
  let id, moved = false, marker; const checks = [];
  try {
    assert.ok(fs.existsSync(modulePath) && !fs.existsSync(disabledPath));
    const fixture = JSON.parse(php(`$p=new WC_Product_Simple();$p->set_name('vw_cache_fixture_'.bin2hex(random_bytes(5)));$p->set_status('publish');$p->set_catalog_visibility('hidden');$p->set_regular_price('1000000');$p->set_manage_stock(true);$p->set_stock_quantity(7);$p->update_meta_data('_voiceweb_purchase_mode','purchase');$p->save();echo wp_json_encode(['id'=>$p->get_id(),'url'=>get_permalink($p->get_id())]);`));
    id = fixture.id;
    const first = await get(fixture.url), second = await get(fixture.url);
    assert.equal(first.cache, 'MISS'); assert.equal(second.cache, 'HIT'); assert.equal(first.html, second.html); checks.push('guest MISS then identical HIT');
    php(`$p=wc_get_product(${id});$p->set_description('cache-content-updated-unique');$p->save();`);
    const changed = await get(fixture.url); assert.equal(changed.cache, 'MISS'); assert.ok(changed.html.includes('cache-content-updated-unique')); checks.push('editing content regenerates HTML');
    assert.equal((await get(fixture.url)).cache, 'HIT');
    php(`update_option('options_voiceweb_cache_fixture_${id}','first',false);`);
    assert.equal((await get(fixture.url)).cache, 'MISS'); await get(fixture.url);
    php(`update_option('options_voiceweb_cache_fixture_${id}','updated');`);
    assert.equal((await get(fixture.url)).cache, 'MISS'); checks.push('ACF option updates invalidate cached pages');
    marker = path.resolve(`wp-content/themes/voiceweb/assets/js/pages/voiceweb-cache-fixture-${id}.js`);
    fs.writeFileSync(marker, '// fixture');
    assert.equal((await get(fixture.url)).cache, 'MISS'); assert.equal((await get(fixture.url)).cache, 'HIT');
    fs.writeFileSync(marker, '// changed fixture asset content');
    assert.equal((await get(fixture.url)).cache, 'MISS'); checks.push('theme asset changes invalidate HTML without an admin save');
    for (const cookie of ['wordpress_logged_in_test=invalid', 'woocommerce_items_in_cart=1', 'wp_woocommerce_session_test=private', 'unknown_private_cookie=1']) assert.notEqual((await get(fixture.url, { Cookie: cookie })).cache, 'HIT');
    checks.push('login, cart, session and unknown cookies bypass shared cache');
    assert.notEqual((await get(fixture.url + '?vw_price_refresh=1')).cache, 'HIT');
    assert.notEqual((await get(fixture.url + '?vw_card_price_refresh=1')).cache, 'HIT');
    assert.notEqual((await get(site + '/wp-json/wc/store/v1/cart')).cache, 'HIT'); checks.push('price refresh and cart API bypass shared cache');
    const start = Number(php(`$p=wc_get_product(${id});$start=time()+5;$p->set_sale_price('800000');$p->set_date_on_sale_from($start);$p->set_date_on_sale_to($start+86400);$p->save();echo $start;`));
    assert.ok(!/<ins\b/.test((await get(fixture.url)).html)); assert.equal((await get(fixture.url)).cache, 'HIT');
    await new Promise(resolve => setTimeout(resolve, Math.max(0, start * 1000 + 1500 - Date.now())));
    const started = await get(fixture.url); assert.notEqual(started.cache, 'HIT'); assert.ok(/<ins\b/.test(started.html)); checks.push('future sales start without waiting for cached HTML or cron');
    const end = Number(php(`$p=wc_get_product(${id});$end=time()+5;$p->set_sale_price('800000');$p->set_date_on_sale_from(time()-60);$p->set_date_on_sale_to($end);$p->save();echo $end;`));
    const active = await get(fixture.url); assert.ok(/<ins\b/.test(active.html)); assert.equal((await get(fixture.url)).cache, 'HIT');
    await new Promise(resolve => setTimeout(resolve, Math.max(0, end * 1000 + 1500 - Date.now())));
    const ended = await get(fixture.url); assert.notEqual(ended.cache, 'HIT');
    assert.ok(!/<ins\b/.test(ended.html)); checks.push('cached sale expires at the real end and returns native regular pricing');
    await get(fixture.url); assert.equal((await get(fixture.url)).cache, 'HIT');
    const assetUrl = site + '/wp-content/themes/voiceweb/assets/js/global.js';
    assert.match((await fetch(assetUrl, {method:'HEAD'})).headers.get('cache-control') || '', /max-age=31536000/);
    fs.renameSync(modulePath, disabledPath); moved = true;
    assert.notEqual((await get(fixture.url)).cache, 'HIT'); checks.push('renaming the single module disables cached delivery');
    assert.ok(!/max-age=31536000/.test((await fetch(assetUrl, {method:'HEAD'})).headers.get('cache-control') || '')); checks.push('the same switch disables Apache browser-cache headers');
    php(`$p=wc_get_product(${id});$p->set_description('edited-while-cache-disabled');$p->save();`);
    await new Promise(resolve => setTimeout(resolve, 1100));
    fs.renameSync(disabledPath, modulePath); moved = false;
    const restored = await get(fixture.url); assert.ok(restored.html.includes('edited-while-cache-disabled')); assert.notEqual(restored.cache, 'HIT'); checks.push('re-enabling never revives content edited while disabled');
    assert.equal(Number(php(`echo wc_get_product(${id})->get_stock_quantity();`)), 7); checks.push('stock unchanged');
    console.log(JSON.stringify({ pass: true, checks }));
  } finally {
    if (moved && fs.existsSync(disabledPath)) fs.renameSync(disabledPath, modulePath);
    if (marker && fs.existsSync(marker)) fs.unlinkSync(marker);
    if (id) php(`delete_option('options_voiceweb_cache_fixture_${id}');`);
    if (id) php(`as_unschedule_all_actions('wc_product_start_scheduled_sale',['product_id'=>${id}],'woocommerce-sales');as_unschedule_all_actions('wc_product_end_scheduled_sale',['product_id'=>${id}],'woocommerce-sales');$p=wc_get_product(${id});if($p)$p->delete(true);`);
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
