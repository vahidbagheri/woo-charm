/* Read-only launch checks: real HTTPS responses, metadata, sitemap and private-page bypass. */
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const site = (process.env.VOICEWEB_WC_SITE || 'https://woo-charm-main.local').replace(/\/$/, '');
const boot = `<?php define('DISABLE_WP_CRON',true);$_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE')?:'${site}',PHP_URL_HOST);$_SERVER['HTTPS']='on';$_SERVER['SERVER_PORT']=443;$_SERVER['REQUEST_URI']='/';require getcwd().'/wp-load.php';add_filter('pre_wp_mail','__return_false');add_filter('pre_http_request',static fn()=>new WP_Error('launch_test_offline','External requests disabled.'),PHP_INT_MAX);$ids=get_posts(['post_type'=>'product','post_status'=>'publish','numberposts'=>1,'fields'=>'ids','meta_key'=>'_voiceweb_avan_catalog_key']);$terms=get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'number'=>1]);$old=get_option('voiceweb_launch_index_manifest',[]);echo wp_json_encode(['product'=>get_permalink($ids[0]),'category'=>get_term_link(reset($terms)),'shop'=>wc_get_page_permalink('shop'),'private'=>array_map('wc_get_page_permalink',['cart','checkout','myaccount']),'legacy'=>get_permalink($old['posts'][0]),'legacy_term'=>get_term_link($old['terms'][0])]);`;
const bootstrap = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d','mysqli.default_port='+(process.env.VOICEWEB_WC_MYSQL_PORT || '10046')], {input:boot,encoding:'utf8',windowsHide:true});
assert.equal(bootstrap.status,0,bootstrap.stderr);
const urls = JSON.parse(bootstrap.stdout);
const checks = [];
function meta(html,name) { return [...html.matchAll(/<meta\b[^>]*>/gi)].filter(m=>new RegExp(`\\bname=["']${name}["']`,'i').test(m[0])).map(m=>m[0].match(/\bcontent=["']([^"']*)/i)?.[1] || ''); }
async function get(url) {
  assert.equal(new URL(url).origin,new URL(site).origin,'Every requested route must belong to the configured HTTPS site.');
  const response=await fetch(url);return {response,html:await response.text()};
}
(async()=>{
  for (const url of [site+'/',urls.shop,urls.category,urls.product]) {
    const {response,html}=await get(url); assert.equal(response.status,200,url);
    const canonical=[...html.matchAll(/<link\b[^>]*\brel=["']canonical["'][^>]*>/gi)];
    assert.equal(canonical.length,1,url+' needs exactly one canonical');
    assert.equal(new URL(canonical[0][0].match(/\bhref=["']([^"']*)/i)[1]).href,new URL(url).href);
    assert.equal(meta(html,'description').length,1);assert.ok(meta(html,'description')[0].length>20);
    assert.ok(!meta(html,'robots').some(value=>/noindex/.test(value)),url+' must be indexable');
    assert.ok(!/\b(?:src|href)=["']http:\/\//i.test(html),'No insecure image, stylesheet, script or navigation URL');
    for (const match of html.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) JSON.parse(match[1]);
  }
  checks.push('home/shop/category/product: HTTP 200, one self-canonical and description, indexable, valid JSON-LD, HTTPS URLs');
  assert.equal((await get(site+'/')).response.headers.get('x-voiceweb-cache'),'HIT');
  for (const url of urls.private) {
    for(let i=0;i<2;i++) {const {response,html}=await get(url);assert.equal(response.status,200);assert.notEqual(response.headers.get('x-voiceweb-cache'),'HIT');assert.ok(meta(html,'robots').some(value=>/noindex/.test(value)));}
  }
  checks.push('cart/checkout/account: noindex and never a shared-cache HIT');
  for(const url of [urls.legacy,urls.legacy_term]) {const {response,html}=await get(url);assert.equal(response.status,200);assert.ok(meta(html,'robots').some(value=>/noindex/.test(value)));}
  checks.push('legacy post and archive remain available with noindex');
  const missing=await get(site+'/voiceweb-launch-missing-'+Date.now()+'/');assert.equal(missing.response.status,404);assert.notEqual(missing.response.headers.get('x-voiceweb-cache'),'HIT');
  const sitemap=await get(site+'/wp-sitemap.xml');assert.equal(sitemap.response.status,200);assert.ok(sitemap.html.includes('<sitemapindex'));
  const pageMap=await get(site+'/wp-sitemap-posts-page-1.xml');assert.equal(pageMap.response.status,200);for(const url of urls.private)assert.ok(!pageMap.html.includes('<loc>'+url+'</loc>'));
  const robots=await get(site+'/robots.txt');assert.equal(robots.response.status,200);assert.ok(robots.html.includes('Sitemap: https://avancharm.ir/wp-sitemap.xml'));assert.ok(!robots.html.includes('yourdomain.com'));
  checks.push('404 stays uncached, native sitemap works and excludes private pages, robots names the production sitemap');
  const redirect=await fetch(site.replace(/^https:/,'http:')+'/',{redirect:'manual'});assert.ok([301,302,307,308].includes(redirect.status));assert.equal(new URL(redirect.headers.get('location')).protocol,'https:');
  checks.push('HTTP redirects to HTTPS');console.log(JSON.stringify({pass:true,checks}));
})().catch(error=>{console.error(error);process.exitCode=1;});
