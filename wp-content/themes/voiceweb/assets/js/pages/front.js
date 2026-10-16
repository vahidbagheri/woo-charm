(function () {
  'use strict';
  const hero = document.querySelector('[data-vw-hero]');
  if (hero) setupHero(hero);
  const knownTimers = new WeakSet();
  let clockOffset = 0;
  let clockReady = !document.querySelector('[data-vw-offer-deadline]'), clockVerified = false;
  setupTimers(document);

  function publicUrl() {
    const canonical = document.querySelector('link[rel="canonical"]');
    const url = new URL(canonical?.href || location.href);
    if (url.origin !== location.origin) return new URL(location.origin + location.pathname);
    [...url.searchParams.keys()].forEach(key => { if (!['p', 'page_id', 'lang'].includes(key)) url.searchParams.delete(key); });
    return url;
  }

  // HTTP Date avoids trusting an incorrectly set device clock or an old cached HTML stamp.
  if (document.querySelector('[data-vw-offer-deadline]')) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 5000);
    fetch(publicUrl(), { method: 'HEAD', cache: 'no-store', credentials: 'same-origin', signal: controller.signal })
      .then(response => { const time = Date.parse(response.headers.get('Date')); if (response.ok && Number.isFinite(time)) { clockOffset = time - Date.now(); clockVerified = true; } })
      .catch(() => {})
      .finally(() => { clearTimeout(timeout); clockReady = true; });
  }

  function setupHero(root) {
    const slides = [...root.querySelectorAll('[data-vw-slide]')];
    if (slides.length < 2) return;
    const controls = root.querySelector('[data-vw-hero-controls]');
    const dots = [...root.querySelectorAll('[data-vw-slide-to]')];
    const pause = root.querySelector('[data-vw-pause]');
    const status = root.querySelector('[data-vw-slide-status]');
    const number = root.querySelector('[data-vw-slide-number]');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const automatic = root.dataset.autoplay === '1';
    const digits = value => String(value).replace(/\d/g, digit => '۰۱۲۳۴۵۶۷۸۹'[digit]);
    let current = 0, timer, paused = false, hovered = false, gesture;

    function schedule() {
      clearTimeout(timer);
      if (!automatic || paused || hovered || reduced.matches || document.hidden || root.contains(document.activeElement)) return;
      timer = setTimeout(() => show(current + 1, false), Math.max(5, Number(root.dataset.interval) || 8) * 1000);
    }
    function hydrate(slide) {
      slide.querySelectorAll('[data-vw-srcset]').forEach(image => { image.setAttribute('srcset', image.dataset.vwSrcset); delete image.dataset.vwSrcset; });
      slide.querySelectorAll('[data-vw-src]').forEach(image => { image.src = image.dataset.vwSrc; delete image.dataset.vwSrc; });
    }
    function show(index, announce = true) {
      current = (index + slides.length) % slides.length;
      hydrate(slides[current]);
      slides.forEach((slide, i) => { slide.hidden = i !== current; slide.inert = i !== current; slide.setAttribute('aria-hidden', String(i !== current)); });
      dots.forEach((button, i) => button.setAttribute('aria-pressed', String(i === current)));
      number.textContent = `${digits(String(current + 1).padStart(2, '0'))} / ${digits(String(slides.length).padStart(2, '0'))}`;
      if (announce) status.textContent = `اسلاید ${slides[current].getAttribute('aria-label')}`;
      schedule();
    }
    controls.hidden = false; pause.hidden = !automatic;
    root.querySelector('[data-vw-next]').addEventListener('click', () => show(current + 1));
    root.querySelector('[data-vw-previous]').addEventListener('click', () => show(current - 1));
    dots.forEach((button, i) => button.addEventListener('click', () => show(i)));
    pause.addEventListener('click', () => { paused = !paused; pause.setAttribute('aria-pressed', String(paused)); schedule(); });
    controls.addEventListener('keydown', event => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      if (event.key === 'Home') show(0);
      else if (event.key === 'End') show(slides.length - 1);
      else show(current + ((event.key === 'ArrowLeft') === (getComputedStyle(root).direction === 'rtl') ? 1 : -1));
    });
    root.addEventListener('pointerdown', event => {
      if (event.pointerType !== 'mouse' && !event.target.closest('a,button,input')) gesture = { x: event.clientX, y: event.clientY };
    });
    root.addEventListener('pointerup', event => {
      if (!gesture) return;
      const dx = event.clientX - gesture.x, dy = event.clientY - gesture.y; gesture = null;
      if (Math.abs(dx) < 50 || Math.abs(dx) < Math.abs(dy) * 1.5) return;
      paused = true; pause.setAttribute('aria-pressed', 'true');
      show(current + ((dx > 0) === (getComputedStyle(root).direction === 'rtl') ? 1 : -1));
    });
    root.addEventListener('pointercancel', () => { gesture = null; });
    root.addEventListener('mouseenter', () => { hovered = true; clearTimeout(timer); });
    root.addEventListener('mouseleave', () => { hovered = false; schedule(); });
    root.addEventListener('focusin', () => clearTimeout(timer));
    root.addEventListener('focusout', () => requestAnimationFrame(schedule));
    document.addEventListener('visibilitychange', schedule); reduced.addEventListener('change', schedule);
    window.addEventListener('pagehide', () => clearTimeout(timer)); window.addEventListener('pageshow', schedule);
    schedule();
  }

  function setupTimers(scope) {
    scope.querySelectorAll('[data-vw-offer-deadline]').forEach(root => {
      if (knownTimers.has(root)) return;
      knownTimers.add(root);
      const end = Number(root.dataset.vwOfferDeadline);
      if (!Number.isFinite(end)) return;
      let interval;
      const format = new Intl.NumberFormat(document.documentElement.lang || 'fa-IR', { minimumIntegerDigits: 2, useGrouping: false });
      function tick() {
        if (!root.isConnected) { clearInterval(interval); return; }
        if (!clockReady) return;
        const remaining = Math.max(0, Math.ceil((end - Date.now() - clockOffset) / 1000));
        if (!remaining && !clockVerified) return;
        const values = { days: Math.floor(remaining / 86400), hours: Math.floor(remaining % 86400 / 3600), minutes: Math.floor(remaining % 3600 / 60), seconds: remaining % 60 };
        Object.entries(values).forEach(([unit, value]) => { const slot = root.querySelector(`[data-vw-time-${unit}]`); if (slot) slot.textContent = format.format(value); });
        if (!remaining) {
          clearInterval(interval); root.setAttribute('data-vw-expired', 'true');
          root.textContent = 'زمان این پیشنهاد به پایان رسید.'; root.setAttribute('role', 'status');
          // Woo treats the final timestamp second as inclusive; read after it passes.
          setTimeout(() => refreshExpiredProducts(root), 1100);
        }
      }
      interval = setInterval(tick, 1000); tick();
      window.addEventListener('pagehide', () => clearInterval(interval));
      window.addEventListener('pageshow', event => {
        if (event.persisted && root.isConnected && root.dataset.vwExpired !== 'true') {
          interval = setInterval(tick, 1000); tick();
        }
      });
    });
  }
  async function refreshExpiredProducts(timer) {
    const section = timer.closest('.vw-store-products');
    if (!section || section.dataset.vwRefreshing) return;
    section.dataset.vwRefreshing = '1';
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 12000);
    try {
      const url = publicUrl(); url.searchParams.set('vw_offer_refresh', String(Date.now()));
      const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin', signal: controller.signal, headers: { 'Accept': 'text/html' } });
      if (!response.ok) return;
      const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
      const fresh = parsed.querySelector('.vw-store-products');
      const freshDeadline = Number(fresh?.querySelector('[data-vw-offer-deadline]')?.dataset.vwOfferDeadline);
      if (freshDeadline > 0 && freshDeadline <= Date.now() + clockOffset) return;
      const grid = fresh?.querySelector('.vw-product-grid');
      if (!grid) return;
      section.querySelector('.vw-product-grid').replaceWith(document.importNode(grid, true));
      document.dispatchEvent(new CustomEvent('voiceweb:catalog-updated', { detail: { root: section } }));
    } catch { /* Current selection remains usable; native prices are checked again during purchase. */ }
    finally { clearTimeout(timeout); delete section.dataset.vwRefreshing; }
  }
})();
