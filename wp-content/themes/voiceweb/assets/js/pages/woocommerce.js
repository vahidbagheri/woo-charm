(function () {
  'use strict';
  function quantities() {
    document.querySelectorAll('.quantity .qty').forEach(function (input) {
      if (!input.getAttribute('aria-label')) input.setAttribute('aria-label', document.documentElement.lang.startsWith('fa') ? 'تعداد محصول' : 'Product quantity');
      input.setAttribute('inputmode', 'numeric');
    });
  }
  quantities();
  var persian = document.documentElement.lang.startsWith('fa');
  var formatter = new Intl.NumberFormat(persian ? 'fa-IR' : 'en-US', { useGrouping: false });
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var clockOffset = 0, clockReady = false, clockVerified = false, clockPromise = null;
  var clockListeners = [];
  function publicUrl() {
    var canonical = document.querySelector('link[rel="canonical"]');
    var url = new URL(canonical ? canonical.href : location.href);
    if (url.origin !== location.origin) url = new URL(location.href);
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
      .finally(function () { window.clearTimeout(timeout); clockReady = true; clockPromise = null; clockListeners.forEach(function (render) { render(); }); });
  }
  function renderTimeline(root, timer, deadline) {
    var seconds = Math.max(0, Math.ceil((deadline * 1000 - serverNow()) / 1000));
    var parts = [
      { key: 'days', value: Math.floor(seconds / 86400), label: persian ? 'روز' : 'days' },
      { key: 'hours', value: Math.floor(seconds / 3600) % 24, label: persian ? 'ساعت' : 'hours' },
      { key: 'minutes', value: Math.floor(seconds / 60) % 60, label: persian ? 'دقیقه' : 'minutes' },
      { key: 'seconds', value: seconds % 60, label: persian ? 'ثانیه' : 'seconds' }
    ];
    var first = parts.findIndex(function (part) { return part.value > 0; });
    if (first < 0) first = 3;
    var primary = parts.slice(first, first + 2), layout = primary.map(function (part) { return part.key; }).join('-');
    // A physical left-to-right row keeps the day number on the left, regardless of Persian bidi rules.
    if (timer.dataset.vwClockLayout !== layout || !timer.querySelector('.vw-sale-clock__units')) {
      timer.replaceChildren();
      timer.dir = 'ltr';
      timer.dataset.vwClockLayout = layout;
      var units = document.createElement('span');
      units.className = 'vw-sale-clock__units';
      units.dataset.single = primary.length === 1 ? 'true' : 'false';
      primary.forEach(function (part, index) {
        if (index) {
          var join = document.createElement('span'); join.className = 'vw-sale-clock__join';
          join.textContent = persian ? 'و' : 'and'; units.append(join);
        }
        var unit = document.createElement('span'); unit.className = 'vw-sale-clock__unit';
        unit.dataset.vwSaleUnit = part.key; unit.dir = 'ltr';
        var value = document.createElement('b'); value.dataset.vwSaleValue = part.key; value.dir = 'ltr';
        var label = document.createElement('span'); label.dir = persian ? 'rtl' : 'ltr'; label.textContent = part.label;
        unit.append(value, document.createTextNode(' '), label); units.append(unit);
      });
      var detail = document.createElement('span'); detail.className = 'vw-sale-clock__detail';
      detail.dir = persian ? 'rtl' : 'ltr';
      timer.append(units, detail);
    }
    primary.forEach(function (part) {
      var value = timer.querySelector('[data-vw-sale-value="' + part.key + '"]');
      var formatted = formatter.format(part.value);
      if (value.textContent === formatted) return;
      var changed = value.textContent !== ''; value.textContent = formatted;
      if (changed && !reducedMotion.matches && value.animate) value.animate([{ transform: 'translateY(-25%)', opacity: 0 }, { transform: 'translateY(0)', opacity: 1 }], { duration: 280, easing: 'ease-out' });
    });
    var joinText = persian ? ' و ' : ' and ';
    var trailing = parts.slice(first + 2).map(function (part) { return formatter.format(part.value) + ' ' + part.label; }).join(joinText);
    timer.querySelector('.vw-sale-clock__detail').textContent = (trailing ? trailing + ' ' : '') + (persian ? 'مانده' : 'remaining');
    timer.setAttribute('aria-label', parts.filter(function (part) { return part.value > 0; }).map(function (part) { return formatter.format(part.value) + ' ' + part.label; }).join(joinText) + (persian ? ' مانده تا پایان تخفیف' : ' remaining on this offer'));
    root.dataset.vwSaleUrgent = seconds < 3600 ? 'true' : 'false';
    timer.dateTime = new Date(deadline * 1000).toISOString();
    var start = Number(root.dataset.vwSaleStart), track = root.querySelector('[data-vw-sale-track]');
    if (track) {
      track.hidden = !(start > 0 && start < deadline);
      var remaining = track.hidden ? 0 : Math.max(0, Math.min(100, 100 * (deadline * 1000 - serverNow()) / ((deadline - start) * 1000)));
      track.setAttribute('aria-valuenow', String(Math.round(remaining)));
      track.firstElementChild.style.width = remaining.toFixed(2) + '%';
    }
    return seconds;
  }
  function setOffer(root, timer, offer) {
    timer.dataset.vwProductDeadline = offer.voiceweb_sale_deadline || 0;
    root.dataset.vwSaleStart = offer.voiceweb_sale_start || 0;
    root.dataset.vwSaleDiscount = offer.voiceweb_sale_discount || 0;
    root.querySelector('[data-vw-sale-percent]').textContent = new Intl.NumberFormat(persian ? 'fa-IR' : 'en-US').format(offer.voiceweb_sale_discount || 0) + (persian ? '٪' : '%');
    var start = Number(root.dataset.vwSaleStart);
    var dates = root.querySelector('[data-vw-sale-dates]');
    dates.hidden = !start;
    root.querySelector('[data-vw-sale-from]').textContent = offer.voiceweb_sale_from_label || '';
    root.querySelector('[data-vw-sale-to]').textContent = offer.voiceweb_sale_to_label || '';
  }
  var timer = document.querySelector('[data-vw-product-deadline]');
  var panel = document.querySelector('[data-vw-product-offer]');
  if (timer && panel) {
    var product = timer.closest('div.product');
    var summary = product && product.querySelector('.summary');
    var form = window.jQuery ? window.jQuery(product).find('form.variations_form').first() : null;
    var seen = new Set(), pending = new Map(), refreshPromise = null, interval;
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
          } else {
            if (freshTimer) {
              var freshPanel = freshTimer.closest('[data-vw-product-offer]');
              setOffer(panel, timer, { voiceweb_sale_deadline: freshDeadline, voiceweb_sale_start: freshPanel.dataset.vwSaleStart, voiceweb_sale_discount: freshPanel.dataset.vwSaleDiscount, voiceweb_sale_from_label: freshPanel.querySelector('[data-vw-sale-from]').textContent, voiceweb_sale_to_label: freshPanel.querySelector('[data-vw-sale-to]').textContent });
            }
            else timer.dataset.vwProductDeadline = '0';
            renderTimer();
          }
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
      renderTimeline(panel, timer, deadline);
    }
    if (form && form.length) {
      form.on('found_variation', function (event, chosen) { setOffer(panel, timer, chosen); renderTimer(); });
      form.on('reset_data hide_variation', function () { timer.dataset.vwProductDeadline = '0'; renderTimer(); });
      var resolved = Number(form.find('input.variation_id').val());
      var variations = form.data('product_variations');
      if (resolved && Array.isArray(variations)) {
        var chosen = variations.find(function (value) { return Number(value.variation_id) === resolved; });
        if (chosen) setOffer(panel, timer, chosen);
      }
    }
    clockListeners.push(renderTimer);
    renderTimer();
    function start() { window.clearInterval(interval); interval = window.setInterval(function () { if (!document.hidden) renderTimer(); }, 1000); renderTimer(); }
    start();
    document.addEventListener('visibilitychange', renderTimer);
    window.addEventListener('pagehide', function () { window.clearInterval(interval); pending.forEach(function (timeout) { window.clearTimeout(timeout); }); pending.clear(); });
    window.addEventListener('pageshow', function (event) { if (event.persisted) { if (!clockVerified) clockReady = false; start(); } });
  }
  // Shared clock and one ticker also cover cards inserted by filters, load-more and homepage refreshes.
  var cardInterval, cardRefreshes = new Map();
  async function refreshCard(root) {
    var card = root.closest('[data-vw-product-id]');
    if (!card) return;
    var id = card.dataset.vwProductId;
    if (!cardRefreshes.has(id)) {
      cardRefreshes.set(id, (async function () {
        var controller = new AbortController(), timeout = window.setTimeout(function () { controller.abort(); }, 12000);
        try {
          var url = new URL(root.dataset.vwCardUrl, location.href);
          if (url.origin !== location.origin) throw new Error('Invalid product origin.');
          url.searchParams.set('vw_card_price_refresh', String(Math.floor(serverNow())));
          var response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
          if (!response.ok || new URL(response.url).origin !== location.origin) throw new Error('Card price refresh failed.');
          var parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
          var fresh = parsed.getElementById('product-' + id);
          var price = fresh && fresh.querySelector('.summary > .price');
          if (!price) throw new Error('Card product mismatch.');
          var now = readServerDate(response);
          var expired = [...fresh.querySelectorAll('[data-vw-product-deadline]')].some(function (node) { var end = Number(node.dataset.vwProductDeadline); return end > 0 && end * 1000 <= now; });
          var nativeForm = fresh.querySelector('form.variations_form');
          var data = nativeForm && JSON.parse(nativeForm.getAttribute('data-product_variations'));
          if (expired || (Array.isArray(data) && data.some(function (value) { return Number(value.voiceweb_sale_deadline) > 0 && Number(value.voiceweb_sale_deadline) * 1000 <= now; }))) throw new Error('Expired card response.');
          var badge = [...fresh.children].find(function (node) { return node.classList.contains('onsale'); });
          document.querySelectorAll('[data-vw-product-id="' + id + '"]').forEach(function (target) {
            var oldPrice = target.querySelector('.price');
            if (oldPrice) oldPrice.innerHTML = price.innerHTML;
            target.querySelectorAll('.onsale').forEach(function (node) { node.remove(); });
            if (badge) target.querySelector('.vw-product-card__media').prepend(document.importNode(badge, true));
          });
        } catch (error) {
          root.hidden = false;
          root.textContent = persian ? 'قیمت به‌روز در صفحهٔ محصول' : 'See product for the current price';
        } finally { window.clearTimeout(timeout); }
      })());
    }
    await cardRefreshes.get(id);
  }
  function renderCards() {
    document.querySelectorAll('[data-vw-card-offer]').forEach(function (root) {
      var cardTimer = root.querySelector('[data-vw-card-deadline]');
      if (!cardTimer) return;
      var end = Number(cardTimer.dataset.vwCardDeadline);
      if (!Number.isFinite(end) || end <= 0) return;
      ensureClock();
      if (!clockReady) return;
      var seconds = renderTimeline(root, cardTimer, end);
      if (!seconds && clockVerified) {
        root.hidden = true;
        if (serverNow() >= end * 1000 + 1100 && !root.dataset.vwExpired) { root.dataset.vwExpired = 'true'; refreshCard(root); }
      }
    });
  }
  function startCards() {
    window.clearInterval(cardInterval);
    if (!document.querySelector('[data-vw-card-offer]')) return;
    renderCards();
    cardInterval = window.setInterval(function () { if (!document.hidden) renderCards(); }, 1000);
  }
  clockListeners.push(renderCards);
  startCards();
  document.addEventListener('voiceweb:catalog-updated', startCards);
  document.addEventListener('visibilitychange', renderCards);
  window.addEventListener('pagehide', function () { window.clearInterval(cardInterval); });
  window.addEventListener('pageshow', function (event) { if (event.persisted) startCards(); });
  document.addEventListener('voiceweb:catalog-updated', quantities);
})();
