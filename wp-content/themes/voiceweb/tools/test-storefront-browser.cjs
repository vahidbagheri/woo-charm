/* Read-only checks of the configured store: no new users, orders, leads, SMS or payments. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_WC_SITE || 'http://woo-theme-1405.local';
const output = process.env.VOICEWEB_WC_SCREENSHOTS;
const source = `<?php define('DISABLE_WP_CRON',true);define('CN_LOG_DIR',sys_get_temp_dir().'/voiceweb-storefront-tests');$_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE')?:'http://woo-theme-1405.local',PHP_URL_HOST);$_SERVER['REQUEST_URI']='/';require getcwd().'/wp-load.php';add_filter('pre_wp_mail','__return_false');add_filter('pre_http_request',static fn()=>new WP_Error('test_no_network','No external requests.'),PHP_INT_MAX);$about=voiceweb_front_value('about_link');echo wp_json_encode(['about'=>$about['url']??'', 'slides'=>count((array)voiceweb_front_value('slides',[])), 'categories'=>count(voiceweb_front_categories()), 'offers'=>count(voiceweb_front_products('offers')), 'arrivals'=>count(voiceweb_front_products('arrivals')), 'blog'=>count(voiceweb_front_posts()), 'deadline'=>voiceweb_front_offer_deadline(voiceweb_front_products('offers'))]);`;
(async () => {
  let browser;
  try {
    const info = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '3306')], { input: source, encoding: 'utf8', windowsHide: true });
    assert.equal(info.status, 0, info.stderr || 'PHP read-only bootstrap failed.');
    const data = JSON.parse(info.stdout); assert.ok(data.slides >= 2 && data.offers > 0 && data.deadline > Date.now()/1000);
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage(); const errors = [], heads = []; page.on('pageerror', error => errors.push(error.message));
    await page.addInitScript(() => { if(location.pathname==='/') history.replaceState(null,'',location.pathname+'?add-to-cart=0'); });
    page.on('request', request => { if(request.method()==='HEAD') heads.push(request.url()); });
    await page.route('**/*', route => {
      const url = new URL(route.request().url());
      if ((/^https?:$/.test(url.protocol) && url.hostname !== new URL(site).hostname) || /request-code|verify-code|voiceweb\/v1\/inquiries|voiceweb-payments/.test(url.pathname) && route.request().method() === 'POST') return route.abort();
      return route.continue();
    });
    await page.goto(site + '/', { waitUntil: 'networkidle' });
    assert.equal(await page.locator('[data-vw-slide]').count(), data.slides);
    assert.equal(await page.locator('h1').count(), 1);
    assert.ok(await page.locator('.cn-header-nav a').filter({hasText:'فروشگاه'}).count(), 'The configured leather header menu must expose the native shop.');
    assert.ok(await page.locator('script[src*="frontend/add-to-cart"]').count(), 'Homepage product cards need native Woo AJAX cart.');
    assert.equal(await page.locator('.vw-store-categories a').count(), data.categories);
    assert.equal(await page.locator('.vw-store-products').first().locator('.vw-product-card').count(), data.offers);
    assert.equal(await page.locator('.vw-store-products').last().locator('.vw-product-card').count(), data.arrivals);
    assert.equal(await page.locator('.vw-store-posts article').count(), data.blog);
    const slides = page.locator('[data-vw-slide]');
    const deferred = slides.nth(1).locator('.vw-store-hero__image');
    assert.ok((await deferred.getAttribute('src')).startsWith('data:'));
    assert.ok(await deferred.getAttribute('data-vw-src'));
    assert.equal(await slides.nth(1).evaluate(element => element.inert), true);
    assert.equal(await slides.first().locator('.vw-store-hero__image').getAttribute('fetchpriority'), 'high');
    await page.locator('[data-vw-next]').click();
    assert.equal(await slides.nth(1).isVisible(), true);
    assert.equal(await deferred.getAttribute('data-vw-src'), null);
    await page.locator('[data-vw-slide-to="0"]').click();
    assert.equal(await slides.first().isVisible(), true);
    await page.locator('[data-vw-next]').focus(); await page.keyboard.press('End');
    assert.equal(await slides.last().isVisible(), true);
    await page.keyboard.press('Home'); assert.equal(await slides.first().isVisible(), true);
    const tone = await slides.first().getAttribute('data-tone');
    await slides.first().evaluate(el => { el.dataset.tone='ink'; });
    assert.equal(await slides.first().evaluate(el => getComputedStyle(el).backgroundColor),'rgb(24, 60, 50)');
    await slides.first().evaluate((el,value) => { el.dataset.tone=value; },tone);
    const timer = page.locator('[data-vw-offer-deadline]');
    assert.equal(Number(await timer.getAttribute('data-vw-offer-deadline')), data.deadline*1000);
    const before = await timer.locator('[data-vw-time-seconds]').textContent();
    await page.waitForTimeout(2200); assert.notEqual(await timer.locator('[data-vw-time-seconds]').textContent(), before);
    assert.ok(heads.length && heads.every(url => !new URL(url).searchParams.has('add-to-cart')), 'Clock requests must never replay a Woo cart action.');
    await page.evaluate(() => { dispatchEvent(new PageTransitionEvent('pagehide')); dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true })); });
    const restored = await timer.locator('[data-vw-time-seconds]').textContent();
    await page.waitForTimeout(2200); assert.notEqual(await timer.locator('[data-vw-time-seconds]').textContent(), restored);
    for (const width of [320,390,768,1280,1440]) {
      await page.setViewportSize({ width, height: 900 }); await page.evaluate(() => scrollTo(0,0));
      await page.evaluate(() => document.fonts.ready);
      assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth+1), 'Home overflow at ' + width);
      assert.ok(await page.locator('.vw-store-hero__image').first().evaluate(img => img.complete && img.naturalWidth > 1));
      if (width < 768) {
        assert.equal(await page.locator('.vw-store-products').first().locator('.vw-product-grid').evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length), 2);
        const toggle = page.locator('[data-cn-footer-toggle]').first();
        await toggle.click(); assert.equal(await toggle.getAttribute('aria-expanded'),'true');
        await toggle.click(); assert.equal(await toggle.getAttribute('aria-expanded'),'false');
        await page.locator('.vwa-mobile-nav').waitFor();
        assert.ok(await page.locator('[data-vw-next]').evaluate(el => el.getBoundingClientRect().width >= 44));
        assert.ok(await page.locator('[data-vw-slide-to="0"]').evaluate(el => el.getBoundingClientRect().width >= 44));
      }
      if (output) {
        fs.mkdirSync(output,{recursive:true}); await page.evaluate(() => scrollTo(0,0));
        await page.screenshot({path:path.join(output,'home-'+width+'.png'),fullPage:true});
        if(width===390 || width===1440) await page.screenshot({path:path.join(output,'home-top-'+width+'.png'),fullPage:false});
      }
    }
    await page.emulateMedia({ reducedMotion: 'reduce' });
    assert.equal(await page.locator('.vw-store-hero__float').first().evaluate(el => getComputedStyle(el).animationName),'none');
    await page.goto(data.about,{waitUntil:'networkidle'});
    assert.ok(await page.locator('.vw-about-page').count());
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth+1));
    if(output) await page.screenshot({path:path.join(output,'about-1440.png'),fullPage:true});
    const plain = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 900 } });
    const nojs = await plain.newPage(); await nojs.goto(site+'/',{waitUntil:'load'});
    assert.equal(await nojs.locator('[data-vw-slide]').first().isVisible(),true);
    assert.equal(await nojs.locator('[data-vw-slide]').nth(1).isVisible(),false);
    assert.ok(await nojs.locator('.vw-store-hero__actions a').first().getAttribute('href'));
    assert.ok(await nojs.evaluate(() => document.documentElement.scrollWidth <= innerWidth+1));
    assert.deepEqual(errors,[]);
    console.log(JSON.stringify({ configuredContent:data, responsive:[320,390,768,1280,1440],deferredHero:true,keyboardControls:true,countdown:true,restoredCountdown:true,nativeFooterAccordion:true,reducedMotion:true,withoutJavaScript:true,javascriptErrors:0,orders:0,SMS:0,payments:0 }));
  } finally { if(browser) await browser.close(); }
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
