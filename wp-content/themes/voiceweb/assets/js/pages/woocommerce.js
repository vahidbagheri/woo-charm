(function () {
  'use strict';
  function quantities() {
    document.querySelectorAll('.quantity .qty').forEach(function (input) {
      if (!input.getAttribute('aria-label')) input.setAttribute('aria-label', document.documentElement.lang.startsWith('fa') ? 'تعداد محصول' : 'Product quantity');
      input.setAttribute('inputmode', 'numeric');
    });
  }
  quantities();
  var timer = document.querySelector('[data-vw-product-deadline]');
  var panel = document.querySelector('[data-vw-product-offer]');
  if (timer && panel) {
    var persian = document.documentElement.lang.startsWith('fa');
    var formatter = new Intl.NumberFormat(persian ? 'fa-IR' : 'en-US', { minimumIntegerDigits: 2, useGrouping: false });
    var product = timer.closest('div.product');
    var summary = product && product.querySelector('.summary');
    var form = window.jQuery ? window.jQuery(product).find('form.variations_form').first() : null;
    var clockOffset = 0, clockReady = false, clockVerified = false, clockPromise = null;
    var seen = new Set(), pending = new Map(), refreshPromise = null, interval;

    function publicUrl() {
      var canonical = document.querySelector('link[rel="canonical"]');
      var url = new URL(canonical ? canonical.href : location.href);
      if (url.origin !== location.origin) url = new URL(location.href);
      // A native ?add-to-cart URL must never be replayed by a price refresh/HEAD request.
      [...url.searchParams.keys()].forEach(function (key) {
        if (!['p', 'page_id', 'product', 'post_type', 'name', 'lang'].includes(key)) url.searchParams.delete(key);
      });
      return url;
    }
    function serverNow() { return Date.now() + clockOffset; }
    function readServerDate(response) {
      var date = Date.parse(response.headers.get('Date'));
      if (Number.isFinite(date)) { clockOffset = date - Date.now(); clockVerified = true; }
      return Number.isFinite(date) ? date : serverNow();
    }
    function ensureClock() {
      if (clockPromise || clockReady) return;
      var controller = new AbortController();
      var timeout = window.setTimeout(function () { controller.abort(); }, 5000);
      clockPromise = fetch(publicUrl(), { method: 'HEAD', credentials: 'same-origin', cache: 'no-store', signal: controller.signal })
        .then(function (response) { if (response.ok) readServerDate(response); })
        .catch(function () {})
        .finally(function () { window.clearTimeout(timeout); clockReady = true; clockPromise = null; renderTimer(); });
    }
    function feedback(error) {
      if (!summary) return;
      var message = summary.querySelector('[data-vw-price-refresh-feedback]');
      if (!error) { if (message) message.remove(); return; }
      if (!message) {
        message = document.createElement('p'); message.dataset.vwPriceRefreshFeedback = 'true';
        message.className = 'vw-wishlist-feedback is-error'; message.setAttribute('role', 'status');
        summary.appendChild(message);
      }
      message.textContent = persian ? 'برای دیدن قیمت به‌روز، صفحه را دوباره باز کنید.' : 'Reload this page to see the current price.';
    }
    async function refreshPrices() {
      // Serialize genuine overlapping boundaries without replacing the native form or user input.
      while (refreshPromise) await refreshPromise;
      refreshPromise = (async function () {
        var controller = new AbortController();
        var timeout = window.setTimeout(function () { controller.abort(); }, 12000);
        try {
          var url = publicUrl(); url.searchParams.set('vw_price_refresh', String(Math.floor(serverNow())));
          var response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal, headers: { 'Accept': 'text/html' } });
          if (!response.ok || new URL(response.url).origin !== location.origin) throw new Error('Price refresh failed.');
          var now = readServerDate(response);
          var parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
          var freshProduct = product && parsed.getElementById(product.id);
          var freshSummary = freshProduct && freshProduct.querySelector('.summary');
          var freshPrice = freshSummary && freshSummary.querySelector(':scope > .price');
          if (!freshPrice || !product.isConnected) throw new Error('Product response mismatch.');
          var freshTimer = freshProduct.querySelector('[data-vw-product-deadline]');
          var freshDeadline = Number(freshTimer && freshTimer.dataset.vwProductDeadline);
          if (freshDeadline > 0 && freshDeadline * 1000 <= now) throw new Error('Expired cached product HTML.');
          var freshForm = freshSummary.querySelector('form.variations_form');
          var variationData;
          if (form && form.length) {
            if (!freshForm || Number(freshForm.dataset.product_id) !== Number(form.attr('data-product_id'))) throw new Error('Variation response mismatch.');
            variationData = JSON.parse(freshForm.getAttribute('data-product_variations'));
            if (variationData !== false && !Array.isArray(variationData)) throw new Error('Invalid native variation data.');
            if (Array.isArray(variationData) && variationData.some(function (value) { return Number(value.voiceweb_sale_deadline) > 0 && Number(value.voiceweb_sale_deadline) * 1000 <= now; })) throw new Error('Expired cached variation HTML.');
          }
          var price = summary.querySelector(':scope > .price');
          if (price) price.replaceWith(document.importNode(freshPrice, true));
          var oldBadge = [...product.children].find(function (node) { return node.classList.contains('onsale'); });
          var newBadge = [...freshProduct.children].find(function (node) { return node.classList.contains('onsale'); });
          if (oldBadge) oldBadge.remove();
          if (newBadge) product.prepend(document.importNode(newBadge, true));
          feedback(false);
          if (form && form.length) {
            form.attr('data-product_variations', JSON.stringify(variationData));
            form.data('product_variations', variationData);
            // Native onReload updates its internal variationData/useAjax and triggers check_variations.
            form.trigger('reload_product_variations');
          } else { timer.dataset.vwProductDeadline = String(freshDeadline || 0); renderTimer(); }
          document.dispatchEvent(new CustomEvent('voiceweb:product-price-updated', { detail: { product: product.id } }));
        } catch (error) { feedback(true); }
        finally { window.clearTimeout(timeout); }
      })();
      try { await refreshPromise; } finally { refreshPromise = null; }
    }
    function scheduleRefresh(deadline) {
      if (!clockVerified || seen.has(deadline) || pending.has(deadline)) return;
      // Woo's native end timestamp is inclusive through that whole second.
      var delay = Math.max(0, deadline * 1000 + 1100 - serverNow());
      pending.set(deadline, window.setTimeout(function () {
        pending.delete(deadline); seen.add(deadline); refreshPrices();
      }, delay));
    }
    function renderTimer() {
      var deadline = Number(timer.dataset.vwProductDeadline);
      if (!Number.isFinite(deadline) || deadline <= 0) { panel.hidden = true; timer.textContent = ''; return; }
      ensureClock();
      // Wait for the server clock before deciding a boundary; a fast device clock cannot expire it.
      if (!clockReady) { panel.hidden = false; return; }
      var seconds = Math.max(0, Math.ceil((deadline * 1000 - serverNow()) / 1000));
      panel.hidden = !seconds;
      if (!seconds) { timer.textContent = ''; scheduleRefresh(deadline); return; }
      var days = Math.floor(seconds / 86400);
      var hours = Math.floor(seconds / 3600) % 24;
      var minutes = Math.floor(seconds / 60) % 60;
      var rest = seconds % 60;
      timer.textContent = (days ? formatter.format(days) + (persian ? ' روز · ' : ' days · ') : '') + [hours, minutes, rest].map(function (value) { return formatter.format(value); }).join(':');
      timer.dateTime = new Date(deadline * 1000).toISOString();
    }
    if (form && form.length) {
      form.on('found_variation', function (event, chosen) { timer.dataset.vwProductDeadline = chosen.voiceweb_sale_deadline || 0; renderTimer(); });
      form.on('reset_data hide_variation', function () { timer.dataset.vwProductDeadline = '0'; renderTimer(); });
      var resolved = Number(form.find('input.variation_id').val());
      var variations = form.data('product_variations');
      if (resolved && Array.isArray(variations)) {
        var chosen = variations.find(function (value) { return Number(value.variation_id) === resolved; });
        if (chosen) timer.dataset.vwProductDeadline = chosen.voiceweb_sale_deadline || 0;
      }
    }
    renderTimer();
    function start() { window.clearInterval(interval); interval = window.setInterval(function () { if (!document.hidden) renderTimer(); }, 1000); renderTimer(); }
    start();
    document.addEventListener('visibilitychange', renderTimer);
    window.addEventListener('pagehide', function () { window.clearInterval(interval); pending.forEach(function (timeout) { window.clearTimeout(timeout); }); pending.clear(); });
    window.addEventListener('pageshow', function (event) { if (event.persisted) { if (!clockVerified) clockReady = false; start(); } });
  }
  document.addEventListener('voiceweb:catalog-updated', quantities);
})();
